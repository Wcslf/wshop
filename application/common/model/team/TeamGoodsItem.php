<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/29
 * Time: 10:02
 */

namespace app\common\model\team;


use think\Model;

class TeamGoodsItem extends Model
{
    public function specGoodsPrice()
    {
        return $this->hasOne('specGoodsPrice', 'item_id', 'item_id');
    }
    public function goods(){
        return $this->hasOne('app\common\model\Goods', 'goods_id', 'goods_id');
    }
    public function teamActivity(){
        return $this->hasOne('teamActivity', 'team_id', 'team_id');
    }
}