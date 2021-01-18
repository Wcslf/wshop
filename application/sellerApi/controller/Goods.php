<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 *
 * Date: 2019-03-1
 */
namespace app\sellerApi\controller;

use app\seller\logic\GoodsCategoryLogic;
use app\seller\logic\GoodsLogic;
use app\common\model\StoreBindClass;
use think\Db;
use think\Page;
use think\AjaxPage;
use think\Loader;

class Goods extends Base
{

    /**
     *  商品列表
     */
    public function goodsList()
    {
        $where['store_id'] = STORE_ID;
        $is_sale = I('is_sale', '');   //上下架
        $cat_id = I('cat_id', '');   //一级分类id
        $key_word = I('key_word', '');   //商品名称查询

        switch ($is_sale){   //默认显示全部
            case 1 : $where['is_on_sale'] = 1; break;
            case 0 : $where['is_on_sale'] = 0; break;
            default :  break;
        }
        !empty($cat_id) && $where['cat_id1'] = $cat_id;
        !empty($key_word)&& $where['goods_name|goods_sn'] = ['like', '%' . $key_word . '%'];
        $goodsModel = new \app\common\model\Goods;
        $count = $goodsModel->where($where)->count();
        $Page = new AjaxPage($count,10);
        $goodsList = $goodsModel->where($where)->order('goods_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        if($goodsList){
            $data['goodsList'] = collection($goodsList)->hidden(['goods_content','mobile_content'])->toArray(); //隐藏没必要的输出的字段
            $data['warningStorage'] = M('store')->where(['store_id'=>STORE_ID])->getField('store_warning_storage');  //店铺库存预警
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' =>$data]);
        }
            $this->ajaxReturn(['status' => -1, 'msg' => '获取失败！']);
    }

    /**
     * 获取店铺的商品分类
     */
    public function getStoreGoodsCategory(){
        $parent_id = I('parent_id/d',0);
        $GoodsCategoryLogic = new GoodsCategoryLogic();
        $GoodsCategoryLogic->setStore($this->storeInfo);
        $data['goodsCategory'] = $GoodsCategoryLogic->getStoreGoodsCategory($parent_id); //获取店铺的商品分类
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' =>$data]);
    }

    /**
     * 商品类目管理(根据分类刷选商品)
     */
    public function categoryFiltrate(){
        $storeBindClass = new StoreBindClass();
        $data = $storeBindClass->with('goodsCategory')->withCount(['goods'=>function($query){$query->where(['store_id'=>STORE_ID]);}])->where(['store_id' => STORE_ID,'state'=>1])->select();
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' =>$data]);

    }

    /**
     * 发布商品选择商品类目
     */
    public function addStepOne(){
        //限制发布商品数量，0为不限制
        $alreadyPushNum = Db::name('goods')->where(['store_id' => STORE_ID])->count();
        $sgGoodsLimit = Db::name('store_grade')->where(['sg_id' => $this->storeInfo['grade_id']])->getField('sg_goods_limit');
        if($alreadyPushNum >= $sgGoodsLimit && $sgGoodsLimit > 0 && $this->storeInfo['is_own_shop'] !=1){
            $this->ajaxReturn(['status' => -1, 'msg' => '可发布商品数量已达到上限！']);
        }
        $GoodsCategoryLogic = new GoodsCategoryLogic();
        $GoodsCategoryLogic->setStore($this->storeInfo);
        $goodsCategoryLevelOne = $GoodsCategoryLogic->getStoreGoodsCategory();
        $data['goodsCategoryLevelOne']=$goodsCategoryLevelOne;
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' =>$data]);
    }

    /**
     * 添加修改商品
     */
    public function addEditGoods()
    {
        $goods_id = I('goods_id/d', 0);
        $Goods = new \app\common\model\Goods();
        $data = $Goods->where(['goods_id' => $goods_id, 'store_id' => STORE_ID])->find(); // 商品详情
        empty($data) && $this->ajaxReturn(['status' => -1, 'msg' => '非法操作']);
        $goods_cat = Db::name('goods_category')->where('id','IN',[$data['cat_id1'],$data['cat_id2'],$data['cat_id3']])->order('level asc')->getField('id,mobile_name');
        $data['goods_cat'] = implode('->',$goods_cat);// 分类
        $data['freight_template'] = Db::name('freight_template')->where(['store_id' => STORE_ID])->select(); //运费模板
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' =>$data]);
    }

    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function getGoodsSpec(){
         $GoodsLogic = new GoodsLogic();
         $goods_id = I('get.goods_id/d', 0);
         $spec_arr = I('spec_arr/a', []);
         $str = $GoodsLogic->getApiSpecInput($goods_id, $spec_arr, STORE_ID);
         $this->ajaxReturn(['status'=>1,'msg'=>'','result'=>$str]);
     }


