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
class PromOrder extends Model {
    public function getPromDescAttr($value,$data)
    {
        $parse_type = array('0' => '满额打折', '1' => '满额优惠金额', '2' => '满额送积分', '3' => '满额送优惠券');
        return $parse_type[$data['type']];
    }
    //状态描述
    public function getStatusDescAttr($value, $data)
    {
        $status = array(0=>'管理员关闭', 1=>'正常');
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
}
