<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * Author: 二月鸟飞
 * 专题管理
 * Date: 2016-06-09
 */

namespace app\sellerApi\controller;
 
use app\common\model\FlashSale;
use app\common\model\Goods;
use app\common\model\GroupBuy;
use think\Db;
use think\Page;
use think\Loader;

class Promotion extends Base
{

    public $store_id;

    public function __construct()
    {
        parent::__construct();
        $this->store_id = STORE_ID;
    }
    /**
     * 选择活动商品
     */
    public function searchGoods()
    {
        $keywords = I('keywords');           //关键词
        $prom_id = input('prom_id');         //活动ID
        $prom_type = input('prom_type/d');   //活动类型
        $where = ['is_on_sale' => 1,'store_id' => $this->store_id,'is_virtual'=>0,'store_count'=>['gt',0],'exchange_integral'=>0];//基础搜索条件
        if ($keywords) {  //商品模糊查询
            $this->assign('keywords', $keywords);
            $where['goods_name|keywords'] = array('like', '%' . $keywords . '%');
        }
        $Goods = new Goods();
        $count = $Goods->where($where)->where(function ($query) use ($prom_type, $prom_id) {
            if($prom_type == 3){
                //优惠促销
                if ($prom_id) {
                    $query->where(['prom_id' => $prom_id, 'prom_type' => 3])->whereor('prom_type', 0);
                } else {
                    $query->where('prom_type', 0);
                }
            }else if(in_array($prom_type,[1,2,6])){
                //抢购，团购
                $query->where('prom_type','in' ,[0,$prom_type])->where('prom_id',0);
            }else{
                $query->where('prom_type',0);
            }
        })->count();
        $Page = new Page($count, 10);
        $goodsList = $Goods->with('specGoodsPrice')->where($where)->where(function ($query) use ($prom_type, $prom_id) {
            if($prom_type == 3){
                //优惠促销
                if ($prom_id) {
                    $query->where(['prom_id' => $prom_id, 'prom_type' => 3])->whereor('prom_type', 0);
                } else {
                    $query->where('prom_type', 0);
                }
            }else if(in_array($prom_type,[1,2,6])){
                //抢购，团购
                $query->where('prom_type','in' ,[0,$prom_type])->where('prom_id',0);
            }else{
                $query->where('prom_type',0);
            }
        })->order('goods_id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $data=[
            'goodsList' =>$goodsList,
            'prom_id'   =>$prom_id,
            'prom_type' =>$prom_type,
        ];
        $this->ajaxReturn(['status'=>1, 'msg'=>'获取成功','result'=>$data]);
    }

/****抢购****/

    /**
     * 限时抢购列表
     */
    public function flashSaleList()
    {
        $status  = input('status/d');
        $condition['store_id'] = $this->store_id;
        switch ($status){
            case 1: $condition['start_time'] = ['gt',time()] ; break;  //未开始
            case 2: $condition['status'] = 4 ; break;      //已售馨
            case 3: $condition['is_end'] = 0 ; break;      //已结束
            default :
                $condition['status'] = 1;
                $condition['is_end'] = 0;
                $condition['start_time'] = ['lt',time()];
                $condition['end_time']   = ['gt',time()];
                break;//进行中
        }
        $FlashSale = new FlashSale();
        $count = $FlashSale->where($condition)->count();
        $Page = new Page($count, 10);
        $prom_list_obj = $FlashSale->append(['status_desc','specGoodsPrice','goods'])->where($condition)->order("id desc")->limit($Page->firstRow . ',' . $Page->listRows)->select();
        if($prom_list_obj){
            $prom_list = collection($prom_list_obj)->append(['status_desc'])->toArray();
        }
        $this->ajaxReturn(['status'=>1, 'msg'=>'获取成功','result'=>$prom_list]);
    }

