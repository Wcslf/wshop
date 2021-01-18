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

class StoreReopen extends Model
{
    public function getReopenStateAttr($value, $data){
        switch ($data['re_state']){
            case -1 : return '审核失败'; break;
            case 0  : return '未上传凭证'; break;
            case 1  : return '审核中'; break;
            case 2  : return '审核通过'; break;
        }
    }
}
