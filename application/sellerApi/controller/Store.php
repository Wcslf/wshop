<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * 评论咨询投诉管理
 * @author soubao 当燃
 * @Date: 2017-03-20
 */

namespace app\sellerApi\controller;

use app\seller\logic\StoreLogic;
use think\Db;
use think\Page;

class Store extends Base
{
    /**
     * 店铺管理
     */
    public function index(){
        $data=$this->storeInfo;
        $data['count_goods'] =  Db::name('goods')->where(['store_id'=>STORE_ID])->count();//商品总数
        $data['count_order'] =  Db::name('order')->where(['store_id'=>STORE_ID])->count();//订单总数
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功！','result'=>$data]);
    }

    /**
     * 申请升级列表
     */
    public function storeReopen()
    {
        $StoreReopenModel =new  \app\common\model\StoreReopen();
        $count = $StoreReopenModel->where(['re_store_id'=>STORE_ID])->count();
        $page = new Page($count,20);
        $StoreReopenObj = $StoreReopenModel->where(['re_store_id'=>STORE_ID])->order('re_id desc')->limit($page->listRows,$page->firstRow)->select();
        if($StoreReopenObj) {
            $data = collection($StoreReopenObj)->append(['reopen_state'])->toArray();
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功！','result'=>$data]);
        }
        $this->ajaxReturn(['status' => -1, 'msg' => '获取失败！']);
    }

    /**
     * 店铺当前等级，获取所有等级
     */
    public function getStoreGrade(){
        $data['store_grade_id'] =$this->storeInfo['grade_id'];
        $store_reopen = Db::name('store_reopen')->where(['re_store_id'=>STORE_ID])->order('re_id desc')->find();
        $info['earlier']=$earlier= 30; //可提前申请时间
        $start_apply_time = $store_reopen['re_end_time']-($earlier*60*60*24);  //继续续期开始时间
        if($start_apply_time < time() && $store_reopen['re_state']==2){    //是否可续签时间
            $data['apply_status']= true;
        }else{
            $data['apply_status']=false;
        }
        $data['grade'] = Db::name('store_grade')->order('sg_id')->select();
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功！','result'=>$data]);
    }

    /**
     * 店铺等级申请提交
     */
    public function applyStoreGrade(){
        $post_data = I('post.');
        $StoreLogic =new StoreLogic();
        $StoreLogic ->setStoreInfo($this->storeInfo);
        $res = $StoreLogic ->editStoreReopen($post_data);
        $this->ajaxReturn($res);
    }
    /**
     * 获取店铺设置详细
     */
    public function getConfiguration()
    {
        if ($this->storeInfo) {
            $this->storeInfo['grade_name'] = Db::name('store_grade')->where("sg_id", $this->storeInfo['grade_id'])->getField('sg_name');
            $this->storeInfo['province'] = Db::name('region')->where(['id' => $this->storeInfo['province_id']])->find();
            $this->storeInfo['city'] = Db::name('region')->where(['id' => $this->storeInfo['city_id']])->find();
            $this->storeInfo['area'] = Db::name('region')->where(['id' => $this->storeInfo['district']])->find();
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功！','result'=>$this->storeInfo]);
        }
        $this->ajaxReturn(['status' => -1, 'msg' => '获取失败！']);
    }
    
    /**
     * 提交店铺设置
     */
    public function settingSave()
    {
        $data = I('post.');
        $res = Db::name('store')->where(["store_id"=>STORE_ID])->save($data);
        if ($res !== false) {
            $this->ajaxReturn(['status' => 1,'msg' => '操作成功']);
        }
        $this->ajaxReturn(['status' => -1,'msg' => '操作失败']);
    }

    /**
     * 店铺已申请的经营类目
     */
    public function bindClassList()
    {
        $where=[];
        $this->store['bind_all_gc'] == 0 && $where['store_id']=STORE_ID;
        $status = I('status/d');//0审核中1审核通过 2审核失败
        $status !== '' ? $where['state'] = $status : false;
        $count = Db::name('store_bind_class')->where($where)->count();
        $page = new Page($count,10);
        $bind_class_list = Db::name('store_bind_class')->where($where)->limit($page->listRows,$page->firstRow)->select();
        $goods_class = Db::name('goods_category')->getField('id,name');
        for ($i = 0, $j = count($bind_class_list); $i < $j; $i++) {
            $bind_class_list[$i]['class_1_name'] = $goods_class[$bind_class_list[$i]['class_1']];
            $bind_class_list[$i]['class_2_name'] = $goods_class[$bind_class_list[$i]['class_2']];
            $bind_class_list[$i]['class_3_name'] = $goods_class[$bind_class_list[$i]['class_3']];

        }
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功！','result'=>$bind_class_list]);
    }

    /**
     * 未经营类目
     */
    public function unboundClass()
    {
        $bind_class_list = Db::name('store_bind_class')->where(['store_id'=>STORE_ID])->select();
        $bind_class3 = get_arr_column($bind_class_list,'class_3');
        $data['class_level1'] = Db::name('goods_category')->where(['level'=>1])->select();
        $data['class_level2'] = Db::name('goods_category')->where(['level'=>2])->select();
        $class_level3 = Db::name('goods_category')->where(['level'=>3])->select();
        foreach ($class_level3 as $k3 => $l3){
            if(!in_array($l3['id'],$bind_class3)){   //去掉已经升级的三级分类
                $data['class_level3'][] = $l3;
            }
        }
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功！','result'=>$bind_class_list]);
    }
}