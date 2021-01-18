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

class Complain extends Model
{
    public function goods()
    {
        return $this->hasOne('goods','goods_id','order_goods_id');
    }

    public function getComplainStateAttr($value,$data){
        $complain_state = array(1=>'待处理',2=>'对话中',3=>'待仲裁',4=>'已完成');
        return $complain_state[$data['complain_state']];
    }

    public function getComplainPicAttr($value,$data){
       return  unserialize($data['complain_pic']);
    }
}
