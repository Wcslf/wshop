<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * $Author: 二月鸟飞 2019-03-1 $
 */ 
namespace app\sellerApi\controller;
use think\Db;
use think\Controller;
use think\Session;

class Base extends Controller {
    public $http_url;
    public $seller = [];
    public $seller_id = 13;    //13
    public $storeInfo = [];
    public $token = '';
    /**
     * 析构函数
     */
    function __construct() {       
        parent::__construct();
        if ($_REQUEST['test'] == '1') {
            $test_str = 'POST'.print_r($_POST,true);
            $test_str .= 'GET'.print_r($_GET,true);
            file_put_contents('a.html', $test_str);            
        }
//        $this->checkToken(); // 检查token
//        $this->checkedStore();  //检查店铺
        $this->seller = Db::name('seller')->where(['seller_id' => $this->seller_id])->find();
        session('seller',$this->seller);
        $store = Db::name('store')->where(array('store_id' =>2))->find();
        $this->storeInfo = $store;
        define('STORE_ID',2);
        $unique_id = I("unique_id"); // 唯一id  类似于 pc 端的session id
        define('SESSION_ID',$unique_id); //将当前的session_id保存为常量，供其它方法调用                

    }    
    
    /*
     * 初始化操作
     */
    public function _initialize() {

        if(isset($_REQUEST["unique_id"])){           // 兼容手机app
            session_id($_REQUEST["unique_id"]);
            setcookie('token',$_REQUEST["token"]);
        }
        Session::start();
        
        //file_put_contents("/data/virtualhost/tp-shop.cn/test6/a.html", $c);
        
        $local_sign = $this->getSign();
        $api_secret_key = C('API_SECRET_KEY');
        
     //    if('www.tp-shop.cn' == $api_secret_key)
     //           exit(json_encode(array('status'=>-1,'msg'=>'请到后台修改php文件Application/Api/Conf/config.php 文件内的秘钥','data'=>'' )));
            
        // 不参与签名验证的方法
        //@modify by wangqh. add notify
        $arr = ['getservertime','group_list','getconfig','alipaynotify', 'notify', 'goodsthumimages','login','favourite','homepage'];
        if(!in_array(strtolower(ACTION_NAME), $arr))
        {        
            if($local_sign != $_POST['sign'])
            {    
                $json_arr = array('status'=>-1,'msg'=>'签名失败!!!','result'=>'' );
                //exit(json_encode($json_arr));

            }
            if(time() - $_POST['time'] > 600)
            {    
                $json_arr = array('status'=>-1,'msg'=>'请求超时!!!','result'=>'' );
                //exit(json_encode($json_arr));
            }
        }       
    }
    
    /**
     *  app 端万能接口 传递 sql 语句 sql 错误 或者查询 错误 result 都为 false 否则 返回 查询结果 或者影响行数
     */
    public function sqlApi()
    {            
        exit(json_encode(array('status'=>-1,'msg'=>'使用万能接口必须开启签名验证才安全','result'=>''))); //  开启后注释掉这行代码即可
        
        C('SHOW_ERROR_MSG',1);
            $sql = $_REQUEST['sql'];
            try
            {
                 if(preg_match("/insert|update|delete/i", $sql))            
                     $result = Db::execute($sql);
                 else             
                     $result =  Db::query($sql);
             }
             catch (\Exception $e)
             {
                 $json_arr = array('status'=>-1,'msg'=>'系统错误','result'=>'');
                 $json_str = json_encode($json_arr);            
                 exit($json_str);            
             }            
                         
            if($result === false) // 数据非法或者sql语句错误            
                $json_arr = array('status'=>-1,'msg'=>'系统错误','result'=>'');
            else
                $json_arr = array('status'=>1,'msg'=>'成功!','result'=>$result);
                                   
            $json_str = json_encode($json_arr);            
            exit($json_str);            
    }

    /**
     * app端请求签名
     * @return type
     */
    protected function getSign(){
        header("Content-type:text/html;charset=utf-8");
        $data = $_POST;        
        unset($data['time']);    // 删除这两个参数再来进行排序     
        unset($data['sign']);    // 删除这两个参数再来进行排序
        ksort($data);
        $str = implode('', $data);        
        $str = $str.$_POST['time'].C('API_SECRET_KEY');        
        return md5($str);
    }
        
    /**
     * 获取服务器时间
     */
    public function getServerTime()
    {
        $json_arr = array('status'=>1,'msg'=>'成功!','result'=>time());
        $json_str = json_encode($json_arr);
        exit($json_str);       
    }
    
