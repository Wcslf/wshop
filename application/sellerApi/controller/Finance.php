<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 *
 *
 * Date: 2016-03-09
 */

namespace app\sellerApi\controller;

use app\seller\logic\FinanceLogic;
use app\seller\logic\OrderLogic;
use app\seller\logic\ReturnGoodsLogic;
use app\common\model\Goods;
use think\Db;
use think\Page;

class Finance extends Base
{
    /*
     * 初始化操作
     */
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     *  数据统计
     */
    public function index()
    {
        $orderLogic = new OrderLogic();
        $returnGoodsLogic = new ReturnGoodsLogic();
        $yesterday = strtotime('yesterday');   //昨天
        $today = strtotime('today');           //今天
        $month = strtotime(date('Y-m-d', strtotime('-30 day')));    //30天前
        $data['sale_goods'] = Db::name('goods')->where(['store_id' => STORE_ID,'is_on_sale'=>1])->count();  //在售商品
        //订单
        $yesterdayOrder  = $orderLogic->getOrderPaidAmount(STORE_ID,$yesterday,$today); //昨日支付
        $allOrder        = $orderLogic->getOrderPaidAmount(STORE_ID);                   //全部支付情况
        $yesterdayRefund = $returnGoodsLogic->getRefundAmount(STORE_ID,$yesterday,$today);  //昨日退货
        $monthRefund     = $returnGoodsLogic->getRefundAmount(STORE_ID,$month,$today);      //昨日退货
        $allRefund       = $returnGoodsLogic->getRefundAmount(STORE_ID);                    //全部退货情况
        $info =[
            'yester_paid_amount'        =>$yesterdayOrder['paid_order_sum'],     //昨日支付订单数
            'yester_paid_money'         =>$yesterdayOrder['paid_order_money'],   //昨日支付金额
            'all_paid_amount'           =>$allOrder['paid_order_sum'],           //全部支付数量
            'all_paid_money'            =>$allOrder['paid_order_money'],         //全部支付金额
            'yester_refund_amount'        =>$yesterdayRefund['refund_sum'],          //昨日退货订单数量
            'yester_refund_money'         =>$yesterdayRefund['refund_money'],        //昨日退货金额
            'yester_refund_goods_amount'  =>$yesterdayRefund['refund_goods_num'],    //昨日退货商品
            'month_refund_amount'         =>$monthRefund['refund_sum'],           //30天退货数量
            'month_refund_goods_amount'   =>$monthRefund['refund_goods_num'],     //30天退货商品数量
            'all_refund_amount'           =>$allRefund['refund_sum'],         //全部退货数量
            'all_refund_money'            =>$allRefund['refund_money'],       //全部退货金额
            'all_refund_goods_amount'     =>$allRefund['refund_goods_num'],   //全部退货商品数量
        ];
        $data['order_goods_amount'] = Db::name('order')->alias('o')->join('order_goods og','og.order_id=o.order_id')
            ->where(['o.store_id'=>STORE_ID,'o.deleted'=>0])->whereNotIn('o.order_status','3,5')->sum('og.goods_num');  //全部有效订单商品
        $data['refund_goods_ratio']  = round(($info['all_refund_goods_amount']/$data['order_goods_amount'])*100,2);   //退货比例：全部退货商品数量/全部有效订单商品
        $data = array_merge($data,$info);
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功！','restut'=>$data]);
    }

    /**
     * 数据统计—查询订单日期
     */
    public function selectOrder(){
        $start_time = input('keyword','');
        $end_time = $start_time+24*60*60;
        empty($start_time) && $this->ajaxReturn(['status' => -1, 'msg' => '请输入查询条件']);
        $orderLogic = new OrderLogic();
        $returnGoodsLogic = new ReturnGoodsLogic();
        $order_paid_amount =  $orderLogic->getOrderPaidAmount(STORE_ID,$start_time,$end_time);
        $refund_amount = $returnGoodsLogic->getRefundAmount(STORE_ID,$start_time,$end_time);
        $data =  [
            'paid_amount' => $order_paid_amount['paid_order_sum'],           //全部支付订单数量
            'paid_money'  => $order_paid_amount['paid_order_money'],         //全部支付金额
            'refund_amount'           =>$refund_amount['refund_sum'],         //全部退货订单数量
            'refund_money'            =>$refund_amount['refund_money'],       //全部退货金额
            'refund_goods_amount'     =>$refund_amount['refund_goods_num'],   //全部退货商品数量
        ];
        $order_goods_amount = Db::name('order')->alias('o')->join('order_goods og','og.order_id=o.order_id')
            ->where(['o.store_id'=>STORE_ID,'o.deleted'=>0])->whereNotIn('o.order_status','3,5')->sum('og.goods_num');  //全部有效订单商品
        $data['refund_goods_ratio'] = round(($data['refund_goods_amount']/$order_goods_amount)*100,2);   //退货比例：全部退货商品数量/全部有效订单商品
        $data['select_time'] = date('Y年m月d日',$start_time);
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功！','restut'=>$data]);
    }

    /**
     * 数据统计—查询商品名称
     */
    public function selectGoods(){
        $keyword = input('post.keyword','');
        empty($keyword) && $this->ajaxReturn(['status' => -1, 'msg' => '请输入查询条件']);
        $goodsModel = new Goods();
        $FinanceLogic = new FinanceLogic();
        $where = ['store_id'=>STORE_ID,'goods_name'=>['like',"%$keyword%"]];
        $count  = $goodsModel->where($where)->count();
        $page = new Page($count,10);
        $goodsObj = $goodsModel->field('goods_id,goods_name')->where($where)->limit($page->listRows,$page->firstRow)->select();   //商品信息
        if ($goodsObj){
            $goods = collection($goodsObj)->toArray();
            $data = $FinanceLogic->getGoodsReturnInfo($goods);
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功！','restut'=>$data]);
        }
        $this->ajaxReturn(['status' => -1, 'msg' => '未找到对应数据！']);
    }

    /**
     *  今日结算
     */
    public function todayStatis()
    {
        $data['today_total_amount'] = Db::name('order')->where(['store_id' => STORE_ID,'order_status'=>['lt',3],'pay_status'=>1])
            ->whereTime('add_time','today')->sum('total_amount-shipping_price-coupon_price-order_prom_amount');  //今日销售额，订单总价减去物流，减去各种优惠
        $data['order_statis'] = Db::name('order')->where(['store_id' => STORE_ID,'order_statis_id'=>['gt',0]])->count();  //已结算订单
        $data['not_refund_money'] = Db::name('return_goods')->where(['store_id' => STORE_ID,'type'=>['lt',2],'refund_time'=>['elt',0]])->count();  //全部未退款订单
        $data['today_apply'] = Db::name('return_goods')->where(['store_id' => STORE_ID])->whereTime('addtime','today')->count();  //今日申请的售后
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功！','restut'=>$data]);
    }
}