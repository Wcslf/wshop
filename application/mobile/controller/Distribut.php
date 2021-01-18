<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * 2015-11-21
 */
namespace app\mobile\controller;
use app\common\logic\GoodsLogic;
use app\common\logic\DistributLogic;
use think\Verify;
use think\Db;
use think\Page;

class Distribut extends MobileBase {
    /*
    * 初始化操作
    */
    public $user_id = 0;
    public $user = [];
    public function _initialize() {
        parent::_initialize();
        if($this->tpshop_config['distribut_switch'] != 1){
            $this->error('分销已关闭',U('Mobile/Index/index'));
        }
        if(session('?user'))
        {
            $user = session('user');
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user',$user); //存储用户信息
        }
        $nologin = array(
            'login','pop_login','do_login','logout','verify','set_pwd','finished',
            'verifyHandle','reg','send_sms_reg_code','find_pwd','check_validate_code',
            'forget_pwd','check_captcha','check_username','send_validate_code',
        );
        if(!$this->user_id && !in_array(ACTION_NAME,$nologin)){
            header("location:".U('Mobile/User/login'));
            exit;
        }
        $first_leader = I('first_leader/d');
        if($this->user['is_distribut'] == 1){ //是分销商才查找用户店铺信息
            $store_user_id = ($first_leader>0) ? $first_leader :  $this->user_id;
            $user_store = Db::name('user_store')->where("user_id", $store_user_id)->find();
            $this->userStore=$user_store;
            $this->assign('store',$user_store);
        }

        $order_count = Db::name('order')->where("user_id", $this->user_id)->count(); // 我的订单数
        $goods_collect_count = Db::name('goods_collect')->where("user_id", $this->user_id)->count(); // 我的商品收藏
        $comment_count = Db::name('comment')->where("user_id", $this->user_id)->count();//  我的评论数
        $coupon_count = Db::name('coupon_list')->where("uid", $this->user_id)->count(); // 我的优惠券数量
        $first_nickname = Db::name('users')->where("user_id", $this->user['first_leader'])->getField('nickname');
        $level_name = Db::name('user_level')->where("level_id", $this->user['level'])->getField('level_name'); // 等级名称
        $this->assign('level_name',$level_name);
        $this->assign('first_nickname',$first_nickname);
        $this->assign('order_count',$order_count);
        $this->assign('goods_collect_count',$goods_collect_count);
        $this->assign('comment_count',$comment_count);
        $this->assign('coupon_count',$coupon_count);

    }

    /**
     * 分销用户中心首页（分销中心）
     */
    public function index(){
        // 销售额 和 我的奖励
        $result = DB::query("select sum(goods_price) as goods_price, sum(money) as money from __PREFIX__rebate_log where user_id = {$this->user_id}");
        $result = $result[0];
        $result['goods_price'] = $result['goods_price'] ? $result['goods_price'] : 0;
        $result['money'] = $result['money'] ? $result['money'] : 0;

        $lower_count[1] = Db::name('users')->where("first_leader", $this->user_id)->count();
        $lower_count[2] = Db::name('users')->where("second_leader", $this->user_id)->count();
        $lower_count[3] = Db::name('users')->where("third_leader", $this->user_id)->count();


        $result2 = DB::query("select status,count(1) as c , sum(goods_price) as goods_price from `__PREFIX__rebate_log` where user_id = :user_id group by status",['user_id'=>$this->user_id]);
        $level_order = convert_arr_key($result2, 'status');
        for($i = 0; $i <= 5; $i++)
        {
            $level_order[$i]['c'] = $level_order[$i]['c'] ? $level_order[$i]['c'] : 0;
            $level_order[$i]['goods_price'] = $level_order[$i]['goods_price'] ? $level_order[$i]['goods_price'] : 0;
        }

        $money['withdrawals_money'] = Db::name('withdrawals')->where(['user_id'=>$this->user_id,'status'=>1])->sum('money'); // 已提现财富
        $money['achieve_money'] = Db::name('rebate_log')->where(['user_id'=>$this->user_id,'status'=>3])->sum('money');  //累计获得佣金
        $time=strtotime(date("Y-m-d"));
        $money['today_money'] = Db::name('rebate_log')->where("user_id=$this->user_id and status in(2,3) and create_time>$time")->sum('money');    //今日收入
        $this->assign('user_id',$this->user_id);
        $this->assign('level_order',$level_order); // 下线订单
        $this->assign('lower_count',$lower_count); // 下线人数
        $this->assign('sales_volume',$result['goods_price']); // 销售额
        $this->assign('reward',$result['money']);// 奖励
        $this->assign('money',$money);
        return $this->fetch();
    }

