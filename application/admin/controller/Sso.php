<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采有最新thinkphp5助手函数特性实现函数简写方式M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * @Author: lhb
 */

namespace app\admin\controller;

use think\Controller;
use app\common\logic\Saas;

class Sso extends Controller
{
    public function logout()
    {
        $ssoToken = input('sso_token', '');

        $return = Saas::instance()->ssoLogout($ssoToken);

        ajaxReturn($return);
    }
}