<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/30
 * Time: 14:12
 */
namespace app\sellerApi\controller;

use app\seller\logic\AdminLogic;
use think\Db;

class Admin extends Base
{
    /**
     * 析构流函数
     */
    public function  __construct() {
        parent::__construct();
        $this->AdminLogic = new AdminLogic();
    }

    public function index(){
    }
    /*
     * 管理员登陆
     */
    public function login()
    {
        if ($this->seller_id) {
            $this->ajaxReturn(['status' => -1, 'msg' => '您已登录！']);
        }
        if (IS_POST) {
            $seller_name = trim(I('post.username'));
            $password = trim(I('post.password'));
            $result=$this->AdminLogic->sellerApiLogin($seller_name,$password);
            if($result['status']==1){  //登录成功后要做些操作
                define('STORE_ID', $result['data']['store_id']);
//                $this->AdminLogic->login_task();  //商家登录后 处理相关操作
                $this->ajaxReturn(['status' => 1, 'msg' => '登录成功']);
                exit;
            }
            $this->ajaxReturn($result);
        }else{
            $this->ajaxReturn(['status' => 0, 'msg' => '请求方式错误！']);
        }
    }

    /**
     * 退出登陆
     */
    public function logout()
    {
        Db::name('seller')->where(array('seller_id' => $this->seller_id))->save(['token'=>'']);
        define('STORE_ID', '');
        $this->seller = [];
        $this->seller_id=0;
        $this->ajaxReturn(['status' => 1, 'msg' => '退出成功！']);
    }
}