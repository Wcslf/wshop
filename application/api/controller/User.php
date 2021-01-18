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
namespace app\api\controller;


use app\common\model\Users;
use app\common\util\TpshopException;
use think\Db;
use app\common\logic\CartLogic;
use app\common\logic\OrderLogic;
use app\common\logic\UsersLogic;
use app\common\logic\CommentLogic;
use app\common\logic\CouponLogic;
use app\common\logic\Saas;
use think\Page;

class User extends Base {
    public $userLogic;
    
    /**
     * 析构流函数
     */
    public function  __construct() {   
        parent::__construct();

        $action = strtolower(ACTION_NAME);
        if (in_array($action, ['login', 'thirdlogin', 'reg'])) {
            $terminal = input('terminal', '');
            Saas::instance()->checkApiRight($terminal);
        }

        $this->userLogic = new UsersLogic();
    } 

    /**
     *  登录
     */
    public function login()
    {
        $username = I('username', '');
        $password = I('password', '');
        $capache = I('capache', '');
        $unique_id = I("unique_id"); // 唯一id  类似于 pc 端的session id
        $push_id = I('push_id', '');

        $data = $this->userLogic->app_login($username, $password, $capache, $push_id);
        if($data['status'] != 1){
            $this->ajaxReturn($data);
        }
        
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($data['result']['user_id']);
        $cartLogic->setUniqueId($unique_id);
        $cartLogic->doUserLoginHandle();  // 用户登录后 需要对购物车 一些操作
        $this->ajaxReturn($data);
    }
    
    /**
     * 登出
     */
    public function logout()
    {
        $token = I("post.token", ''); 
        $data = $this->userLogic->app_logout($token);
        $this->ajaxReturn($data);
    }
    
    /**
     * 第三方登录
     */
    public function thirdLogin()
    {
        $unique_id = I("unique_id"); // 唯一id  类似于 pc 端的session id
        $map['openid'] = I('openid','');
        $map['nickname'] = I('nickname','');
        $map['head_pic'] = I('head_pic','');        
        $map['unionid'] = I('unionid','');
        $map['push_id'] = I('push_id','');
        $map['sex'] = I('sex', 0);
        $map['oauth'] = I('oauth','');
        $map['versionCode'] = I('versionCode','');
		//小程序昵称过滤特殊字符
        $map['nickname'] && $map['nickname'] = replaceSpecialStr($map['nickname']);
          
        if ($map['oauth'] == 'miniapp') {
            $code = I('post.code', '');
 
            $iv = I('post.iv', '');
            $signature = I('post.signature', '');
            $encryptedData = I('post.encryptedData', '');
            if (!$code) {
                $this->ajaxReturn(['status' => -1, 'msg' => 'code值非空','result'=>'']);
            }
	        $miniapp = new \app\common\logic\wechat\MiniAppUtil;
	        //M: 4月30日起获取用户信息接口调整同时兼容旧版小程序登录流程 @{
            $tagCode = "1.0.1";//固定值, 旧版没有versionCode参数
            $result = compareVersion($map['versionCode'] , $tagCode );
            if($result === 0 || $result == -1){
                //就版接口:wx.getUserInfo
                $session = $miniapp->getSessionInfo($code);
                if ($session === false) $this->ajaxReturn(['status' => -1, 'msg' => $miniapp->getError()]);
            }else{
                //新版接口:wx.getUserInfo, 小程序端需要传versionCode
                 try{
                    $session = array();
                    $errCode = $miniapp->getWxUserInfo($code , $iv , $encryptedData , $session);
                    $session = json_decode( $session , true );
                }catch (TpshopException $t){
                    $error = $t->getErrorArr();
                    $this->ajaxReturn($error);
                }
            }
            //@} 
            $map['openid'] = $session['openid'] ? $session['openid'] : $session['openId'] ;
            $map['unionid'] = $session['unionid'] ? $session['unionid']: $session['unionId'];
 
        }
        
        $is_bind_account = tpCache('basic.is_bind_account');
        if ($is_bind_account == 1) {
            $data = $this->userLogic->thirdLogin_new($map);
            if ($data['status'] != 1) {
                if ($data['result'] === '100') {
                    session("third_oauth" , $map);
                }
                $this->ajaxReturn($data);
            }
        } else {
            $data = $this->userLogic->thirdLogin($map);
        }
            
        if($data['status'] == 1){
            $cartLogic = new CartLogic();
            $cartLogic->setUserId($data['result']['user_id']);
            $cartLogic->setUniqueId($unique_id);
            $cartLogic->doUserLoginHandle();// 用户登录后 需要对购物车 一些操作
            //重新获取用户信息，补全数据
            $data = $this->userLogic->getApiUserInfo($data['result']['user_id']);
        }
        $this->ajaxReturn($data);
    }

