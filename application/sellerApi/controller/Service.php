<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * 评论咨询投诉管理
 * @author soubao 当燃
 * @Date: 2017-03-20
 */

namespace app\sellerApi\controller;

use \app\common\model\ReturnGoods;
use app\seller\logic\OrderLogic;
use think\Db;
use think\Page;

class Service extends Base
{
	/**
	 * 析构流函数
	 */
	public function  __construct() {
		parent::__construct();
		$this->returnGoodsModel = new ReturnGoods();
	}
    /**
     * 售后列表
     */
    public function return_list(){
    	//搜索条件
    	$where['store_id'] = STORE_ID;
        I('type/d') ? $where['type'] = I('type/d'):false;
		I('status')   ? $where['status'] = trim(I('status')) : false;    //状态
		I('order_sn') ? $where['order_sn'] = trim(I('order_sn')) : false;  //订单号
    	$count = $this->returnGoodsModel->where($where)->count();
    	$Page  = new Page($count,20);
    	$returnGoodsObj = $this->returnGoodsModel->where($where)->order("id desc")->limit("{$Page->firstRow},{$Page->listRows}")->select();
        if ($returnGoodsObj) {
            $data = collection($returnGoodsObj)->append(['service_type', 'service_status'])->toArray();
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $data]);
        }
        $this->ajaxReturn(['status' => -1, 'msg' => '获取失败！']);
    }

    /**
     * 售后详情
     */
    public function return_info()
    {
        $id = I('id/d');
        $orderLogic = new OrderLogic();
        $returnGoodsObj = $this->returnGoodsModel->get(['id'=>$id,'store_id'=>STORE_ID]);
        empty($returnGoodsObj) && $this->ajaxReturn(['status' => -1, 'msg' => '参数错误']);
        $return_goods =$returnGoodsObj->append(['service_type','service_status'])->toArray();  //获取对应状态，售后类型
        if($return_goods['imgs']) $return_goods['imgs'] = explode(',', $return_goods['imgs']);
        if($return_goods['delivery']) $return_goods['delivery'] = unserialize($return_goods['delivery']);
        if($return_goods['seller_delivery']) $return_goods['seller_delivery'] = unserialize($return_goods['seller_delivery']);
        $user = get_user_info($return_goods['user_id']);  //用户信息
        $select_year = getTabByOrderId($return_goods['order_id']);  //获取订单对应表
        $data['order_goods'] = M('order_goods'.$select_year)->where(['rec_id'=>$return_goods['rec_id']])->find();  //订单商品
        $data = M('order'.$select_year)->where(array('order_id'=>$return_goods['order_id']))->find();
        $address = $orderLogic->getAddressName($data['province'],$data['city'],$data['district']);
        $data['address2'] = $address.$data['address'];  //收货地址
        $data['user']=$user;
        $data['return_goods']=$return_goods;// 退换货申请信息
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功','result'=>$data]);
    }

    /**
     * 换货操作
     */
    public function updateReturnInfo()
    {
        if(IS_POST) {
            $post_data = I('post.');
            $return_goods = M('return_goods')->where(array('id' => $post_data['id'], 'store_id' => STORE_ID))->find();
            $select_year = getTabByOrderId($return_goods['order_id']);
            empty($return_goods) && $this->ajaxReturn(['status' => -1, 'msg' => '参数错误']);
            if ($post_data['status'] == 1 || $post_data['status'] == -1) {
                $post_data['checktime'] = time();//审核换货申请
                if ($return_goods['is_receive'] == 0 && $post_data['status'] != -1) $post_data['status'] = 3;//未发货商品无需确认收货
            } else {
                $post_data['status'] = 4;//处理发货
                $post_data['seller_delivery']['express_time'] = date('Y-m-d H:i:s');
                $post_data['seller_delivery'] = serialize($post_data['seller_delivery']);
                M('order_goods' . $select_year)->where('rec_id', $return_goods['rec_id'])->save(array('is_send' => 2));
            }
            M('return_goods')->where(array('id' => $post_data['id'], 'store_id' => STORE_ID))->save($post_data);
            $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
        }else{
            $this->ajaxReturn(['status' => 0, 'msg' => '请求方式错误']);
        }
    }

    /**
     * 退货操作
     */
    public function updateRefundInfo(){
        if(IS_POST){
            $post_data = I('post.');
            $return_goods = M('return_goods')->where(array('id'=>$post_data['id'],'store_id'=>STORE_ID))->find();
            empty($return_goods) && $this->ajaxReturn(['status' => -1, 'msg' => '参数有误']);
            $post_data['checktime'] = time();
            if($post_data['status'] == 1){
                if($return_goods['is_receive'] == 0) $post_data['status'] = 3;//未发货商品无需确认收货
            }
            if($post_data['refund_money'] != $return_goods['refund_money']){
                $post_data['gap'] = $return_goods['refund_money'] - $post_data['refund_money'];//退款差额
            }
            M('return_goods')->where(array('id'=>$post_data['id'],'store_id'=>STORE_ID))->save($post_data);
            $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
        }else{
            $this->ajaxReturn(['status' => 0, 'msg' => '请求方式错误']);
        }
    }

}