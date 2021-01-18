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
use think\Model;
class GroupBuy extends Model {
    public function goods(){
        return $this->hasOne('goods','goods_id','goods_id');
    }
    public function specGoodsPrice(){
        return $this->hasOne('specGoodsPrice','item_id','item_id');
    }
    //剩余团购库存
    public function getStoreCountAttr($value, $data)
    {
        return $data['goods_num'] - $data['buy_num'];
    }
    //状态描述
    public function getStatusDescAttr($value, $data)
    {
        $status = array('审核中', '正常', '审核失败', '管理员关闭');
        if ($data['status'] != 1) {
            return $status[$data['status']];
        } else {
            if (time() < $data['start_time']) {
                return '未开始';
            } else if (time() > $data['start_time'] && time() < $data['end_time']) {
                return '进行中';
            } else {
                return '已结束';
            }
        }
    }
}
