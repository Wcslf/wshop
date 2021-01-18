<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/31
 * Time: 9:21
 */
namespace app\sellerApi\controller;

use app\seller\logic\OrderLogic;
use think\Db;
use think\Page;
class Order extends Base
{
    /**
     * 析构流函数
     */
    public function  __construct() {
        parent::__construct();
        $this->OrderLogic = new OrderLogic();
        $this->orderModel = new \app\common\model\Order();
    }

    /**
     * 普通订单列表
     */
    public function orderList(){
        $order_list = DB::name('order')->where(['deleted'=> 0,'store_id'=>STORE_ID,'order_status'=>['lt',2]])->select();    //进行中的订单
        $wait_shipping = $wait_pay= $already_shipping = 0;
        $order_sum = count($order_list);
        if ($order_sum) {
            foreach ($order_list as $v) {
                if ($v['order_status'] == 1 && $v['shipping_status'] == 0 ) {  //待发货
                    $wait_shipping++;
                }
                if ($v['pay_status'] == 0 && ($v['order_status'] != 3 && $v['order_status'] != 5)) {
                    $wait_pay++;
                }
                if ($v['shipping_status'] > 0 && $v['order_status'] == 1) {
                    $already_shipping++;
                }
            }
        }
        $data['count']=[
            'all_order'         => $order_sum,          //全部订单
            'wait_shipping'     => $wait_shipping,      //待发货
            'wait_pay'          => $wait_pay,           //未支付
            'already_shipping'  => $already_shipping,   //已发货
        ];
        // 搜索条件 STORE_ID

        $status = I('order_status/d',0);
        $pay_status = I('pay_status');
        $order_sn =trim(I('order_sn'));
        $shipping_status =I('shipping_status');
        $order_sn ? $condition['order_sn|master_order_sn'] = $order_sn : false;               //订单号
        switch ($status){
            case 1:$condition['order_status'] = ['lt',2]     ;break;    //全部进行中
            case 2:$condition['order_status'] = ['in','2,4'] ;break;    //已完成
            case 3:$condition['order_status'] = 5            ;break;    //已关闭
            default : break;
        }
        if ($pay_status){
            $condition['pay_status']=$pay_status;//支付状态
        }

        switch ($shipping_status){
            case $shipping_status<2:$condition['shipping_status'] =$shipping_status;break;    //全部进行中
            default : break;
        }
        $condition['store_id']=STORE_ID;
        $sort_order = I('order_by', 'add_time') . ' ' . I('sort','DESC');
        $condition['prom_type'] = array('lt',5);
        $orderModel = new \app\common\model\Order();
        $count = $orderModel->where($condition)->count();
        $Page = new Page($count, 20);
        $order_list_obj = $orderModel->withCount('OrderGoods')->where($condition)->limit("{$Page->firstRow},{$Page->listRows}")->order($sort_order)->select();
        $data['order_list']=[];
        if($order_list_obj){
            //转为数组，并获取订单状态，订单状态显示按钮，订单商品
          $data['order_list']=collection($order_list_obj)->append(['show_status','shipping_address','order_goods'])->toArray();
        }
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' =>$data]);
    }

    /**
     * 订单详情
     * @return mixed
     */
    public function detail()
    {
        $order_id = I('order_id/d',0);
        $order_list_obj = $this->orderModel->where(['order_id'=>$order_id])->find();
        if(!$order_list_obj){
            $this->ajaxReturn(['status' => 1, 'msg' => '该订单不存在或没有权利查看']);
        }
        $order = $order_list_obj->append(['show_status','shipping_address','order_goods'])->toArray();
        if($order['is_comment'] == 1){
            $comment_time = Db::name('comment')->where('order_id' , $order['order_id'])->order('comment_id desc')->value('add_time');
            $order['comment_time']=$comment_time; //查询评论时
        }
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' =>$order]);
    }

    /**
     * 查看订单物流
     */
    public function express()
    {
        $is_json = I('is_json', 0);
        $order_id = I('get.order_id/d', 0);
        $data = Db::name('order')->field('order_id,order_sn,master_order_sn,shipping_code,shipping_name')->where("order_id" , $order_id)->find();
        if(empty($data) || empty($order_id)){
            $this->ajaxReturn(['status' => 1, 'msg' => '没有获取到订单信息']);
        }
        $delivery = Db::name('delivery_doc')->where("order_id" , $order_id)->getField('shipping_code,invoice_no');
        if ($is_json) {
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $delivery]);
        }
        $data['delivery']=$delivery;
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' =>$data]);
    }

    /**
     * 生成发货单
     */
    public function deliveryHandle()
    {
        if(IS_POST){
            $orderLogic = new OrderLogic();
            $data = I('post.');
            $res = $orderLogic->deliveryHandle($data, STORE_ID);
            if ($res) {
                $this->ajaxReturn(['status'=>1,'msg'=>'操作成功', 'url'=>U('Seller/Order/index')]);
            } else {
                $this->ajaxReturn(['status'=>-1,'msg'=>'操作失败', 'url'=>U('Order/delivery_info', array('order_id' => $data['order_id']))]);
            }
        }else{
            $this->ajaxReturn(['status' =>0, 'msg' =>'请求方式错误',]);
        }
    }
}