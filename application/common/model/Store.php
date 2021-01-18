<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 *
 * Date: 2019-03-1
 */
namespace app\common\model;
use think\Db;
use think\Model;
class Store extends Model {

    public function shippingAreas(){
        return $this->hasMany('ShippingArea','store_id','store_id');
    }
    public function carts()
    {
        return $this->hasMany('cart','store_id','store_id');
    }
    public function getAddressRegionAttr($value, $data){
        $regions = Db::name('region')->where('id', 'IN', [$data['province_id'], $data['city_id'], $data['district']])->order('level desc')->select();
        $address = '';
        if($regions){
            foreach($regions as $regionKey=>$regionVal){
                $address = $regionVal['name'] . $address;
            }
        }
        return $address;
    }

    /**
     * 获取店铺分类的评分
     * @param $value
     * @param $data
     * @return array|false|\PDOStatement|string|Model
     */
    public function getStoreClassStatisticsAttr($value, $data)
    {
        $comparison_where = array('sc_id' => $data['sc_id'], 'deleted' => 0);
        $field = "avg(store_desccredit) as store_desccredit_avg,avg(store_servicecredit) as store_servicecredit_avg,avg(store_deliverycredit) as store_deliverycredit_avg";
        $statistics = Db::name('store')->field($field)->where($comparison_where)->cache('true')->find();
        if($statistics && $statistics['store_desccredit_avg']>0 && $statistics['store_servicecredit_avg']>0 && $statistics['store_deliverycredit_avg']>0){
            $statistics['store_desccredit_match'] = ($data['store_desccredit'] - $statistics['store_desccredit_avg']) / $statistics['store_desccredit_avg'] * 100;
            $statistics['store_servicecredit_match'] = ($data['store_servicecredit'] - $statistics['store_servicecredit_avg']) / $statistics['store_servicecredit_avg'] * 100;
            $statistics['store_deliverycredit_match'] = ($data['store_deliverycredit'] - $statistics['store_deliverycredit_avg']) / $statistics['store_deliverycredit_avg'] * 100;
        }else{
            $statistics['store_desccredit_match'] = 100;
            $statistics['store_servicecredit_match'] = 100;
            $statistics['store_deliverycredit_match'] = 100;
        }
        return $statistics;
    }

    public function storeGoodsClass()
    {
        return $this->hasMany('StoreGoodsClass', 'store_id', 'store_id')->cache(true);
    }

    public function storeGoodsClassTopParent()
    {
        return $this->hasMany('StoreGoodsClass', 'store_id', 'store_id')->where(['parent_id' => 0])->cache(true);
    }

    public function province()
    {
        return $this->hasOne('region', 'id', 'province_id')->cache(true);
    }
    public function city()
    {
        return $this->hasOne('region', 'id', 'city_id')->cache(true);
    }
    public function district()
    {
        return $this->hasOne('region', 'id', 'district')->cache(true);
    }

    public function goods()
    {
        return $this->hasMany('goods','store_id','store_id');
    }

    /**
     * 获取店铺商品总数
     * @param $value
     * @param $data
     * @return int|string
     */
    public function getGoodsCountAttr($value, $data){
        return Db::name('goods')->where(['store_id'=>$data['store_id']])->count();
    }

    public function getTotalCreditAttr($value, $data)
    {
        return ($data['store_desccredit'] + $data['store_servicecredit'] + $data['store_servicecredit']) / 3;
    }
}
