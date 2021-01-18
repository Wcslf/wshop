<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 */

//1开启，0关闭
SaasBoot::init(0);

class SaasBoot
{
    protected $appDomain = '';  //应用主域名
    protected $miniappDomain = ''; //小程序域名
    protected $saasDomain = '';  //saas域名
    protected $hostDomain = '';  //请求主域名
    protected $userDomain = '';  //用户域名
    protected $userRight = null; //用户权限

    static public function init($saasEnable)
    {
        define('IS_SAAS', $saasEnable ? 1 : 0);
        if (!$saasEnable) {
            define('SAAS_BASE_USER', 0);
            return;
        }

        (new self)->initAll();
    }

    private function initAll()
    {
        $this->initAppConfig();
        $this->initUserDomain();
        $this->initUserRight();
        $this->initParams();
        $this->initHook();
    }

    private function initAppConfig()
    {
        $configPath = 'saas/_config.cfg';
        if (!is_file($configPath)) {
            $this->saasFailExit('主域名配置出错');
        }
        $configStr = file_get_contents($configPath);
        $config = json_decode($configStr, true);

        if (empty($config['main_domain'])) {
            $this->saasFailExit('主域名不存在');
        }
        if (empty($config['miniapp_domain'])) {
            $this->saasFailExit('小程序域名不存在');
        }
        if (empty($config['saas_domain'])) {
            $this->saasFailExit('saas域名不存在');
        }

        $GLOBALS['SAAS'] = $config;

        $this->saasDomain = $config['saas_domain'];
        $this->appDomain = $config['main_domain'];
        $this->miniappDomain = $config['miniapp_domain'];
        $this->hostDomain = strtolower($_SERVER['HTTP_HOST']);
    }

    private function initUserDomain()
    {
        if ($this->miniappDomain == $this->hostDomain) {
            $domain = $this->initUserDomainByQuery();
        } else {
            $domain = $this->initUserDomainByHost();
        }

        $this->userDomain = $domain;
    }

    private function initUserDomainByHost()
    {
        $host = $this->hostDomain;
        if (stripos(strrev($host), strrev($this->appDomain)) === 0) {
            //获取三级域名
            $hostArr = explode('.', $host);
            if (count($hostArr) !== 4) {
                $this->saasFailExit('网页不存在，域名异常');
            }
            $domain = $hostArr[0];
        } else {
            //进入个人域名配置获取
            //个人域名的配置文件路径
            $domainMapPath = 'saas/_domain_map.cfg';
            if (!is_file($domainMapPath)) {
                $this->saasFailExit('网页不存在，个人域名异常');
            }
            //获取映射的三级域名
            $dataStr = file_get_contents($domainMapPath);
            $data = json_decode($dataStr, true);
            if (empty($data[$host])) {
                $this->saasFailExit('网页不存在，个人域名不存在');
            }
            $domain = $data[$host];
        }

        return $domain;
    }

    // 参数解析法(apache .htaccess配置规则)，只用于小程序，共用https
    private function initUserDomainByQuery()
    {
        if (empty($_GET['_saas_app'])) {
            $this->saasFailExit('网页不存在，域名路径错误');
        }

        return $_GET['_saas_app'];
    }

    private function initUserRight()
    {
        //用户配置文件的路径
        $rightFilePath = 'saas/'.$this->userDomain.'.cfg';
        if (!is_file($rightFilePath)) {
            $this->saasFailExit('网页不存在，子域名不存在');
        }

        $rightStr= file_get_contents($rightFilePath);
        $right = json_decode($rightStr, true);
        if (!$right['is_base_app'] && (!isset($right['expires']) || $right['expires'] < time())) {
            $this->saasFailExit('应用已过期');
        }

        $GLOBALS['SAAS_CONFIG'] = $right;
        $this->userRight = $right;
    }

    private function initParams()
    {
        define('SAAS_BASE_USER', $this->userRight['is_base_app']);
        define('UPLOAD_PATH', 'public/upload/saas/'.$this->userDomain.'/');
        define('RUNTIME_PATH', __DIR__ . '/runtime/saas/'.$this->userDomain.'/');

        if (!file_exists(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0777, true);
        }
        if (!file_exists(RUNTIME_PATH)) {
            mkdir(RUNTIME_PATH, 0777, true);
        }
    }

    private function saasFailExit($msg)
    {
        header("Content-type: text/html; charset=utf-8");
        http_response_code(404);
        exit($msg);
    }

    private function initHook()
    {
        require 'thinkphp/library/think/Hook.php';
        \think\Hook::add('module_init', function () {
            \app\common\logic\Saas::instance()->initSaas();
        });
    }
}