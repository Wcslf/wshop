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

use app\common\logic\CouponLogic;
use app\common\logic\GoodsLogic;
use app\common\logic\Pay;
use app\common\logic\PlaceOrder;
use app\common\logic\team\TeamOrder;
use app\common\model\Goods;
use app\common\model\Order;
use app\common\model\OrderGoods;
use app\common\model\team\TeamActivity;
use app\common\model\team\TeamFound;
use app\common\util\TpshopException;
use think\Db;
use think\Page;


class Team extends Base
{
    public $json = [];
    /**
     * 构造函数
     */
    public function  __construct()
    {
        $this->json['status'] = 1;
        $this->json['msg'] = '获取成功';
        parent::__construct();
    }

    /**
     * 拼团首页列表
     */
    public function AjaxTeamList()
    {
        $p = input('p', 1);
        $id = input('id/d');//一级分类ID
        $tid = input('tid/d');//二级分类ID
        $goods_where = ['is_on_sale' => 1];
        if ($id) {
            $goods_where['cat_id1'] = $id;
        }
        if ($tid) {
            $goods_where['cat_id2'] = $tid;
        }
        $team_where = ['t.status' => 1, 't.is_lottery' => 0, 'g.is_on_sale' => 1];
        if (count($goods_where) > 0) {
            $goods_ids = Db::name('goods')->where($goods_where)->getField('goods_id', true);
            if (!empty($goods_ids)) {
                $team_where['t.goods_id'] = ['IN', $goods_ids];
            } else {
                $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => '']);
            }
        }
        $TeamActivity = new TeamActivity();
        $list = $TeamActivity->field('t.team_id,t.act_name,t.team_price,t.needer,t.goods_id,t.item_id')->alias('t')->join('__GOODS__ g', 'g.goods_id = t.goods_id')
            ->with([
                'goods'=>function($query) {
                    $query->field('goods_id,shop_price');
                },
                'specGoodsPrice'=>function($query) {
                    $query->field('item_id,price');
                }
            ])
            ->where($team_where)->group('t.goods_id')->order('t.team_id desc')->page($p, 10)->select();
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $list]);
    }

    public function info(){
        $team_id = input('team_id');
        $goods_id = input('goods_id');
        if(empty($goods_id)){
            $this->json['msg'] = "参数错误";
            $this->json['status'] = 0;
            $this->ajaxReturn($this->json);
        }
        $TeamActivity = new TeamActivity();
        $Goods = new Goods();
        $goods = $Goods->field('goods_id,goods_name,market_price,goods_content,shop_price,store_count')->where(['is_on_sale'=>1,'goods_id'=>$goods_id])->find();
        if($goods){
            $goods = $goods->append(['comment_statistics'])->toArray();
        }else{
            $this->json['status'] = 0;
            $this->json['msg'] = '此商品不存在或者已下架';
            $this->ajaxReturn($this->json);
        }
        $teamList = $TeamActivity->field('team_id,team_price,needer,share_desc,store_id,goods_name,item_id,status')->where('goods_id', $goods_id)->select();
        if($teamList){
            $teamList = collection($teamList)->append(['bd_url','front_status_desc','bd_pic'])->toArray();
        }else{
            $this->json['status'] = 0;
            $this->json['msg'] = '该商品拼团活动不存在或者已被删除';
            $this->ajaxReturn($this->json);
        }
        foreach($teamList as $teamKey=>$teamVal){
            if($team_id && $teamVal['team_id'] == $team_id){
                $team = $teamVal;
                break;
            }
        }
        if(empty($team)){
            $team = $teamList[0];
        }
        $store = Db::name('store')->field('store_id,store_logo,store_name,store_sales,service_phone')->where('store_id',$teamList[0]['store_id'])->find();
        if($this->user_id){
            $collect = Db::name('goods_collect')->where(array("goods_id"=>$goods_id ,"user_id"=>$this->user_id))->count();
        }
        $this->json['result']['collect'] = empty($collect) ? 0 : 1;
        $spec_goods_price = Db::name('spec_goods_price')->field("item_id,key,price,store_count,item_id,prom_id")->where("goods_id",$goods_id)->select(); // 规格 对应 价格 库存表
        $goods_images_list = Db::name('goods_images')->field('image_url')->where("goods_id" , $goods_id)->select(); // 商品图册
        $goodsLogic = new GoodsLogic();
        $filter_spec = $goodsLogic->get_spec($goods_id);
        $goods_spec_list = [];
        foreach ($filter_spec as $key => $val) {
            $goods_spec_list[] = [
                'spec_name' => $key,
                'spec_list' => $val,
            ];
        }
        if($spec_goods_price){
            foreach($spec_goods_price as $specKey=>$specVal){
                $spec_goods_price[$specKey]['team_id'] = 0;
                foreach($teamList as $teamKey=>$teamVal){
                    if($specVal['item_id'] == $teamVal['item_id'] && $teamVal['status'] == 1){
                        $spec_goods_price[$specKey]['team_id'] = $teamVal['team_id'];
                        $spec_goods_price[$specKey] = array_merge($spec_goods_price[$specKey], $teamVal);
                        continue;
                    }
                }
            }
        }
        $this->json['result']['goods_images_list'] = $goods_images_list;
        $this->json['result']['filter_spec'] = $goods_spec_list;//规格参数
        $this->json['result']['goods'] = $goods;
        $this->json['result']['goods']['is_collect'] = $goodsLogic->isCollectGoods($this->user_id, $goods_id);
        $this->json['result']['spec_goods_price'] = $spec_goods_price;
        $this->json['result']['store'] = $store;
        $this->json['result']['team'] = $team;
        $this->ajaxReturn($this->json);
    }


    public function ajaxTeamFound()
    {
        $goods_id = input('goods_id');
        $TeamActivity = new TeamActivity();
        $TeamFound = new TeamFound();
        $team_ids = $TeamActivity->where(['goods_id'=>$goods_id,'status'=>1,'is_lottery'=>0])->getField('team_id',true);
        //活动正常，抽奖团未开奖才获取商品拼团活动拼单
        if (count($team_ids) > 0) {
            $teamFounds = $TeamFound->with(['teamActivity'=>function($query){$query->field('team_id,time_limit');}])
                ->with(['order'=>function($query){$query->field('order_id,province,city,district,twon');}])
                ->where(['team_id' => ['IN',$team_ids], 'status' => 1])->order('found_id desc')->select();
            if($teamFounds) {
                $teamFounds = collection($teamFounds)->append(['surplus','order'=>'address_region'])->toArray();
            }
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => ['teamFounds' => $teamFounds,'server_time'=>time()]]);
        } else {
            $this->ajaxReturn(['status' => 0, 'msg' => '没有相关记录', 'result' => []]);
        }
    }

    /**
     * 下单
     */
    public function addOrder()
    {
        $goods_id = input('goods_id/d');
        $item_id = input('item_id/d',0);
        $team_id = db('team_goods_item')->field('team_id')->where('deleted',0)->where('item_id',$item_id)->find()['team_id'];
        $goods_num = input('goods_num/d');
        $found_id = input('found_id/d');//拼团id，有此ID表示是团员参团,没有表示团长开团
        if ($this->user_id == 0) {
            $this->ajaxReturn(['status' => -101, 'msg' => '购买拼团商品必须先登录', 'result' => '']);
        }
        if (empty($team_id)){
            $this->ajaxReturn(['status' => 0, 'msg' => '参数错误', 'result' => '']);
        }
        if(empty($goods_num)){
            $this->ajaxReturn(['status' => 0, 'msg' => '至少购买一份', 'result' => '']);
        }
        $team = new \app\common\logic\team\Team();
        $team->setUserById($this->user_id);
        $team->setTeamGoodsItemById($goods_id, $item_id);//设置拼团活动，拼团id  //通过goods_id,item_id查询出活动id，活动
        $team->setTeamFoundById($found_id);
        $team->setBuyNum($goods_num);
        try{
            $team->buy();
            $goods = $team->getTeamBuyGoods();
            $goodsList[0] = $goods;
            $pay = new Pay();
            $pay->setUserId($this->user_id);
            $pay->payGoodsList($goodsList);
            $placeOrder = new PlaceOrder($pay);
            $placeOrder->addTeamOrder($team);
            $order_list = $placeOrder->getOrderList();
            $this->ajaxReturn(['status' => 1, 'msg' => '提交拼团订单成功', 'result' => ['order_sn' => $order_list[0]['order_sn']]]);
        }catch (TpshopException $t){
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }
    }

    /**
     * 结算页
     * @return mixed
     */
    public function order()
    {
        $order_sn = input('order_sn/s',0);
        $address_id = input('address_id/d');
        if(empty($this->user_id)){
            $this->json['status'] = 0;
            $this->json['msg'] = '用户user_id不能为空';
            $this->ajaxReturn($this->json);
        }
        $Order = new Order();
        $OrderGoods = new OrderGoods();
        $order = $Order->field("mobile,province,city,district,twon,address,order_id,total_amount,order_amount,consignee,mobile,address,pay_status,order_status,"."
        coupon_price,store_id,goods_price,user_note,shipping_price,user_money,integral_money")->where(['order_sn'=>$order_sn,'user_id'=>$this->user_id])->find();
        if(empty($order)){
            $this->json['status'] = 0;
            $this->json['msg'] = '订单不存在或者已取消';
            $this->ajaxReturn($this->json);
        }
        if($order['province']){
            $userAddress['province'] = $order['province'];
            $userAddress['city'] = $order['city'];
            $userAddress['district'] = $order['district'];
            $userAddress['twon'] = $order['twon'];
            $userAddress['address'] = $order['address'];
            $userAddress['mobile'] = $order['mobile'];
            $userAddress['consignee'] = $order['consignee'];
        }else{
            if ($address_id) {
                $address_where = ['address_id' => $address_id];
            } else {
                $address_where = ["user_id" => $this->user_id];
            }
            $userAddress = Db::name('user_address')->where($address_where)->order(['is_default' => 'desc'])->find();
        }
        if(empty($userAddress)){
            $this->json['status'] = 0;
            $this->json['msg'] = '请先添加收货地址';
            $this->ajaxReturn($this->json);
        }else{
            $userAddress['total_address'] = getTotalAddress($userAddress['province'], $userAddress['city'], $userAddress['district'], $userAddress['twon'], $userAddress['address']);
        }
        $this->json['result']['addressList'] = $userAddress;
        $order_goods = $OrderGoods->field('goods_id,goods_name,spec_key_name,member_goods_price,goods_num')
            ->with(['goods'=>function($query){$query->field('goods_id,cat_id3,weight');}])->where(['order_id' => $order['order_id']])->find();
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){
            $this->json['status'] = 0;
            $this->json['msg'] = '该订单已支付成功';
            $this->ajaxReturn($this->json);
        }
        if($order['order_status'] == 3 ){   //订单已经取消
            $this->json['status'] = 0;
            $this->json['msg'] = '订单已取消';
            $this->ajaxReturn($this->json);
        }
        //订单没有使用过优惠券
        if($order['coupon_price'] <= 0){
            $couponLogic = new CouponLogic();
            $team = new \app\common\logic\team\Team();
            $userCouponList = $couponLogic->getUserAbleCouponList($this->user_id, [$order_goods['goods_id']], [$order_goods['goods']['cat_id3']]);//用户可用的优惠券列表
            $team->setOrder($order);
            $this->json['result']['userCartCouponList'] = $team->getCouponOrderAbleList($userCouponList);
        }
        $this->json['result']['order'] = $order;
        $this->json['result']['store'] = Db::name('store')->field('store_id,store_name')->where('store_id',$order['store_id'])->find();
        $this->json['result']['order_goods'] = $order_goods;
        $this->json['result']['userInfo'] = Db::name('users')->field("user_id,user_money,pay_points")->where('user_id',$this->user_id)->find();
        $this->ajaxReturn($this->json);
    }

    /**
     * 获取订单详情
     */
    public function getOrderInfo()
    {
        $order_id       = input('order_id/d');
        $goods_num      = input('goods_num/d');
        $coupon_id      = input('coupon_id/d');
        $address_id     = input('address_id/d');
        $user_money     = input('user_money/f');
        $pay_points     = input('pay_points/d');
        $pay_pwd        = trim(input("paypwd")); //  支付密码
        $user_note      = trim(input("user_note")); //  用户备注
        $act            = input('post.act','');
        if(empty($this->user_id)){
            $this->ajaxReturn(['status'=>0,'msg'=>'登录超时','result'=>['url'=>U("User/login")]]);
        }
        if(empty($order_id)){
            $this->ajaxReturn(['status'=>0,'msg'=>'参数错误','result'=>[]]);
        }
        try{
            $teamOrder = new TeamOrder($this->user_id, $order_id);
            $teamOrder->changNum($goods_num);//更改数量
            $teamOrder->pay();//获取订单结账信息
            $teamOrder->useUserAddressById($address_id);//设置配送地址
            $teamOrder->useCouponById($coupon_id);//使用优惠券
            $teamOrder->usePayPoints($pay_points);//使用积分
            $teamOrder->useUserMoney($user_money);//使用余额
            $order = $teamOrder->getOrder();//获取订单信息
            $orderGoods = $teamOrder->getOrderGoods();//获取订单商品信息
            if ($act == 'submit_order') {
                $teamOrder->setUserNote($user_note);//设置用户备注
                $teamOrder->setPayPsw($pay_pwd);//设置支付密码
                $teamOrder->submit();//确认订单
                $this->ajaxReturn(['status' => 1, 'msg' => '提交成功', 'result' => ['order_amount'=>$order['order_amount']]]);
            }else{
                $couponLogic = new CouponLogic();
                $team = new \app\common\logic\team\Team();
                $userCouponList = $couponLogic->getUserAbleCouponList($this->user_id, [$orderGoods['goods_id']], [$orderGoods['goods']['cat_id3']]);//用户可用的优惠券列表
                $team->setOrder($order);
                $userCartCouponList = $team->getCouponOrderAbleList($userCouponList);
                $result = [
                    'order'=>$order,
                    'order_goods'=>$orderGoods,
                    'couponList'=>$userCartCouponList
                ];
                $this->ajaxReturn(['status' => 1, 'msg' => '计算成功', 'result' => $result]);
            }
        }catch (TpshopException $t){
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }

    }

    /**
     * 拼团分享页
     * @return mixed
     */
    public function found()
    {
        $found_id = input('id');
        if (empty($found_id)) {
            $this->json['status'] = 0;
            $this->json['msg'] = '参数错误';
            $this->ajaxReturn($this->json);
        }
        $team = new \app\common\logic\team\Team();
        $team->setTeamFoundById($found_id);
        $teamFound = $team->getTeamFound();
        $teamFollow = $teamFound->teamFollow()->field('follow_id,follow_user_id,follow_user_nickname,follow_user_head_pic')->where('status','IN', [1,2])->select();
        $team = $teamFound->teamActivity;
        $this->json['result']['teamFollow'] = $teamFollow;
        $this->json['result']['store'] = $teamFound->store;
        $this->json['result']['team'] = $team->append(['virtual_sale_num'])->toArray();
        $this->json['result']['team']['store_count'] = $team->spec_goods_price ? $team->spec_goods_price->store_count : $team->goods->store_count;
        $this->json['result']['teamFound'] = $teamFound->append(['surplus','cut_price'])->toArray();
        $this->json['result']['server_time'] = time();
        $this->ajaxReturn($this->json);
    }

    public function ajaxGetMore()
    {
        $p = input('p/d', 0);
        $TeamActivity = new TeamActivity();
        $team = $TeamActivity->where(['status' => 1])->page($p, 4)->order(['is_recommend' => 'desc', 'sort' => 'desc'])->select();
        if (empty($team)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '已显示完所有记录']);
        } else {
            $result = collection($team)->append(['virtual_sale_num'])->toArray();
            $this->ajaxReturn(['status' => 1, 'msg' => '', 'result' => $result]);
        }
    }

    public function lottery(){
        $team_id = input('team_id/d',0);
        $team_lottery = Db::name('team_lottery')->where('team_id',$team_id)->select();
        $TeamActivity = new TeamActivity();
        $team = $TeamActivity->with('specGoodsPrice,goods')->where('team_id',$team_id)->find();
        $this->json['result']['team'] = $team;
        $this->json['result']['team_lottery'] = $team_lottery;
        $this->ajaxReturn($this->json);
    }

}