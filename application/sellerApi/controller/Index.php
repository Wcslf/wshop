<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/30
 * Time: 14:12
 */
namespace app\sellerApi\controller;

use app\seller\logic\AdminLogic;
use app\common\model\StoreMsg;
use think\Db;
use think\Loader;
use think\Page;

class Index extends Base
{
    /**
     * 析构流函数
     */
    public function  __construct() {
        parent::__construct();
        $this->AdminLogic = new AdminLogic();
    }

    /**
     * 首页
     */
    public function index(){
        $today_start_time = strtotime(date('Y-m-d',time()));
        $today_where = [
            'store_id'=>STORE_ID,
            'confirm_time'=>['between',[$today_start_time,time()]],
        ]; //今日收益查询条件
        $data['todayOrder'] = Db::name('order')->where($today_where)->count(); //今日订单数
        $today_where['order_status']=2;
        $data['todayEarnings'] = Db::name('order')->where($today_where)->sum('goods_price') ? :0; //今日收益

        //昨日的
        $yesterday_start_time =  $today_start_time-86400;
        $yesterday_where = [
            'store_id'=>STORE_ID,
            'confirm_time'=>['between',[$yesterday_start_time,$today_start_time]],
        ];
        $data['yesterdayOrder'] = Db::name('order')->where($yesterday_where)->count(); //昨日订单数
        $yesterday['order_status']=2;
        $data['yesterdayEarnings'] = Db::name('order')->where($yesterday_where)->sum('goods_price') ? :0; //昨日收益
        $data['message'] = Db::name('store_msg')->where(['store_id'=>STORE_ID,'open'=>0])->count();
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功','result'=>$data]);
    }


    /**
     * 店铺消息
     */
    public function storeMsg(){
        $where = "store_id=" . STORE_ID;
        $data['msgCount'] = M('store_msg')->where($where)->count();
        $Page = new Page($data['count'], 10);
        $data['msgList'] = M('store_msg')->where($where)->order('sm_id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $data['unread']  = M('store_msg')->where('store_id', STORE_ID)->where('open', 0)->order('sm_id DESC')->count();
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功','result'=>$data]);
    }

    /**
     * 店铺消息详情
     */
    public function storeMsgInfo(){
        $sm_id = I('id');
        empty($sm_id) && $this->ajaxReturn(['status' => 0, 'msg' => '非法操作',]);
        $StoreMsg = new StoreMsg();
        $data = $StoreMsg::get(['store_id' =>STORE_ID,'sm_id'=>$sm_id]);
        $StoreMsg::update(['open' =>1],['sm_id'=>$sm_id],'open');
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功','result'=>$data]);
    }
    
}