    /**
     * 校验token
     */
    public function checkToken()
    {
        $this->token = I("token",''); // token
        if (empty($this->token)) {
            $this->token = $_COOKIE['token'];
        }

        // 判断哪些控制器的 哪些方法需要登录验证的
        $check_arr = [
        ];
        
        // 保留状态的检查组
        $check_session_arr = [
            'cart' => ['cartlist','addcart'],
            'user' => ['getGoodsCollect']
        ];

        $controller_name = strtolower(CONTROLLER_NAME);
        $action_name = strtolower(ACTION_NAME);
        if(in_array($controller_name,array_keys($check_arr)) && in_array($action_name, array_map('strtolower',$check_arr[$controller_name])))
        {
            $return = $this->getUserByToken($this->token);
            if ($return['status'] != 1) {
                $this->ajaxReturn($return);
            }
            $this->seller = $return['result'];
            $this->seller_id = $this->seller['seller_id'];
             // 更新最后一次操作时间 如果用户一直操作 则一直不超时
            Db::name('seller')->where("seller_id",$this->seller_id)->save(array('last_login_time'=>time()));
            
        } elseif (in_array($controller_name, array_keys($check_session_arr)) && in_array($action_name, $check_session_arr[$controller_name])) {
            if ($this->token) {
                $this->seller = Db::name('seller')->where("token",$this->token)->find();
            }
            !$this->seller && $this->seller = session('seller');
            $this->seller && $this->seller_id = $this->seller['seller_id'];
            
        } else {
            if(empty($this->token))$this->token='';
            $this->seller = Db::name('seller')->where("token", $this->token)->find();
            $this->seller && ($this->seller_id = $this->seller['seller_id']);
        }
        session('seller', $this->seller);
    }
    
    protected function getUserByToken($token)
    {
        if (empty($token)) {
            return ['status'=>-100, 'msg'=>'请先登录[无token]'];
        }

        $seller = Db::name('seller')->where("token", $token)->find();
        if (empty($seller)) {
            return ['status'=>-101, 'msg'=>'请登录'];
        }
        
        // 登录超过72分钟 则为登录超时 需要重新登录.  //这个时间可以自己设置 可以设置为 20分钟
        if(time() - $seller['last_login_time'] > C('APP_TOKEN_TIME')) {  //3600
            return ['status'=>-102, 'msg'=>'登录超时,请重新登录!', 'result'=>(time() - $seller['last_login_time'])];
        }
        
        return ['status' => 1, 'msg' => '获取成功', 'result' => $seller];
    }

    /**
     * 校验店铺
     * @return array
     */
    public function checkedStore()
    {
        //过滤不需要登陆的行为
        if ($this->seller_id > 0) {
            define('STORE_ID', $this->seller['store_id']); //将当前的store_id保存为常量，供其它方法调用
            $store = Db::name('store')->where(array('store_id' => STORE_ID))->find();
            if($store['store_state'] == 0 && CONTROLLER_NAME != 'Index'){
                $this->ajaxReturn(['status' => -1, 'msg' => '店铺已关闭']);
            }
//            $this->check_priv();//检查管理员菜单操作权限
            $store['full_address'] = getRegionName($store['province_id']) . ' ' . getRegionName($store['city_id']) . ' ' . getRegionName($store['district']);
            $store['grade_name'] = Db::name('store_grade')->where(['sg_id'=>$store['grade_id'],])->getField('sg_name');
            $this->storeInfo = $store;
        } else {
            $this->ajaxReturn(['status' => -1, 'msg' => '请先登录']);
        }
    }

    public function check_priv()
    {
        $seller = session('seller');
        if ($seller['is_admin'] == 0) {
            $ctl = request()->controller();
            $act = request()->action();
            $act_list = $seller['act_limits'];
            //无需验证的操作
            $uneed_check = array('login', 'logout', 'imageUp', 'upload', 'login_task', 'modify_pwd','index');//修改密码不需要验证权限
            if ($ctl == 'Index' || $act_list == 'all') {
                //后台首页控制器无需验证,超级管理员无需验证
                return true;
            }elseif(request()->isAjax() || strpos($act,'ajax')!== false || in_array($act,$uneed_check)){
                //所有ajax请求不需要验证权限
                return true;
            } else {
                $right = Db::name('system_menu')->where("id", "in", $act_list)->cache(true)->getField('right', true);
                $role_right = '';
                if (count($right) > 0) {
                    foreach ($right as $val) {
                        $role_right .= $val . ',';
                    }
                }
                $role_right = explode(',', $role_right);
                //检查是否拥有此操作权限
                if (!in_array($ctl.'@'.$act, $role_right)) {
                    $this->ajaxReturn(['status' => -1, 'msg' => '您没有操作权限'.($ctl.'@'.$act).',请联店铺超级管理员分配权限']);
                }
            }
        }
        return true;
    }

    public function ajaxReturn($data){
        exit(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}