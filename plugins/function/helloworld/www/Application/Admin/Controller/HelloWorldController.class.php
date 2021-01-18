<?php
/**
 * tpshop HelloWorld 插件  demo 示例
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 *
 * Date: 2019-03-1
 */
namespace Admin\Controller;

// 这是一个demo 插件
class HelloWorldController extends BaseController {

    public function index(){        
        $hello = M('HelloWorld')->find();        
        $this->assign('hello',$hello);
        $this->display();
    }
}