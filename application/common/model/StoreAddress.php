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
class StoreAddress extends Model {
    //自定义初始化
    protected static function init()
    {
        //TODO:自定义的初始化
    }
    public function store()
    {
        return $this->hasOne('store','store_id','store_id');
    }

    public function getFullAddressAttr($value, $data)
    {
        $region = Db::name('region')->where('id', 'IN', [$data['province_id'], $data['city_id'], $data['district_id']])->column('name');
        return implode('', $region) . $data['address'];
    }
    public function getTypeDescAttr($value, $data)
    {
        if($data['type'] == 1){
            return '收货';
        }else{
            return '发货';
        }
    }
    public function getIsDefaultDescAttr($value, $data)
    {
        if($data['is_default'] == 1){
            return '是';
        }else{
            return '否';
        }
    }
}
