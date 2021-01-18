<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * Author: 二月鸟飞      
 * Date: 2015-09-21
 */

namespace app\seller\controller;
use think\Db;
use think\Page;

class Shipping extends Base{

    public function index()
    {
        $shipping = new \app\common\model\Shipping();
        $shipping_list = $shipping->field('shipping_id,shipping_name')->where('')->order('shipping_id asc')->select();
        $store_shipping_ids = Db::name('store_shipping')->where(['store_id' => STORE_ID])->order('shipping_id asc')->column('shipping_id');
        $this->assign('shipping_list', $shipping_list);
        $this->assign('store_shipping_ids', $store_shipping_ids);
        return $this->fetch();
    }

    public function save()
    {
        $input_shipping_id_arr = input('shipping_id/a');
        if(count($input_shipping_id_arr) > 0){
            $shipping_id_arr = array_unique($input_shipping_id_arr);
            Db::name('store_shipping')->where(['store_id'=>STORE_ID])->delete();
            $shipping_all_data = [];
            $shipping_data['store_id'] = STORE_ID;
            foreach($shipping_id_arr as $shipping_id){
                $shipping_data['shipping_id'] = $shipping_id;
                array_push($shipping_all_data,$shipping_data);
            }
            Db::name('store_shipping')->insertAll($shipping_all_data);
            $this->ajaxReturn(['status'=>1,'msg'=>'保存成功!']);
        }else{
            $this->ajaxReturn(['status'=>0,'msg'=>'请至少选择一个快递公司!']);
        }
    }
}