    /**
     * 活动详情
     * @return mixed
     */
    public function flashSale()
    {
        $id = I('id/d');
        empty($id) && $this->ajaxReturn(['status'=>-1, 'msg'=>'参数错误！']);
        $FlashSale = new FlashSale();
        $info = $FlashSale->with('specGoodsPrice,goods')->where('store_id', $this->store_id)->where('id', $id)->find();
        if(empty($info)){
            $this->ajaxReturn(['status'=>-1, 'msg'=>'该秒杀记录不翼而飞了']);
        }
        $info['prom_type'] = 1;
        $this->ajaxReturn(['status'=>1, 'msg'=>'获取成功','result'=>$info]);
    }

    /**
     * 添加编辑抢购
     */
    public function flashSaleHandle(){
        if (IS_POST) {
            $data = I('post.');
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time']);
            $flashSaleValidate = Loader::validate('\app\seller\validate\FlashSale');
            if (!$flashSaleValidate->batch()->check($data)) {
                $return = ['status' => -1, 'msg' => '操作失败',
                    'result'    => $flashSaleValidate->getError(),
                    'token'       =>  \think\Request::instance()->token(),
                ];
                $this->ajaxReturn($return);
            }
            if (empty($data['id'])) {
                $data['store_id'] = $this->store_id;
                $flashSaleInsertId = Db::name('flash_sale')->insertGetId($data);
                if($data['item_id'] > 0){
                    //设置商品一种规格为活动
                    Db::name('spec_goods_price')->where('item_id',$data['item_id'])->update(['prom_id' => $flashSaleInsertId, 'prom_type' => 1]);
                    Db::name('goods')->where("goods_id", $data['goods_id'])->save(array('prom_id' => 0, 'prom_type' => 1));
                }else{
                    Db::name('goods')->where("goods_id", $data['goods_id'])->save(array('prom_id' => $flashSaleInsertId, 'prom_type' => 1));
                }
                sellerLog("管理员添加抢购活动 " . $data['name']);
                if ($flashSaleInsertId !== false) {
                    $this->ajaxReturn(['status' => 1, 'msg' => '添加抢购活动成功', 'result' => '']);
                } else {
                    $this->ajaxReturn(['status' => -1, 'msg' => '添加抢购活动失败', 'result' => '']);
                }
            } else {
                $flash_sale = Db::name('flash_sale')->where(['id' => $data['id'], 'store_id' => $this->store_id])->find();
                if(empty($flash_sale)){
                    $this->ajaxReturn(['status' => -1, 'msg' => '该秒杀记录不翼而飞啦~', 'result' => '']);
                }
                $r = M('flash_sale')->where(['id' => $data['id'], 'store_id' => $this->store_id])->save($data);
                M('goods')->where(['prom_type' => 1, 'prom_id' => $data['id']])->save(array('prom_id' => 0, 'prom_type' => 0));
                if($data['item_id'] > 0){
                    //设置商品一种规格为活动
                    Db::name('spec_goods_price')->where(['prom_type' => 1, 'prom_id' => $data['item_id']])->update(['prom_id' => 0, 'prom_type' => 0]);
                    Db::name('spec_goods_price')->where('item_id', $data['item_id'])->update(['prom_id' => $data['id'], 'prom_type' => 1]);
                    M('goods')->where("goods_id", $data['goods_id'])->save(array('prom_id' => 0, 'prom_type' => 1));
                }else{
                    M('goods')->where("goods_id", $data['goods_id'])->save(array('prom_id' => $data['id'], 'prom_type' => 1));
                }
                if ($r !== false) {
                    $this->ajaxReturn(['status' => 1, 'msg' => '编辑抢购活动成功', 'result' => '']);
                } else {
                    $this->ajaxReturn(['status' => -1, 'msg' => '编辑抢购活动失败', 'result' => '']);
                }
            }
        }
    }

