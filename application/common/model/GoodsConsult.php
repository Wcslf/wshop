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
use think\Db;
class GoodsConsult extends Model {
    public function getReplyListAttr($value,$data)
    {
        return Db::name('GoodsConsult')->where(['parent_id' => $data['id'],'is_show'=>1])->order('add_time desc')->select();
    }
}