    /**
     * 用户注册
     */
    public function reg(){
        $nickname = I('post.nickname','');
        $username = I('post.username','');
        $password = I('post.password','');
        $code = I('post.code');        
        $type = I('type','phone');
        $session_id = I('unique_id', session_id());// 唯一id  类似于 pc 端的session id
        $scene = I('scene' , 1);
        $push_id = I('post.push_id' , '');

        $reg_sms_enable = tpCache('sms.regis_sms_enable');
        //是否开启注册验证码机制
        if(check_mobile($username)){
            if ($reg_sms_enable) {
                $res = $this->userLogic->check_validate_code($code, $username  , $type , $session_id , $scene);
                if($res['status'] != 1) exit(json_encode($res));
            }
        }

        $is_bind_account = tpCache('basic.is_bind_account');
        $wxuser = session('third_oauth');
        if($is_bind_account && $wxuser){
            $head_pic = $wxuser['head_pic'];
            $nickname = $nickname ?: $wxuser['nickname'];
            $data = $this->userLogic->reg($username,$password , $password, $push_id,$nickname,$head_pic);
            if($data['status'] == -1)$this->ajaxReturn($data);
            $data = $this->userLogic->oauth_bind_new($data['result']);
        }else{
            $data = $this->userLogic->reg($username,$password ,$password, $push_id);
        }
        
        if($data['status'] == 1){
            $cartLogic = new CartLogic();
            $cartLogic->setUserId($data['result']['user_id']);
            $cartLogic->setUniqueId($session_id);
            $cartLogic->doUserLoginHandle(); // 用户登录后 需要对购物车 一些操作
        }        
        exit(json_encode($data));
    }