    /**
     * 删除抢购活动
     */
    public function flashSaleDel()
    {
        $id = input('del_id/d');
        if ($id) {
            $flash_sale = M('flash_sale')->where(['id' => $id, 'store_id' => $this->store_id])->find();
            if(empty($flash_sale)){
                $this->ajaxReturn(['status' => -1, 'msg' => '抢购活动不存在！', 'result' => '']);
            }
            $spec_goods = Db::name('spec_goods_price')->where(['prom_type' => 1, 'prom_id' => $id])->find();
            //有活动商品规格
            if($spec_goods){
                Db::name('spec_goods_price')->where(['prom_type' => 1, 'prom_id' => $id])->save(array('prom_id' => 0, 'prom_type' => 0));
                //商品下的规格是否都没有活动
                $goods_spec_num = Db::name('spec_goods_price')->where(['prom_type' => 1, 'goods_id' => $spec_goods['goods_id']])->find();
                if(empty($goods_spec_num)){
                    //商品下的规格都没有活动,把商品回复普通商品
                    Db::name('goods')->where(['goods_id' => $spec_goods['goods_id']])->save(array('prom_id' => 0, 'prom_type' => 0));
                }
            }else{
                //没有商品规格
                Db::name('goods')->where(['prom_type' => 1, 'prom_id' => $id])->save(array('prom_id' => 0, 'prom_type' => 0));
            }
            M('flash_sale')->where(['id' => $id, 'store_id' => $this->store_id])->delete();
            $this->ajaxReturn(['status' =>1, 'msg' => '操作成功！',]);
        } else {
            $this->ajaxReturn(['status' => -1, 'msg' => '参数错误',]);
        }
    }

/*****团购活动*****/
    /**
     * @return mixed
     */
    public function groupBuyList()
    {
        $GroupBuy = new GroupBuy();
        $count = $GroupBuy->where("store_id", $this->store_id)->count();
        $Page = new Page($count, 10);
        $data = $GroupBuy->order('id desc')->where("store_id", $this->store_id)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        if($data){
            $this->ajaxReturn(['status' => 1,'msg' =>'获取成功','result' => $data]);
        }else{
            $this->ajaxReturn(['status' => 0,'msg' =>'没有更多数据！',]);
        }

    }
    /**
     * 团购详情
     */
    public function groupBuy()
    {
        $groupbuy_id = input('id/d');
        $group_info = array();
        empty($groupbuy_id) && $this->ajaxReturn(['status' => -1,'msg' =>'参数错误']);
        $GroupBy = new GroupBuy();
        $data = $GroupBy->with('specGoodsPrice,goods')->where(['id'=>$groupbuy_id,'store_id'=>$this->store_id])->find();
        if(empty($group_info)){
            $this->ajaxReturn(['status' => 1,'msg' => '该团购记录不翼而飞啦~']);
        }
        $data['prom_type'] = 2;
        $this->ajaxReturn(['status' => 1,'msg' =>'获取成功','result' => $data]);
    }

