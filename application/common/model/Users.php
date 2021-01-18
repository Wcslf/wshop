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
use app\common\logic\FlashSaleLogic;
use app\common\logic\GroupBuyLogic;

class Users extends Model
{
    //自定义初始化
    protected static function init()
    {
        //TODO:自定义的初始化
    }

    /**
     * 用户下线分销金额
     * @param $value
     * @param $data
     * @return float|int
     */
    public function getRebateMoneyAttr($value, $data){
      $sum_money = DB::name('rebate_log')->where(['status' => 3,'buy_user_id'=>$data['user_id']])->sum('money');
	   $rebate_money = empty($sum_money) ? (float)0 : $sum_money;
	   return  $rebate_money;
    }

}
