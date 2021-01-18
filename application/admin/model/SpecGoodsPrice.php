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
namespace app\admin\model;

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
}
