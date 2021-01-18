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
class FlashSale extends Model {
    //自定义初始化
    protected static function init()
    {
        //TODO:自定义的初始化
    }

    public function specGoodsPrice()
    {
        return $this->hasOne('SpecGoodsPrice','item_id','item_id');
    }

    public function goods()
    {
        return $this->hasOne('goods','prom_id','id');
    }
    //剩余抢购库存
    public function getStoreCountAttr($value, $data)
    {
        return $data['goods_num'] - $data['buy_num'];
    }

    //状态描述
    public function getStatusDescAttr($value, $data)
    {
        $status = array('审核中', '正常', '审核失败', '管理员关闭', '商品售馨');
        if($data['status'] != 1){
            return $status[$data['status']];
        }else{
            if(time() < $data['start_time']){
                return '未开始';
            }else if(time() > $data['start_time'] && time() < $data['end_time'] ){
                return '进行中';
            }else{
                return '已结束';
            }
        }
    }

    //状态描述
    public function getEditStatusAttr($value, $data)
    {
        if(time() <= $data['end_time'] && ($data['status'] == 2 || $data['status'] == 0)){
            return 1;
        }else if(time() >= $data['end_time'] && $data['status'] == 0) {
            return 1;
        }else {
            return 0;
        }
    }
}
