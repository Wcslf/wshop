<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * Author: lhb
 * Date: 2017-05-15
 */

namespace app\common\logic;

use think\Model;

/**
 * 活动逻辑类
 */
class ActivityLogic extends Model
{


    /**
     * 获取优惠券查询对象
     * @param int $queryType 0:count 1:select
     * @param type $user_id
     * @param int $type 查询类型 0:未使用，1:已使用，2:已过期
     * @param null $orderBy 排序类型，use_end_time、send_time,默认send_time
     * @param int  $belone 0:具体商家，1:自营, 2:所有商家
     * @param int $store_id
     * @param int $order_money
     * @return mixed
     */
    private function getCouponQuery($queryType, $user_id, $type = 0, $orderBy = null, $belone = 0, $store_id = 0, $order_money = 0)
    {
        $where['l.uid'] = $user_id;
        $where['l.deleted'] = 0;
        $where['c.status'] = 1;

        //查询条件
        if (empty($type)) {
            // 未使用
            $where['c.use_end_time'] = array('gt', time());
            $where['c.status'] = 1;
            $where['l.status'] = 0;
        } elseif ($type == 1) {
            //已使用
            $where['l.order_id'] = array('gt', 0);
            $where['l.use_time'] = array('gt', 0);
            $where['l.status'] = 1;
        } elseif ($type == 2) {
            //已过期
            $where['c.use_end_time'] = array('lt', time());
            $where['l.status'] = array('neq', 1);
        }
        if ($orderBy == 'use_end_time') {
            //即将过期
            $order['c.use_end_time'] = 'asc';
        } elseif ($orderBy == 'send_time') {
            //最近到账
            $where['l.send_time'] = array('lt',time());
            $order['l.send_time'] = 'desc';
        } elseif (empty($orderBy)) {
            $order = array('l.send_time' => 'DESC', 'l.use_time');
        }
        
        $condition = floatval($order_money) ? ' AND c.condition <= '.$order_money : '';
        $query = M('coupon_list')->alias('l')
            ->join('__COUPON__ c','l.cid = c.id'.$condition)
            ->join('__STORE__ s','s.store_id = c.store_id')
            ->where($where)
            ->where(function($query) use ($belone, $store_id) {
                if ($belone == 1) {   //自营
                    $query->where("s.is_own_shop",1)->whereOr('s.store_id = 1');
                } elseif ($belone == 2) { //商家
                    $query->where("s.is_own_shop",0);
                } elseif ($store_id) {
                    $query->where("l.store_id", $store_id);
                }
            });
        
        if ($queryType != 0) {
            $query = $query->field('l.*,c.name,c.use_type,c.money,c.use_start_time,c.use_end_time,c.condition')
                    ->order($order);
        }

        return $query;
    }
    
    /**
     * 获取优惠券数目
     */
    public function getUserCouponNum($user_id, $type = 0, $orderBy = null, $belone = 0, $store_id = 0, $order_money = 0)
    {
        $query = $this->getCouponQuery(0, $user_id, $type, $orderBy, $belone, $store_id, $order_money);
        return $query->count();
    }
    
    /**
     * 获取用户优惠券列表
     */
    public function getUserCouponList($firstRow, $listRows, $user_id, $type = 0, $orderBy = null, $belone = 0, $store_id = 0, $order_money = 0)
    {
        $query = $this->getCouponQuery(1, $user_id, $type, $orderBy, $belone, $store_id, $order_money);
        return $query->limit($firstRow, $listRows)->select();
    }

}