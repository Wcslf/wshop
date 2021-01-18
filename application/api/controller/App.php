<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 */ 
namespace app\api\controller;

/**
 * Description of App
 *
 */
class App extends Base
{
    /**
     * 获取最新的app
     */
    public function latest()
    {
        $inVersion = input('get.version', '0');
        if ($inVersion === null || $inVersion === '') {
            $this->ajaxReturn(['status' => -1, 'msg' => 'app版本号无效']);
        }
        
        $app = tpCache('mobile_app');
        if (strnatcasecmp($app['android_app_version'], $inVersion) > 0) {
            $this->ajaxReturn(['status' => 1, 'msg' => '有新版本', 'result' => [
                'new' => 1,
                'url' => SITE_URL.'/'.$app['android_app_path'],
                'log' => $app['android_app_log']
            ]]);
        }
        
        $this->ajaxReturn(['status' => 1, 'msg' => '无新版本', 'result' => ['new' => 0]]);
    }
    
}