    /**
     * 商品保存
     */
    public function save(){
        // 数据验证
        $data =input('post.');
        $goods_id = input('post.goods_id');
        $goods_cat_id3 = input('post.cat_id3');
        $spec_goods_item = input('post.item/a',[]);
        $store_count = input('post.store_count');
        $is_virtual = input('post.is_virtual');
        $virtual_indate = I('post.virtual_indate');//虚拟商品有效期
        $exchange_integral = I('post.exchange_integral');//虚拟商品有效期
        $validate = Loader::validate('Goods');
        $data['store_id'] = STORE_ID;
        if (!$validate->batch()->check($data)) {
            $error = $validate->getError();
            $error_msg = array_values($error);
            $return_arr = array(
                'status' => -1,
                'msg' => $error_msg[0],
                'data' => $error,
            );
            $this->ajaxReturn($return_arr);
        }
        $data['on_time'] = time(); // 上架时间

        $type_id = M('goods_category')->where("id", $goods_cat_id3)->getField('type_id'); // 找到这个分类对应的type_id
        $stores = M('store')->where(array('store_id' => STORE_ID))->getField('store_id , goods_examine,is_own_shop' , 1);
        $store_goods_examine = $stores[STORE_ID]['goods_examine'];
        if ($store_goods_examine) {
            $data['goods_state'] = 0; // 待审核
            $data['is_on_sale'] = 0; // 下架
        } else {
            $data['goods_state'] = 1; // 出售中
        }
        //总平台自营标识为2 , 第三方自营店标识为1
        $is_own_shop = (STORE_ID == 1) ? 2 : ($stores[STORE_ID]['is_own_shop']);
        $data['is_own_shop'] = $is_own_shop;
        $data['goods_type'] = $type_id ? $type_id : 0;
        $data['virtual_indate'] = !empty($virtual_indate) ? strtotime($virtual_indate) : 0;
        $data['exchange_integral'] = ($is_virtual == 1) ? 0 : $exchange_integral;
        if ($goods_id > 0) {
            $Goods = \app\common\model\Goods::get(['goods_id' => $goods_id, 'store_id' => STORE_ID]);
            if(empty($Goods)){
                $this->ajaxReturn(array('status' => 0, 'msg' => '非法操作','result'=>''));
            }
            if (empty($spec_goods_item) && $store_count != $Goods['store_count']) {
                $real_store_count = $store_count - $Goods['store_count'];
                update_stock_log(session('admin_id'), $real_store_count, array('goods_id' => $goods_id, 'goods_name' => $Goods['goods_name'], 'store_id' => STORE_ID));
            } else {
                unset($data['store_count']);
            }
            $Goods->data($data, true); // 收集数据
            $update = $Goods->save(); // 写入数据到数据库
            // 更新成功后删除缩略图
            if($update !== false){
                // 修改商品后购物车的商品价格也修改一下
                Db::name('cart')->where("goods_id", $goods_id)->where("spec_key = ''")->save(array(
                    'market_price' => $Goods['market_price'], //市场价
                    'goods_price' => $Goods['shop_price'], // 本店价
                    'member_goods_price' => $Goods['shop_price'], // 会员折扣价
                ));
                delFile("./public/upload/goods/thumb/$goods_id", true);
            }
        } else {
            $Goods = new \app\common\model\Goods();
            $Goods->data($data, true); // 收集数据
            $Goods->save(); // 新增数据到数据库
            $goods_id = $Goods->getLastInsID();
            //商品进出库记录日志
            if (empty($spec_goods_item)) {
                update_stock_log(session('admin_id'), $store_count, array('goods_id' => $goods_id, 'goods_name' => $Goods['goods_name'], 'store_id' => STORE_ID));
            }
        }
        $Goods->afterSave($goods_id, STORE_ID);
        $GoodsLogic = new GoodsLogic();
        $GoodsLogic->saveGoodsAttr($goods_id, $type_id); // 处理商品 属性
        $this->ajaxReturn([ 'status' => 1, 'msg' => '操作成功', 'result' => ['goods_id'=>$Goods->goods_id]]);
    }

    /**
     * 更改指定表的指定字段
     */
    public function updateField()
    {
        $ids = I('ids/a',[]);  //需要前台数组进来
        $goods_ids = implode(',',$ids);
        $field = I('field','');
        $value = I('value','');
        $field_arr = ['is_on_sale','is_recommend','is_new','is_hot'];
        if(!in_array($field,$field_arr)){
            $this->ajaxReturn(['status' => -1, 'msg' => '非法操作！']);
        }
        $res = Db::name('goods')->whereIn('goods_id',$goods_ids,'and')->where('store_id', STORE_ID)->save([$field => $value]);
        if($res === false){
            $this->ajaxReturn(['status' => -1, 'msg' => '修改失败！！']);
        }
        $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
    }

    /**
     * 删除商品
     */
    public function delGoods()
    {
        $ids_arr = I('ids/a', []);  //需要前台数组进来
        $goods_ids = implode(',', $ids_arr);
        $GoodsLogic = new GoodsLogic();
        $res = $GoodsLogic->delStoreGoods($goods_ids);
        $this->ajaxReturn($res);
    }

    /**
     * 运费模板列表
     */
    public function freightTemplate()
    {
        $goods_id = I('goods_id/d', 0);
        $freight_template = Db::name('freight_template')->where(['store_id' => STORE_ID])->select(); //运费模板
        $data = [
            $freight_template,  //运费模板
        ];
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' =>$data]);
    }
}