    /**
     * 添加编辑团购
     */
    public function groupBuyHandle()
    {
        $data = I('post.');
        $data['groupbuy_intro'] = htmlspecialchars(stripslashes($_POST['groupbuy_intro']));
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);
        if($data['item_id'] > 0){
            $spec_goods_price = Db::name("spec_goods_price")->where(['item_id'=>$data['item_id']])->find();
            $data['goods_price'] = $spec_goods_price['price'];
            $data['store_count'] = $spec_goods_price['store_count'];
        }else{
            $goods = Db::name("goods")->where(['goods_id'=>$data['goods_id']])->find();
            $data['goods_price'] = $goods['shop_price'];
            $data['store_count'] = $goods['store_count'];
        }
        $groupBuyValidate = Loader::validate('\app\seller\validate\GroupBuy');
        if(!$groupBuyValidate->batch()->check($data)){    //验证数据
            $msg = '';
            foreach ($groupBuyValidate->getError() as $k =>$v){
                $msg .= $v.'！';
            }
            $this->ajaxReturn(['status' => -1,'msg' =>$msg,'token'=> \think\Request::instance()->token()]);
        }
        $data['rebate'] = number_format($data['price'] / $data['goods_price'] * 10, 1);     //折扣率
        if ($data['id'] > 0) {          //编辑活动
            $group_buy = Db::name('group_buy')->where(['id' => $data['id'], 'store_id' => $this->store_id])->find();
            if(empty($group_buy)){
                $this->ajaxReturn(['status' => -1,'msg' =>'该团购记录不翼而飞啦~','result' => '']);
            }
            $r = Db::name('group_buy')->where(['id' => $data['id'], 'store_id' => $this->store_id])->update($data);
            if($data['item_id'] > 0){
                //设置商品一种规格为活动
                Db::name('spec_goods_price')->where(['prom_type' => 2, 'prom_id' => $data['id']])->update(['prom_id' => 0, 'prom_type' => 0]);
                Db::name('spec_goods_price')->where('item_id', $data['item_id'])->update(['prom_id' => $data['id'], 'prom_type' => 2]);
                M('goods')->where("goods_id", $data['goods_id'])->save(array('prom_id' => 0, 'prom_type' => 2));
            }else{
                M('goods')->where("goods_id", $data['goods_id'])->save(array('prom_id' => $data['id'], 'prom_type' => 2));
            }
        }else{          //添加活动
            $data['store_id'] = $this->store_id;
            $r = Db::name('group_buy')->insertGetId($data);
            if($data['item_id'] > 0){
                //设置商品一种规格为活动
                Db::name('spec_goods_price')->where('item_id',$data['item_id'])->update(['prom_id' => $r, 'prom_type' => 2]);
                Db::name('goods')->where("goods_id", $data['goods_id'])->save(array('prom_id' => 0, 'prom_type' => 2));
            }else{
                Db::name('goods')->where("goods_id", $data['goods_id'])->save(array('prom_id' => $r, 'prom_type' => 2));
            }
        }
        if ($r !== false) {
            $this->ajaxReturn(['status' => 1,'msg' =>'操作成功']);
        } else {
            $this->ajaxReturn(['status' => -1,'msg' =>'操作失败']);
        }
    }

    /**
     * 添加编辑团购
     */
    public function groupBuyDel(){
        $id = input('del_id/d');
        $group_buy = Db::name('group_buy')->where(['id' => $id, 'store_id' => $this->store_id])->find();
        empty($group_buy) && $this->ajaxReturn(['status' => -1,'msg' =>'参数错误！']);
        $spec_goods = Db::name('spec_goods_price')->where(['prom_type' => 2, 'prom_id' => $id])->find();
        //有活动商品规格
        if($spec_goods){
            Db::name('spec_goods_price')->where(['prom_type' => 2, 'prom_id' => $id])->save(array('prom_id' => 0, 'prom_type' => 0));
            //商品下的规格是否都没有活动
            $goods_spec_num = Db::name('spec_goods_price')->where(['prom_type' => 2, 'goods_id' => $spec_goods['goods_id']])->find();
            if(empty($goods_spec_num)){
                //商品下的规格都没有活动,把商品回复普通商品
                Db::name('goods')->where(['goods_id' => $spec_goods['goods_id']])->save(array('prom_id' => 0, 'prom_type' => 0));
            }
        }else{
            //没有商品规格
            Db::name('goods')->where(['prom_type' => 2, 'prom_id' => $id])->save(array('prom_id' => 0, 'prom_type' => 0));
        }
        $r = D('group_buy')->where(['id' => $id, 'store_id' => $this->store_id])->delete();
        if ($r !== false )$this->ajaxReturn(['status' => 1,'msg' =>'操作成功']);
        $this->ajaxReturn(['status' => -1,'msg' =>'操作失败！']);
    }

/*****商品促销活动*****/
}