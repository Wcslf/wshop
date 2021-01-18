<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: yhj
 * Date: 2018-6-27
 */
namespace app\common\model;

use think\Db;
use think\Model;

class UserMessage extends Model
{
    public function messageActivity()
    {
        return $this->hasOne('messageActivity', 'message_id', 'message_id');
    }
    public function messageLogistics()
    {
        return $this->hasOne('messageLogistics', 'message_id', 'message_id');
    }
    public function messageNotice()
    {
        return $this->hasOne('messageNotice', 'message_id', 'message_id');
    }
    public function messagePrivate()
    {
        return $this->hasOne('messagePrivate', 'message_id', 'message_id');
    }           
}
