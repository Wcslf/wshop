<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 */
namespace app\seller\logic;

use think\Model;
use think\Db;
class ReturnGoodsLogic extends Model {

    /**
     * 获取店铺指定时间内退款信息
     * @param $store_id
     * @param $statustime
     * @param $endtime
     * @return mixed
     */
    public function getRefundAmount($store_id,$statustime='',$endtime=''){
        $where = ['store_id'=>$store_id,'type'=>['lt',2],'status'=>['egt',0]];
        if ($statustime && $endtime){
            $where['addtime'] =['between',[$statustime,$endtime]];
        }
        $data['refund_sum'] = Db::name('return_goods')->where($where)->count();
        $refund_money = Db::name('return_goods')->where($where)->sum('refund_money+refund_deposit');
        $refund_goods_num = Db::name('return_goods')->where($where)->sum('goods_num');
        $data['refund_money'] = !empty($refund_money) ? $refund_money :0;
        $data['refund_goods_num'] = !empty($refund_goods_num) ? $refund_goods_num :0;
        return $data;
    }
}
