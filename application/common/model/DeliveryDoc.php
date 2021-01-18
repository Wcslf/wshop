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
class DeliveryDoc extends Model {

    protected $table='';

    //自定义初始化
    protected function initialize()
    {
        parent::initialize();
        $select_year = select_year(); // 查询 三个月,今年内,2016年等....订单
        $prefix = C('database.prefix');  //获取表前缀
        $this->table = $prefix.'delivery_doc'.$select_year;
    }

    public function seller()
    {
        return $this->hasOne('seller', 'seller_id', 'admin_id');
    }

    public function getFullAddressAttr($value, $data)
    {
        $region = Db::name('region')->where('id', 'IN', [$data['store_address_province_id'], $data['store_address_city_id'], $data['store_address_district_id']])->column('name');
        return implode('', $region) . $data['store_address'];
    }
}