    /**
     * 下线列表(我的团队)
     */
    public function lower_list(){
        $user =$this->user;
        if($user['is_distribut'] != 1) {
            $this->error('您还不是分销商');
        }

        $level = I('get.level',1);
        $q = I('post.q','','trim');
        
        $logic = new DistributLogic;
        $result = $logic->lowerList($this->user_id, $level, $q);
        
        $this->assign('count', $result['count']);// 总人数
        $this->assign('page', $result['page']);// 赋值分页输出
        $this->assign('lists',$result['lists']); // 下线
        if (I('is_ajax')) {
            return $this->fetch('ajax_lower_list');
        }
        return $this->fetch();
    }

    /**
     * 下线订单列表（分销订单）
     */
    public function order_list()
    {
        if ($this->user['is_distribut'] != 1) {
            $this->error('您还不是分销商');
        }
        $store = db('user_store')->where(['user_id'=>$this->user['user_id']])->find();
        if (!$store) {
            $this->error('请先创建店铺');
        }
        $status = I('get.status',0);
        $logic = new DistributLogic;
        $result = $logic->orderList($this->user_id, $status);
        $this->assign('page', $result['page']->show());// 赋值分页输出
        $this->assign('list', $result['list']); // 下线
        if(I('is_ajax')){
            return $this->fetch('ajax_order_list');
        }
        return $this->fetch();
    }


    /**
     * 验证码验证
     * $id 验证码标示
     */
    private function verifyHandle($id)
    {
        $verify = new Verify();
        if (!$verify->check(I('post.verify_code'), $id ? $id : 'user_login')) {
            $this->error("验证码错误");
        }
    }

    /**
     * 验证码获取
     */
    public function verify()
    {
        //验证码类型
        $type = I('get.type') ? I('get.type') : 'user_login';
        $config = array(
            'fontSize' => 40,
            'length' => 4,
            'useCurve' => true,
            'useNoise' => false,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);
    }

    /**
     * 个人推广二维码 （我的名片）
     */
    public function qr_code()
    {
        $qr_mode = input('qr_mode', 0); //0：商家二维码，1：微信二维码
        $user_id = input('user_id', 0);
        if (!$user_id) {
            return $this->fetch();
        }
        
        $is_owner = false;//是否是本网页的用户
        if ($user_id == $this->user_id) {
            $user = $this->user;
            $is_owner = true;
        } else {
            $user = M('users')->where('user_id', $user_id)->find();
            if (!$user && $user['is_distribut'] != 1) {
                return $this->fetch();
            }
        }
        
        if ($qr_mode == 1 && $user['is_distribut'] != 1) {
            $this->error('楼主已不是分销商');
        }

        $wx_user = M('wx_user')->find();
        if ($qr_mode && $wx_user) {
            $wechatObj = new \app\common\logic\wechat\WechatUtil($wx_user);
            $wxdata = $wechatObj->createTempQrcode(2592000, $user['user_id']); //30天过期,推荐人
            if (empty($wxdata['url'])) {
                $this->error('微信未成功接入');
            }
        } 
        if ($qr_mode && $wx_user && !empty($wxdata['url'])) {
            $shareLink = urlencode($wxdata['url']);
        } else {
            $shareLink = urlencode("http://{$_SERVER['HTTP_HOST']}/index.php?m=Mobile&c=Index&a=index&first_leader={$user['user_id']}"); //默认分享链接
        }
        
        $head_pic = $user['head_pic'] ?: '';
        if ($head_pic && strpos($head_pic, 'http') !== 0) {
            $head_pic = '.'.$head_pic;
        }
        
        $config = tpCache('distribut');
        $back_img = $config['qr_back'] ? '.'.$config['qr_back'] : './template/mobile/new2/static/images/zz6.png'; 

        $this->assign('user',  $user);
        $this->assign('is_owner', $is_owner);
        $this->assign('qr_mode',  $qr_mode);
        $this->assign('head_pic', $head_pic);
        $this->assign('back_img', $back_img);
        $this->assign('ShareLink', $shareLink);
        return $this->fetch();
    }

