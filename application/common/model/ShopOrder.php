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

class ShopOrder extends Model
{
    //自定义初始化
    protected static function init()
    {
        //TODO:自定义的初始化
    }

    public function order()
    {
        return $this->hasOne('Order', 'order_id', 'order_id');
    }

    public function shop()
    {
        return $this->hasOne('Shop', 'shop_id', 'shop_id');
    }
}