    /**
     * 绑定已有账号
     * @return \think\mixed
     */
    public function bind_account()
    {
        $mobile = input('mobile/s');
        $verify_code = input('verify_code/s');
        //发送短信验证码
        $logic = new UsersLogic();
        $check_code = $logic->check_validate_code($verify_code, $mobile, 'phone', session_id(), 1);
        if ($check_code['status'] != 1) {
            $this->ajaxReturn(['status' => 0, 'msg' => $check_code['msg'], 'result' => '']);
        }
        if (empty($mobile) || !check_mobile($mobile)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '手机格式错误']);
        }
        $users = Users::get(['mobile'=>$mobile]);
        if (empty($users)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '账号不存在']);
        }
        $user = new \app\common\logic\User();
        $user->setUser($users);
        $cartLogic = new CartLogic();
        try {
            $user->checkOauthBind();
            $user->oauthBind();
            $user->doLeader();
            $user->refreshCookie();
            $cartLogic->setUserId($users['user_id']);
            $cartLogic->doUserLoginHandle();
            $orderLogic = new OrderLogic();//登录后将超时未支付订单给取消掉
            $orderLogic->setUserId($users['user_id']);
            $orderLogic->abolishOrder();
            $this->ajaxReturn(['status' => 1, 'msg' => '绑定成功', 'result' => ['user' => $user->getUser()]]);
        } catch (TpshopException $t) {
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }
    }

    /**
     * 先注册再绑定账号
     * @return \think\mixed
     */
    public function bind_reg()
    {
        $mobile = input('mobile/s');
        $verify_code = input('verify_code/s');
        $password = input('password/s');
        $nickname = input('nickname/s', '');
        if (empty($mobile) || !check_mobile($mobile)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '手机格式错误']);
        }
        if (empty($password)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请输入密码']);
        }
        $logic = new UsersLogic();
        $check_code = $logic->check_validate_code($verify_code, $mobile, 'phone', session_id(), 1);
        if ($check_code['status'] != 1) {
            $this->ajaxReturn(['status' => 0, 'msg' => $check_code['msg'], 'result' => '']);
        }
        $thirdUser = session('third_oauth');
        $data = $logic->reg($mobile, $password, $password, 0, $nickname, $thirdUser['head_pic']);
        if ($data['status'] != 1) {
            $this->ajaxReturn(['status' => 0, 'msg' => $data['msg'], 'result' => '']);
        }
        $user = new \app\common\logic\User();
        $user->setUserById($data['result']['user_id']);
        try {
            $user->checkOauthBind();
            $user->oauthBind();
            $user->refreshCookie();
            $this->ajaxReturn(['status' => 1, 'msg' => '绑定成功', 'result' => ['user' => $user->getUser()]]);
        } catch (TpshopException $t) {
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }
    }

    /*
     * 获取用户信息
     */
    public function userInfo(){
        //$user_id = I('user_id/d');
        $data = $this->userLogic->getApiUserInfo($this->user_id);
        exit(json_encode($data));
    }
     
    /*
     *更新用户信息
     */
    public function updateUserInfo()
    {
        if (!IS_POST) {
            $this->ajaxReturn(['status'=>-1, 'msg'=>"请求方式错误"]);
        }
        if (!$this->user_id) {
            $this->ajaxReturn(['status'=>-1,'msg'=>'缺少参数','result'=>'']);
        }

        I('post.nickname') ? $post['nickname'] = I('post.nickname') : false; //昵称
        I('post.qq') ? $post['qq'] = I('post.qq') : false;  //QQ号码
        I('post.head_pic') ? $post['head_pic'] = I('post.head_pic') : false; //头像地址
        I('post.sex') ? $post['sex'] = I('post.sex') : false;  // 性别
        I('post.birthday') ? $post['birthday'] = strtotime(I('post.birthday')) : false;  // 生日
        I('post.province') ? $post['province'] = I('post.province') : false;  //省份
        I('post.city') ? $post['city'] = I('post.city') : false;  // 城市
        I('post.district') ? $post['district'] = I('post.district') : false;  //地区
        I('post.email') ? $post['email'] = I('post.email') : false;  
        I('post.mobile') ? $post['mobile'] = I('post.mobile') : false;  

        $email = $post['email'];
        $mobile = $post['mobile'];
        $code = I('post.mobile_code', '');
        $scene = I('post.scene', 6);

        if (!empty($email)) {
            $c = M('users')->where(['email' => $email, 'user_id' => ['<>', $this->user_id]])->find();
            $c && $this->ajaxReturn(['status'=>-1,'msg'=>"邮箱已被使用"]);
        }
        if (!empty($mobile)) {
            $c = M('users')->where(['mobile' => $mobile, 'user_id' => ['<>', $this->user_id]])->count();
            $c && $this->ajaxReturn(['status'=>-1,'msg'=>"手机已被使用"]);
           // (!$code) && $this->ajaxReturn(['status'=>-1,'msg'=>'请输入验证码']);
            /*$check_code = $this->userLogic->check_validate_code($code, $mobile, 'mobile', SESSION_ID, $scene);
            if ($check_code['status'] != 1) {
                $this->ajaxReturn($check_code);
            }*/
        }

        if (!$this->userLogic->update_info($this->user_id,$post)) {
            $this->ajaxReturn(['status'=>-1,'msg'=>'更新失败','result'=>'']);
        }
        $this->ajaxReturn(['status'=>1,'msg'=>'更新成功','result'=>'']);
    }

    /*
     * 修改用户密码
     */
    public function password(){
        if(IS_POST){
            if(!$this->user_id){
                exit(json_encode(array('status'=>-1,'msg'=>'缺少参数','result'=>'')));
            }
            $data = $this->userLogic->passwordForApp($this->user_id,I('post.old_password'),I('post.new_password')); // 修改密码
            exit(json_encode($data));
        }
    }
    
    public function forgetPasswordInfo()
    {
        $account = I('post.account', '');
        $capache = I('post.capache' , '');
        if (!capache([], SESSION_ID, $capache)) {
            $this->ajaxReturn(['status'=>-1,'msg'=>'验证码错误！']);
        }
        if (($user = M('users')->field('mobile, nickname')->where(['mobile' => $account])->find()) 
            || ($user = M('users')->field('mobile, nickname')->where(['email' => $account])->find())
            || ($user = M('users')->field('mobile, nickname')->where(['nickname' => $account])->find())) {
            $this->ajaxReturn(['status'=>1, 'msg'=>'获取成功', 'result' => $user]);
        }
        if (!$user) {
            $this->ajaxReturn(['status'=>-1, 'msg'=>'该账户不存在']);
        }
    }
    
    /**
     * 短信验证
     */
    public function check_sms()
    {
        $mobile = I('post.mobile');
        $unique_id = I('unique_id');
        $code = I('post.check_code');   //验证码
        $scene = I('post.scene/d', 2);   //验证码
        if (!check_mobile($mobile)) {
            $this->ajaxReturn(['status'=>-1,'msg'=>'手机号码格式不正确','result'=>'']);
        }

        $res = $this->userLogic->check_validate_code($code, $mobile, 'phone', $unique_id , $scene);
        if ($res['status'] != 1) {
            $this->ajaxReturn($res);
        }
       
        $this->ajaxReturn(['status'=>1, 'msg'=>'验证成功']);
    }
    
    /**
     * 修改手机验证
     */
    public function change_mobile()
    {
        $mobile = I('post.mobile');
        $unique_id = I('unique_id');
        $code = I('post.check_code');   //验证码
        $scene = I('post.scene/d', 0);   //验证码
        $capache = I('post.capache' , '');
        if (!check_mobile($mobile)) {
            $this->ajaxReturn(['status'=>-1,'msg'=>'手机号码格式不正确','result'=>'']);
        }
        $isHaveMobile = Db::name('users')->field('user_id')->where(['user_id' => ['<>', $this->user_id], 'mobile' => $mobile])->find();
        if ($isHaveMobile) {
            $this->ajaxReturn(['status' => -1, 'msg' => '该手机号已被绑定']);
        }

        $res = $this->userLogic->check_validate_code($code, $mobile, 'phone', $unique_id , $scene);
        if ($res['status'] != 1) {
            $this->ajaxReturn($res);
        }

        /* if (!capache([], SESSION_ID, $capache)) {
            $this->ajaxReturn(['status'=>-1,'msg'=>'图形验证码错误！']);
        } */
        
        if ($scene != 6) {
            $this->ajaxReturn(['status'=>-1,'msg'=>'场景码错误！']);
        }
        
        $data['mobile'] = $mobile;  
        if (!$this->userLogic->update_info($this->user_id, $data)) {
           $this->ajaxReturn(['status' => -1, 'msg' => '手机号码更新失败']);
        }

        $this->ajaxReturn(['status'=>1, 'msg'=>'更改成功']);
    }
    
    /**
     * @add by wangqh APP端忘记密码
     * 忘记密码
     */
    public function forgetPassword()
    {
        $password = I('password');
        $mobile = I('mobile', 'invalid');
       
        
        $user = M('users')->where("mobile",$mobile)->find();
        if (!$user) {
            $this->ajaxReturn(['status'=>-1,'msg'=>'该手机号码没有关联账户']);
        } else {
            //修改密码
            M('users')->where("user_id",$user['user_id'])->save(array('password'=>$password));
            $this->ajaxReturn(['status'=>1,'msg'=>'密码已重置,请重新登录']);
        }
    }

    /**
     * 获取收货地址
     */
    public function getAddressList()
    {
        if (!$this->user_id) {
            $this->ajaxReturn(array('status'=>-1,'msg'=>'缺少参数'));
        }
        
        $address = M('user_address')->where(array('user_id'=>$this->user_id))->select();
        if(!$address) {
            $this->ajaxReturn(array('status'=>1,'msg'=>'没有数据','result'=>[]));
        }

        $regions = M('region')->cache(true)->getField('id,name');
        foreach ($address as &$addr) {
            $addr['province_name'] = $regions[$addr['province']] ?: '';
            $addr['city_name']     = $regions[$addr['city']] ?: '';
            $addr['district_name'] = $regions[$addr['district']] ?: '';
            $addr['twon_name']     = $regions[$addr['twon']] ?: '';
            $addr['address']       = $addr['address'] ?: '';
        }
        
        $this->ajaxReturn(array('status'=>1,'msg'=>'获取成功','result'=>$address));
    }
     
    /**
     * 订单列表: 临时添加订单列表, APP小程序审核过了删除此方法(已移到Order控制器)
     */
    public function getOrderList()
    {
        $type = I('type','');
        if (!$this->user_id) {
            $this->ajaxReturn(['status'=>-1, 'msg'=>'缺少参数', 'result'=>'']);
        }
        //删除的订单, 关闭订单 ,虚拟订单 不列出来
        $where_arr = [
            'user_id'=>$this->user_id,
            'deleted'=>0,
            'prom_type'=>['lt',5],
        ];
        $where_str = '1=1';
        if($type){
            $where_str .= C(strtoupper($type));
        }
        $orderModel = new \app\common\model\Order();
        $count = $orderModel->where($where_arr)->where($where_str)->count();
        $Page = new Page($count, 10);
        $order_list_obj = $orderModel
        ->where($where_arr)->where($where_str)
        ->limit($Page->firstRow,$Page->listRows)->order("order_id DESC")->select();
        if ($order_list_obj) {
            //转为数字，并获取订单状态，订单状态显示按钮，订单商品
            $order_list=collection($order_list_obj)->append(['order_status_detail','order_button','order_goods','store'])->toArray();
            //返回商品规格组合id(item_id)
            foreach ($order_list as $key=>$order){  //按以前的数据完全可以满足需求，说必须要返回一个reutrn_id给他
                foreach ($order['order_goods'] as $ogk=>$og) {
                    $return_id = Db::name('ReturnGoods')->where(['rec_id' => $og['rec_id']])->getField('id');
                    $order_list[$key]['order_goods'][$ogk]['return_id'] = 0;
                    $return_id && $order_list[$key]['order_goods'][$ogk]['return_id'] = $return_id;
                }
            }
        }
        $this->ajaxReturn(['status'=>1,'msg'=>'获取成功','result'=>$order_list]);
    }

    /*
     * 添加地址
     */
    public function addAddress(){
        //$user_id = I('user_id/d',0);
        if(!$this->user_id) exit(json_encode(array('status'=>-1,'msg'=>'缺少参数','result'=>'')));
        $address_id = I('address_id/d',0);
        $data = $this->userLogic->add_address($this->user_id,$address_id,I('post.')); // 获取用户信息
        exit(json_encode($data));
    }
    /*
     * 地址删除
     */
    public function del_address(){
        $id = I('id/d');
        if(!$this->user_id) exit(json_encode(array('status'=>-1,'msg'=>'缺少参数','result'=>'')));
        $address = M('user_address')->where("address_id" ,$id)->find();
        $row = M('user_address')->where(array('user_id'=>$this->user_id,'address_id'=>$id))->delete();      
      
        // 如果删除的是默认收货地址 则要把第一个地址设置为默认收货地址
        if($address['is_default'] == 1)
        {
            $address = M('user_address')->where("user_id",$this->user_id)->find();    
            
            //@mobify by wangqh {
            if($address) {    
                M('user_address')->where("address_id",$address['address_id'])->save(array('is_default'=>1));
            }//@}
            
        }      

        //@mobify by wangqh 
        if ($row)
           exit(json_encode(array('status'=>1,'msg'=>'删除成功','result'=>''))); 
        else
           exit(json_encode(array('status'=>1,'msg'=>'删除失败','result'=>''))); 
    }
    
    /*
     * 设置默认收货地址
     */
    public function setDefaultAddress() {
//        $user_id = I('user_id/d',0);
        if(!$this->user_id) exit(json_encode(array('status'=>-1,'msg'=>'缺少参数','result'=>'')));
        $address_id = I('address_id/d',0);
        $data = $this->userLogic->set_default($this->user_id,$address_id); // 获取用户信息
        if(!$data)
            exit(json_encode(array('status'=>-1,'msg'=>'操作失败','result'=>'')));
        exit(json_encode(array('status'=>1,'msg'=>'操作成功','result'=>'')));
    }

    /*
     * 获取优惠券列表
     */
    public function getCouponList()
    {
        if (!$this->user_id) {
            $this->ajaxReturn(['status'=>-1, 'msg'=>'还没登录', 'result'=>'']);
        }
        
        $store_id = I('get.store_id', 0);
        $type = I('get.type', 0);
        $order_money = I('get.order_money', 0);
        
        $data = $this->userLogic->get_coupon($this->user_id, $type, null, 0, $store_id, $order_money);
        unset($data['show']);
        
        /* 获取各个优惠券的平台 */
        $coupon_list = &$data['result'];
        $store_id_arr = get_arr_column($coupon_list, 'store_id');
        $store_arr = M('store')->where('store_id', 'in', $store_id_arr)->getField('store_id,store_name,store_logo');
        foreach ($coupon_list as &$coupon) {
            if ($coupon['store_id'] > 0) {
                $coupon['limit_store'] = $store_arr[$coupon['store_id']]['store_name'];
            } else {
                $coupon['limit_store'] = '全平台';
            }
        }
        
        $this->ajaxReturn($data);
    }
    
    /**
     * 获取购物车指定店铺的优惠券
     */
    public function getCartStoreCoupons()
    {
        $store_id = I('store_id/d' , 0);    //限制店铺
        $money = I('money/f' , 0);        //限制金额
        $goods_id = I('goods_id' , 0);        //商品ID
        $item_id = I('item_id' , 0);        //规格ID
        $goods_num = I('goods_num' , 0);        //购买数量
        $action = input('action'); // 行为
        $cartLogic = new CartLogic();
        $couponLogic = new CouponLogic();
        $cartLogic->setUserId($this->user_id);
        if ( $action == 'buy_now'){
            $cartLogic->setGoodsModel($goods_id);
            $cartLogic->setSpecGoodsPriceModel($item_id);
            $cartLogic->setGoodsBuyNum($goods_num);
            $buyGoods = [];
            try{
                $buyGoods = $cartLogic->buyNow();
            }catch (TpshopException $t){
                $error = $t->getErrorArr();
                $this->error($error['msg']);
            }
            $cartList[0] = $buyGoods;
        }else{
            if ($cartLogic->getUserCartOrderCount() == 0){
                $this->ajaxReturn(['status' => -1, 'msg' => '你的购物车没有选中商品']);
            }
            $cartList = $cartLogic->getCartList(1); // 获取用户选中的购物车商品
        }
        $cartGoodsList = get_arr_column($cartList,'goods');
        $cartGoodsId = get_arr_column($cartGoodsList,'goods_id');
        $cartGoodsCatId = get_arr_column($cartGoodsList,'cat_id3');
        $storeCartList = $cartLogic->getStoreCartList($cartList);//转换成带店铺数据的购物车商品
        $userCouponList = $couponLogic->getUserAbleCouponList($this->user_id, $cartGoodsId, $cartGoodsCatId);//用户可用的优惠券列表
        $userCartCouponList = $cartLogic->getCouponCartList($storeCartList, $userCouponList);
        foreach ($storeCartList as &$store) {
            if ($store['store_id'] == $store_id) {
                break;
            }
        }

        $returnCouponList = [];
        foreach ($userCartCouponList as $v) {
            $coupon = $v['coupon'];
            if ($v['store_id'] == $store_id && $coupon['able']) {
                if($money == 0  || ($money > 0 && $coupon['condition'] <  $money)){      //金额限制
                    $limit_store = $store['store_name'];
                    //0全店通用1指定商品可用2指定分类商品可用
                    switch ($coupon['use_type']){
                        case 0 :
                            $returnCoupon['limit_store'] = $limit_store.'全店通用';
                            break;
                        case 1 :
                            $returnCoupon['limit_store'] = $limit_store.'指定商品可用';
                            break;
                        case 2 :
                            $returnCoupon['limit_store'] = $limit_store.'指定分类商品可用';
                            break;
                        case 3 :
                            $returnCoupon['limit_store'] = '全平台可用';
                            break;
                    }
                    $returnCoupon['id'] = $v['id'];
                    $returnCoupon['name'] = $coupon['name'];
                    $returnCoupon['money'] = $coupon['money'];
                    $returnCoupon['condition'] = $coupon['condition'];
                    $returnCoupon['use_start_time'] = $coupon['use_start_time'];
                    $returnCoupon['use_end_time'] = $coupon['use_end_time'];
                    $returnCoupon['store_id'] = $v['store_id'];
                    $returnCouponList[] = $returnCoupon;
                }
            }
        }
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $returnCouponList]);
    }
    
    /*
     * 获取商品收藏列表
     */
    public function getGoodsCollect()
    {
        $data = $this->userLogic->get_goods_collect($this->user_id);
        unset($data['show']);
        unset($data['page']);
        $this->ajaxReturn($data);
    }
    
    /**
     * 取消订单
     */
    public function cancelOrder(){
        $id = I('order_id/d');
//        $user_id = I('user_id/d',0);
        $logic = new OrderLogic();
        if(!$this->user_id > 0 || !$id > 0)
            exit(json_encode(array('status'=>-1,'msg'=>'参数有误','result'=>'')));
        $data = $logic->cancel_order($this->user_id,$id);
        exit(json_encode($data));
    }
     
    /**
     *  收货确认
     */
    public function orderConfirm(){
        $id = I('order_id/d',0);
        //$user_id = I('user_id/d',0);
        if(!$this->user_id || !$id)
            exit(json_encode(array('status'=>-1,'msg'=>'参数有误','result'=>'')));
        $data = confirm_order($id,$this->user_id);            
        exit(json_encode($data));
    }
    
    
    /*
     *添加评论
     */
    public function add_comment()
    {
        $data['order_id']         = input('post.order_id/d', 0);
        $data['rec_id']           = input('post.rec_id/d', 0);
        $data['goods_id']         = input('post.goods_id/d', 0);
        $data['seller_score']     = input('post.service_rank', 0);   //卖家服务分数（0~5）(order_comment表)
        $data['logistics_score']  = input('post.deliver_rank', 0); //物流服务分数（0~5）(order_comment表)
        $data['describe_score']   = input('post.goods_rank', 0);  //描述服务分数（0~5）(order_comment表)
        $data['goods_rank']       = input('post.goods_score/d', 0);   //商品评价等级
        $data['is_anonymous']     = input('post.is_anonymous/d', 0);
        $data['content']          = input('post.content', '');
        $data['img']              = input('post.img/a', ''); //小程序需要
        $data['user_id']          = $this->user_id;
        
        $commentLogic = new CommentLogic;
        $return = $commentLogic->addGoodsAndServiceComment($data);
        
        $this->ajaxReturn($return);
    }  
    
    /**
     * 提交服务评论
     */
    public function add_service_comment()
    {
        $order_id = I('post.order_id/d', 0);
        $service_rank = I('post.service_rank', 0);
        $deliver_rank = I('post.deliver_rank', 0);
        $goods_rank = I('post.goods_rank', 0);

        $store_id = M('order')->where(array('order_id' => $order_id))->getField('store_id');
        
        $commentLogic = new CommentLogic;
        $return = $commentLogic->addServiceComment($this->user_id, $order_id, $store_id, $service_rank, $deliver_rank, $goods_rank);
        
        $this->ajaxReturn($return);
    }
    
    /**
     * 上传头像
     */
    public function upload_headpic()
    {
        $userLogic = new UsersLogic();

        $return = $userLogic->upload_headpic(true);
        if ($return['status'] !== 1) {
            $this->ajaxReturn($return);
        }
        $post['head_pic'] = $return['result'];
        
        if (!$userLogic->update_info($this->user_id, $post)) {
            $this->ajaxReturn(['status' => -1, 'msg' => '保存失败']);
        }
        $this->ajaxReturn(['status' => 1, 'msg' => '操作成功', 'result' => $post['head_pic']]);
    }
    
    /*
     * 账户资金
     */
    public function account(){
        
        $unique_id = I("unique_id"); // 唯一id  类似于 pc 端的session id
       // $user_id = I('user_id/d'); // 用户id
        //获取账户资金记录
        
        $data = $this->userLogic->get_account_log($this->user_id,I('get.type'));
        $account_log = $data['result'];
        exit(json_encode(array('status'=>1,'msg'=>'获取成功','result'=>$account_log)));
    }    

    /**
     * 申请退货状态
     */
    public function return_goods_status()
    {
        $rec_id = I('rec_id','');
        
        $order_goods = Db::name('order_goods')->where(['rec_id'=>$rec_id])->find();
        if (empty($order_goods)) {
            $this->ajaxReturn(['status'=>1,'msg'=>'rec_id,参数错误']);
        }
        $return_goods = Db::name('return_goods')->where(['rec_id'=>$rec_id])->find();

        //判断是否超过退货期
        $order = Db::name('order')->where('order_id',$order_goods['order_id'])->find();
        $auto_service_date = tpCache('shopping.auto_service_date'); //申请售后时间段
        $confirm_time = strtotime("-$auto_service_date day");
        if (!$return_goods && ($order['confirm_time'] < $confirm_time) && !empty($order['confirm_time'])) {
            $this->ajaxReturn(['status'=>1,'msg'=>'已经超过' . ($auto_service_date ?: 0) . "天内退货时间",'code'=>-1]); //-1表示过期
        }

        if($return_goods){ //code: 0. 可申请售后， 1：正在处理 ， -1：过期
            $this->ajaxReturn(['status'=>1, 'msg'=>'获取成功', 'code'=>1, 'result' =>$return_goods['id']]);
        }else{
            $this->ajaxReturn(['status'=>1, 'msg'=>'获取成功', 'code'=>0, 'result' => 0]);
        }
    }
     
    /**
     * 获取收藏店铺列表集合, 只用于查询用户收藏的店铺, 页面判断用, 区别于getUserCollectStore
     */
    public function getCollectStoreData()
    {
        $where = array('user_id' => $this->user_id);
        $storeCollects = M('store_collect')->where($where)->select();
        $json_arr = array('status' => 1, 'msg' => '获取成功', 'result' => $storeCollects);
        exit(json_encode($json_arr));
    }

    /**
     * @author dyr
     * 获取用户收藏店铺列表
     */
    public function getUserCollectStore()
    {
        $page = I('page', 1);
        $db_prefix = C('database.prefix');
        $store_list = Db::name('store_collect')->alias('sc')
            ->field('sc.*,sg.sg_name,s.store_logo,s.store_avatar,s.store_collect,s.store_servicecredit')
            ->join($db_prefix.'store s', 'sc.store_id = s.store_id', 'left')
            ->join($db_prefix.'store_grade sg', 'sg.sg_id = s.grade_id', 'left')
            ->where(['sc.user_id'=>$this->user_id])
            ->page($page,10)
            ->select();
        $json_arr = array('status' => 1, 'msg' => '获取成功', 'result' => $store_list);
        exit(json_encode($json_arr));
    }
    
    /**
     * 申请提现记录列表网页
     * @return type
     */
    public function withdrawals_list()
    {
        $is_json = I('is_json', 0); //json数据请求
        $withdrawals_where['user_id'] = $this->user_id;
        $count = M('withdrawals')->where($withdrawals_where)->count();
        $pagesize = C('PAGESIZE') == 0 ? 10 : C('PAGESIZE');
        $page = new Page($count, $pagesize);
        $list = M('withdrawals')->where($withdrawals_where)->order("id desc")->limit("{$page->firstRow},{$page->listRows}")->select();

        if ($is_json) {
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $list]);
        }
        
        $this->assign('page', $page->show());// 赋值分页输出
        $this->assign('list', $list); // 下线
        if (I('is_ajax')) {
            return $this->fetch('ajax_withdrawals_list');
        }
        return $this->fetch();
    }
    
    /**
     * 申请提现
     */
    public function withdrawals()
    {
        $data = I('post.');
        if (!capache([], SESSION_ID, $data['verify_code'])) {
            $this->ajaxReturn(['status' => -1, 'msg' => "验证码错误"]);
        }
        if(isset($_POST['paypwd']) && encrypt($data['paypwd']) != $this->user['paypwd']){
            $this->ajaxReturn(['status' => -1, 'msg' => "支付密码错误"]);
        }
        $data['user_id'] = $this->user_id;    		    		
        $data['create_time'] = time();                
        $distribut_min = tpCache('basic.min'); // 最少提现额度
        $distribut_need  = tpCache('basic.need'); //满多少才能提
        if ($data['money'] < $distribut_min) {
            $this->ajaxReturn(['status' => -1, 'msg' => '每次最少提现额度'.$distribut_min]);
        }
        if ($data['money'] > $this->user['user_money']) {
            $this->ajaxReturn(['status' => -1, 'msg' => "你最多可提现{$this->user['user_money']}账户余额."]);
        } 
        if ($this->user['user_money']<$distribut_need) {
            $this->ajaxReturn(['status' => -1, 'msg' => '账户余额最少达到'.$distribut_need.'才能提现']);
        }    

        $withdrawal = M('withdrawals')->where(array('user_id'=>$this->user_id,'status'=>0))->sum('money');
        if ($this->user['user_money'] < ($withdrawal+$data['money'])){
            $this->ajaxReturn(['status' => -1, 'msg' => '您有提现申请待处理，本次提现余额不足']);
        }
        $bank['bank_card']= $data['bank_card'] = $data['account_bank'];
        $bank['realname'] = $data['realname'] = $data['account_name'];
        if (M('withdrawals')->add($data)) {
            $bank['bank_name'] = $data['bank_name'];
            M('users')->where(array('user_id'=>$this->user_id))->save($bank);
            $json_arr = array('status' => 1, 'msg' => '提交成功');
        } else {
            $json_arr = array('status' => -1, 'msg' => '提交失败,联系客服!');
        }
        $this->ajaxReturn($json_arr);
    }
    
    /**
     * 账户明细
     */
    public function points()
    {
        $type = I('type','all');
        $usersLogic = new UsersLogic;
    	$result = $usersLogic->points($this->user_id, $type);
        
        $json_arr = ['status' => 1, 'msg' => '获取成功', 'result' => $result['account_log']];
        exit(json_encode($json_arr));
    }
    
    /**
     * 图形验证码获取
     */
    public function verify()
    {
        $type = I('get.type') ?: SESSION_ID;
        $is_image = I('get.is_image', 0);
        if (!$is_image) {
            $result = capache([], $type);
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result]);
        }

        $config = array(
            'fontSize' => 30,
            'length' => 4,
            'imageH' =>  60,
            'imageW' =>  300,
            'fontttf' => '5.ttf',
            'useCurve' => true,
            'useNoise' => false,
        );
        $Verify = new \think\Verify($config);
        $Verify->entry($type);
        exit;
    }
    
    /**
     * 评论列表
     */
    public function comment()
    {
        $status = I('get.status', 0);
        $logic = new CommentLogic;
        $result = $logic->getComment($this->user_id, $status);
        
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result['result']]);
    }
    
    /**
     * 服务评论列表
     */
    public function service_comment()
    {
        $p = input('p', 1);
        $logic = new CommentLogic;
        $result = $logic->getServiceComment($this->user_id, $p);
        
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result]);
    }
    
    public function comment_num()
    {
        $logic = new CommentLogic;
        $result = $logic->getAllTypeCommentNum($this->user_id);
        
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result]);
    }
    
    /**
     * 浏览记录
     */
    public function visit_log()
    {
        $p = I('get.p', 1);

        $user_logic = new UsersLogic;
        $visit_list = $user_logic->visit_log($this->user_id, $p);
        
        $list = [];
        foreach ($visit_list as $k => $v) {
            $list[] = ['date' => $k, 'visit' => $v];
        }
        
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $list]);
    }

    /**
     * 删除浏览记录
     */
    public function del_visit_log()
    {
        $visit_ids = I('visit_ids', 0);
        $row = M('goods_visit')->where('visit_id','IN', $visit_ids)->delete();
        if (!$row) {
            $this->ajaxReturn(['status' => -1, 'msg' => '删除失败']);
        }
        $this->ajaxReturn(['status' => 1, 'msg' => '删除成功']);
    }
    
    /**
     * 清空浏览记录
     */
    public function clear_visit_log()
    {
        $row = M('goods_visit')->where('user_id', $this->user_id)->delete();
        if(!$row) {
            $this->ajaxReturn(['status' => -1, 'msg' => '删除失败']);
        }
        $this->ajaxReturn(['status' => 1, 'msg' => '删除成功']);
    }
    
    /**
     *  获取用户消息通知
     */
    public function message_notice()
    {
        $messageModel = new \app\common\logic\MessageLogic;
        $messages = $messageModel->getUserPerTypeLastMessage();

        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $messages]);
    }
    
    /**
     * 获取消息
     */
    public function message()
    {
        $p = I('get.p', 1);
        $category = I('get.category', 0);
        
        $messageModel = new \app\common\logic\MessageLogic;
        $message = $messageModel->getUserMessageList($this->user_id, $category, $p);

        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $message]);
    }
    
    /**
     * 消息开关
     */
    public function message_switch()
    {
        if (!$this->user) {
            $this->ajaxReturn(['status' => -1, 'msg' => '用户不存在']);
        }
        
        $messageModel = new \app\common\logic\MessageLogic;
        
        if (request()->isGet()) {
            /* 获取消息开关 */
            $notice = $messageModel->getMessageSwitch($this->user['message_mask']);
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $notice]);
        } elseif (request()->isPost()) {
            /* 设置消息开关 */
            $type = I('post.type/d', 0); //开关类型
            $val = I('post.val', 0); //开关值
            $return = $messageModel->setMessageSwitch($type, $val, $this->user);
            $this->ajaxReturn($return);
        }

        $this->ajaxReturn(['status' => -1, 'msg' => '请求方式错误']);
    }

    /**
     * 清除消息
     */
    public function clear_message()
    {
        if (!$this->user_id) {
            $this->ajaxReturn(['status' => -1, 'msg' => '用户不存在']);
        }
        
        $messageModel = new \app\common\logic\MessageLogic;
        $messageModel->setMessageRead($this->user_id);
        
        $this->ajaxReturn(['status' => 1, 'msg' => '清除成功']);
    }
    
    /**
     * 账户明细列表网页
     * @return type
     */
    public function account_list()
    {
    	$type = I('type','all');
        $is_json = I('is_json', 0); //json数据请求
    	$usersLogic = new UsersLogic;
    	$result = $usersLogic->account($this->user_id, $type);
        
        if ($is_json) {
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result['account_log']]);
        }
        
    	$this->assign('type', $type);
    	$showpage = $result['page']->show();
    	$this->assign('account_log', $result['account_log']);
    	$this->assign('page', $showpage);
    	if (I('is_ajax')) {
    		return $this->fetch('ajax_acount_list');
    	}
    	return $this->fetch();
    }
    
    /**
     * 积分类别网络
     * @return type
     */
    public function points_list()
    {
        $type = I('type','all');
        $is_json = I('is_json', 0); //json数据请求
    	$usersLogic = new UsersLogic;
    	$result = $usersLogic->points($this->user_id, $type);
        
        if ($is_json) {
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result['account_log']]);
        }
        
        $this->assign('type', $type);
		$showpage = $result['page']->show();
        $this->assign('account_log', $result['account_log']);
        $this->assign('page', $showpage);
        if (I('is_ajax')) {
            return $this->fetch('ajax_points');
        }
        return $this->fetch();
    }
    
    /**
     * 充值记录网页
     * @return type
     */
    public function recharge_list()
    {
        $is_json = I('is_json', 0); //json数据请求
        $userLogic = new UsersLogic;
        $userLogic->setUserId($this->user_id);
        $result = $userLogic->get_recharge_log();  //充值记录
        if ($is_json) {
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result['result']]);
        }
        
        $this->assign('page', $result['show']);
    	$this->assign('lists', $result['result']);
    	if (I('is_ajax')) {
    		return $this->fetch('ajax_recharge_list');
    	}
    	return $this->fetch();
    }
    
    /**
     * 物流网页
     * @return type
     */
    public function express()
    {
        $is_json = I('is_json', 0);
        $order_id = I('get.order_id/d', 0);
        $order_goods = M('order_goods')->where("order_id" , $order_id)->select();
        $delivery = M('delivery_doc')->where("order_id" , $order_id)->limit(1)->find();
        if ($is_json) {
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $delivery]);
        }
        $this->assign('order_goods', $order_goods);
        $this->assign('delivery', $delivery);
        return $this->fetch();
    }
    
    /**
     * 获取全部地址信息, 从BaseController移入到UserController @modify by wangqh.
     */
    public function allAddress(){
        $data =  M('region')->where('level < 4')->select();
        $json_arr = array('status'=>1,'msg'=>'成功!','result'=>$data);
        $json_str = json_encode($json_arr);
        exit($json_str);
    }
    
    /**
     * 关于我们页面
     */
    public function about_us()
    {
        return $this->fetch();
    }
    
    /**
     * 检查token状态
     */
    public function token_status()
    {
        $token = I('token/s', '');
        $return = $this->getUserByToken($token);
        if ($return['status'] == 1) {
            $return['result'] = '';
        }
        $this->ajaxReturn($return);
    }
    
    /**
     * 上传评论图片，小程序图片只能一张一张传
     */
    public function upload_comment_img()
    {
        $logic = new \app\common\logic\CommentLogic;
        $img = $logic->uploadCommentImgFile('comment_img_file');
        
        if ($img['status'] === 1) {
            $img['result'] = implode(',', $img['result']);
        }

        $this->ajaxReturn($img);
    }
    
    /**
     * 消息列表（小程序临时接口by lhb）
     * @author dyr
     * @time 2016/09/01
     */
    public function message_list()
    {
        $type = I('type', 0);
        $user_logic = new UsersLogic();
        $message_model = new \app\common\logic\MessageLogic();
        if ($type == 1) {
            //系统消息
            $user_sys_message = $message_model->getUserMessageNotice();
            //$user_logic->setSysMessageForRead();
        } else if ($type == 2) {
            //活动消息：后续开发
            $user_sys_message = array();
        } else {
            //全部消息：后续完善
            $user_sys_message = $message_model->getUserMessageNotice();
        }
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $user_sys_message]);
    }
 
    
    
    /**
     * 支付密码
     */
    public function paypwd()
    {
        //短信验证走check_sms
//        $code = I('post.paypwd_code');
//        $mobile = trim(I('mobile'));
//
//        $session_id = SESSION_ID;
//        $scene = 6; //身份验证场景

        $logic = new UsersLogic();
//        $res = $logic->check_validate_code($code, $mobile, 'mobile' ,$session_id, $scene);
//        if ($res['status'] !== 1) {
//            $this->ajaxReturn($res);
//        }

        //检查是否第三方登录用户
        $user = M('users')->where('user_id', $this->user_id)->find();;
        if ($user['mobile'] == '' && $user['email'] == '') {
            $this->ajaxReturn(['status'=>-1, 'msg'=>'请先绑定手机号或者邮箱']);
        }

        $new_password = trim(I('new_password'));
        $data = $logic->payPwdForApp($this->user_id, $new_password);
        $this->ajaxReturn($data);
    }
    
}
