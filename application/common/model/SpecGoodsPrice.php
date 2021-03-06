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

class SpecGoodsPrice extends Model
{

    public function promGoods()
    {
        return $this->hasOne('PromGoods', 'id', 'prom_id')->cache(true,10);
    }

    public function goods()
    {
        return $this->hasOne('Goods', 'goods_id', 'goods_id')->cache(true,10);
    }


    public function getPromDescAttr($value, $data)
    {
        //1抢购2团购3优惠促销4预售5虚拟(5其实没用)6拼团
        switch($data['prom_type']) {
            case 1:
                $desc = '普通';
                break;
            case 2:
                $desc = '团购';
                break;
            case 3:
                $desc = '促销';
                break;
            case 4:
                $desc = '预售';
                break;
            case 6:
                $desc = '拼团';
                break;
            default:
                $desc = '普通';
        }
        return $desc;
    }
}