    public function distribution_list(){
        if(request()->isAjax()){
            $logic = new DistributLogic;
            $goodsList = $logic->getStoreGoods($this->user_id);
            $this->assign('goodsList', $goodsList['list']);
            return $this->fetch('ajax_goods_list');
        }
        return $this->fetch();
    }

    public function delete(){
        if (!$this->user_id) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请先登录']);
        }
        $goods_ids = I('post.goods_ids/a', []);
        if(count($goods_ids) > 0){
            $deleted = Db::name('user_distribution')->where(['user_id'=>$this->user_id,'goods_id'=>['in',$goods_ids]])->delete();
            if($deleted !== false){
                $this->ajaxReturn(['status' => 1, 'msg' => '删除成功']);
            }else{
                $this->ajaxReturn(['status' => 0, 'msg' => '删除失败']);
            }
        }else{
            $this->ajaxReturn(['status' => 0, 'msg' => '请选择要删除的商品']);
        }
    }

    /**
     * 平台分销商品列表
     */
    public function goods_list() 
    {
        if ($this->user['is_distribut'] != 1) {
            $this->error('您还不是分销商');
        }
        $store = db('user_store')->where(['user_id'=>$this->user['user_id']])->find();
        if (!$store) {
            $this->error('请先创建店铺');
        }
        $goodsLogic = new GoodsLogic();
        $brandList = $goodsLogic->getSortBrands();
        $categoryList =  Db::name("GoodsCategory")->where(['level'=>1])->getField('id,name,parent_id,level');
        $this->assign('categoryList', $categoryList);    //品牌
        $this->assign('brandList', $brandList);  //分类
        return $this->fetch();
    }
    
    /**
     * 平台分销商品列表
     */
    public function ajax_goods_list()
    {
        $sort = I('sort', 'goods_id'); // 排序
        $order = I('sort_asc', 'asc'); // 排序
        $cat_id = I('cat_id/d', 0);
        $brand_id = I('brand_id/d', 0);//品牌
        $key_word = trim(I('key_word/s', ''));
        $where = ['is_on_sale'=>1,'distribut'=>['gt',0]];
        if ($cat_id > 0) {
            $grandson_ids = getCatGrandson($cat_id);
            $where['cat_id1'] = ['in',$grandson_ids];
        }
        if ($key_word) {  //搜索
            $where['goods_name'] = ['like', '%'.$key_word.'%'];
        }
        if ($brand_id > 0) {
            $where['brand_id'] = $brand_id;
        }
        if (!in_array($sort, ['goods_id', 'is_new', 'sales_sum', 'distribut'])) {
            $sort = 'goods_id';
        }
        //查找用户已添加的商品ID
        $distribution_ids = Db::name('user_distribution')->where('user_id', $this->user_id)->column('goods_id');
        if ($distribution_ids) {
            $where['goods_id'] = ['not in', $distribution_ids];
        }
        $count = Db::name('goods')->where($where)->count();
        $page = new Page($count, 10);
        $goodsList = Db::name('goods')->field('goods_name,goods_id,distribut,shop_price,brand_id,store_id,cat_id3')
            ->where($where)->order($sort, $order)
            ->limit($page->firstRow, $page->listRows)
            ->select();
        $this->assign('goodsList', $goodsList);
        return $this->fetch();
    }

    /**
     * 添加分销商品
     * @author  lxl
     * @time2017-4-6
     */
    public function add_goods()
    {
        if (!$this->user_id) {
            $this->redirect('Mobile/User/index');
        }
        $goods_ids = I('post.goods_ids/a', []);
        
        $distributLogic = new DistributLogic;
        $result = $distributLogic->addGoods($this->user, $goods_ids);
        if($result){
            $this->success('成功',U('Mobile/Distribut/goods_list'));
        }else{
            $this->error('失败');
        }
    }

    /**
     * 店铺设置
     * @author  lxl
     * @time2017-4-6
     */
    public function set_store()
    {
        if (IS_POST) {
            $storeName = I('store_name', '');
            $trueName = I('true_name', '');
            $mobile = I('mobile', '');
            $qq = I('qq/d');
            $logic = new DistributLogic;
            $result = $logic->setStoreInfo($this->user_id, $storeName, $trueName, $mobile, $qq);
            if ($result['status'] != 1) {
                $this->ajaxReturn(['status'=>-1,'msg'=>$result['msg']]);
            }
            $this->ajaxReturn(['status'=>1,'msg'=>$result['msg']]);
        }
        if ($this->user['is_distribut'] != 1) {
            $this->error('您还不是分销商');
        }
        return $this->fetch();
    }

    /**
     * 用户分销商品
     * @author  lxl
     * @time2017-4-6
     */
    public function my_store()
    {
        if ($this->user['is_distribut'] != 1) {
            $this->error('您还不是分销商');
        }
        $store = db('user_store')->where(['user_id'=>$this->user['user_id']])->find();
        if (!$store) {
            $this->error('请先创建店铺');
        }
        $logic = new DistributLogic;
        $first_leader = I('first_leader/d');
        
        if($first_leader > 0){ //如果是上级店铺的连接则显示上级的微店
            $firstLeader = M("Users")->where('user_id' , $first_leader)->field('nickname , mobile , head_pic')->find();
            $user_id = $first_leader;
            $first_leader_nickname = empty($firstLeader['nickname']) ? $firstLeader['mobile'] : $firstLeader['nickname'];
            $head_pic = $firstLeader['head_pic'];
            $store_name = $first_leader_nickname.'的微店';
        }else{
            $user_id = $this->user_id;
            $store_name = "我的微店";
            $head_pic = $this->user['head_pic'];
        }
        
        $lists = $logic->getStoreGoods($user_id);
        $this->assign('lists', $lists['list']);


        if (I('is_ajax')) {
            return $this->fetch('ajax_my_store');
        }
         
        $sales = $logic->getStoreSales();
        $this->assign('user_id', $user_id);
        $this->assign('head_pic', $head_pic);
        $this->assign('store_name', $store_name);
        $this->assign('promotion', $sales['prom_num']);
        $this->assign('new', $sales['new_num']);
        $this->assign('totalRows', $lists['totalRows']);
        return $this->fetch();
    }


    /**
     * 新手必看
     * @author  lxl
     * @time2017-4-6
     */
    public function must_see(){
        $article = M('article')->where(['cat_id'=>13,'is_open'=>1])->select();
        $this->assign('article', $article);
        return $this->fetch();
    }

    /**
     *分销排行
     * @author  lxl
     * @time2017-4-6
     */
    public function rankings()
    {
        $sort = I('get.sort', 'distribut_money');
        $p= I('get.p/d', 1);

        $logic = new DistributLogic;
        $result = $logic->rankings($this->user, $sort, $p);
        
        $this->assign('lists', $result['lists']);
        $this->assign('sort', $sort);
        $this->assign('firstRow', $result['firstRow']);  //当前分页开始数
        $this->assign('place', $result['place']);  
        
        if(I('is_ajax')){
            return $this->fetch('ajax_rankings');
        }
        return $this->fetch();
    }
    
    /**
     * 分成记录
     * @author  lxl
     * @time2017-4-6
     */
    public function rebate_log()
    {
        if ($this->user['is_distribut'] != 1) {
            $this->error('您还不是分销商');
        }
        
        $status = I('status',''); //日志状态
        $order = I('sort_asc','desc');  //排序
        $sort  = I('sort','create_time'); //排序条件
        
        $logic = new DistributLogic;
        $result = $logic->getRebateLog($this->user_id, $status, $sort, $order);        
        
        $this->assign('lists',$result['list']);
        if(I('is_ajax')){
            return $this->fetch('ajax_rebate_log');
        }
        return $this->fetch();
    }
}