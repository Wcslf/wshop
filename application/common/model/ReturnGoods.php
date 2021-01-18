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

class ReturnGoods extends Model{
    /**
     * 售后类型
     * @param $value
     * @param $data
     */
    public function getServiceTypeAttr($value, $data)
    {
        $return_type =  C('RETURN_TYPE');
        return $return_type[$data['type']];
    }

    /**
     * 售后状态
     * @param $value
     * @param $data
     * @return string
     */
    public function getServiceStatusAttr($value, $data)
    {
        $return_status =  C('RETURN_STATUS');
        if($data['type']== 0 && $data['status']==3){
            return '待退款';
        }
        return $return_status[$data['status']];
    }
}