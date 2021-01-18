<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * Author: 二月鸟飞
 * Date: 2016-03-19
 */

namespace app\common\logic;

use app\common\util\TpshopException;
use app\common\logic\wechat\WechatUtil;
use think\Cache;
use think\Page;
use think\Db;
use think\Model;
use app\common\model\Order;
use app\common\model\ReturnGoods;

/**
 * Class orderLogic
 * @package Common\Logic
 */
class OrderLogic extends Model
{
    protected $user_id=0;
	protected $action;
	protected $cartList;

    /**
     * 设置用户ID
     * @param $user_id
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }
	public function setAction($action){
		$this->action = $action;
	}
	public function setCartList($cartList){
		$this->cartList = $cartList;
	}
	//取消订单
	public function cancel_order($user_id,$order_id){
		$order = M('order')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->find();
		//检查是否未支付订单 已支付联系客服处理退款
		if(empty($order))
			return array('status'=>-1,'msg'=>'订单不存在','result'=>'');
		//检查是否未支付的订单
		if($order['pay_status'] > 0 || $order['order_status'] > 0)
			return array('status'=>-1,'msg'=>'支付状态或订单状态不允许','result'=>'');
		//获取记录表信息
		//$log = M('account_log')->where(array('order_id'=>$order_id))->find();
		if($order['prom_type'] == 6){
			$team_follow = Db::name('team_follow')->where(['order_id'=>$order_id])->find();
			if($team_follow){
				$team_found_queue = Cache::get('team_found_queue');
				$team_found_queue[$team_follow['found_id']] = $team_found_queue[$team_follow['found_id']] + 1;
				Cache::set('team_found_queue', $team_found_queue);
			}
		}
		//有余额支付的情况
		if($order['user_money'] > 0 || $order['integral'] > 0){
			accountLog($user_id, $order['user_money'], $order['integral'], "订单取消，退回{$order['user_money']}元,{$order['integral']}积分", 0, $order['order_id'], $order['order_sn']);
		}

		if($order['coupon_price'] >0){
			$res = array('use_time'=>0,'status'=>0,'order_id'=>0);
			M('coupon_list')->where(array('order_id'=>$order_id,'uid'=>$user_id))->save($res);
		}
		$row = M('order')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->save(array('order_status'=>3,'user_note'=>'用户取消订单'));
		$reduce = tpCache('shopping.reduce');
		if($reduce == 1 || empty($reduce)){
			$this->alterReturnGoodsInventory($order);
		}
		$data['order_id'] = $order_id;
		$data['action_user'] = $user_id;
		$data['action_note'] = '您取消了订单';
		$data['order_status'] = 3;
		$data['pay_status'] = $order['pay_status'];
		$data['shipping_status'] = $order['shipping_status'];
		$data['log_time'] = time();
		$data['status_desc'] = '用户取消订单';
		M('order_action')->add($data);//订单操作记录

		if(!$row) return array('status'=>-1,'msg'=>'操作失败','result'=>'');
        Db::name('rebate_log')->where(['order_id'=>$order_id])->save(['status'=>4,'remark' => '订单取消，取消分成']);
		return array('status'=>1,'msg'=>'操作成功','result'=>'');
	}

	public function check_dispute_order($order_id,$complain_id,$user_id){
		$res = array('flag'=>1,'data'=>'');
		$complain_log = M('complain')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->find();
		if($complain_log){
			$res = array('flag'=>2,'msg'=>"该订单已经投诉过，请在用户中心投诉管理查看处理进度",'data'=>'');
		}else{
			$order = M('order')->where(array('order_id'=>$order_id))->find();
			if($order['pay_status'] == 0){
				$res = array('flag'=>2,'msg'=>"该订单并未付款，无法进行投诉交易服务。",'data'=>'');
			}elseif($complain_id == 1 && $order['shipping_status'] == 1){
				//配送投诉，如果卖家已经发货，所以不能提交
				$res = array('flag'=>2,'data'=>'','msg'=>"该纠纷类型暂无法提交，可能是您的订单已完成，或您已申请过同类型的纠纷单，建议您优先联系卖家客服处理。前往帮助中心了解<a href=''>纠纷发起规则</a>。");
			}elseif(in_array($complain_id,array(2,3,7,8,9,10))){
				//查看是否有申请退货退款，换货维修售后服务
				$return_goods = M('return_goods')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->select();
				$headhtml = '<div class="choosetyp6"><span style="width:20%">是否选择</span><span style="width:20%">售后服务单</span><span style="width:40%">对应商品</span><span style="width:20%">售后服务单状态</span></div>';
				$mismatch = $headhtml.'<div class="applyrestore"><p class="tit">如果没有满足条件的售后服务单</p><p class="mali">如果你遇到售后类型问题，可以先去申请返修退换货；倘若在售后过程中仍有问题，可再来申请交易纠纷</p><a href="'.U('Order/return_goods_index').'">申请返修退换货</a></div>';
				if(empty($return_goods)){
					$res = array('flag'=>2,'data'=> $mismatch,'msg'=>"该纠纷类型暂无法提交，可能是该订单下没有审核不通过的退货服务单，建议您选择其他纠纷类型，或联系卖家客服处理。前往帮助中心了解<a href=''>纠纷发起规则</a>。");
				}else{
					$state = C('RETURN_STATUS');
					$html = $headhtml;
					foreach ($return_goods as $k=>$val){
						$html .= '<div class="choosetyp6">';
						$goods_url = U('Goods/goodsInfo',array('id'=>$val['goods_id']));
						$return_url = U('Order/return_goods_info',array('id'=>$val['id']));
						$goods_name = M('order_goods')->where(array('order_id'=>$order_id,'goods_id'=>$val['goods_id']))->getField('goods_name');
						if($k == 0){
							$html .= '<span style="width:20%"><input type="radio" checked name="order_goods_id" value="'.$val['goods_id'].'">&nbsp;&nbsp;'.$val['id'].'</span>';
						}else{
							$html .= '<span style="width:20%"><input type="radio" name="order_goods_id" value="'.$val['goods_id'].'">&nbsp;&nbsp;'.$val['id'].'</span>';
						}
						$html .= '<span style="width:20%"><a href="'.$return_url.'" target="_blank"><img src="'.goods_thum_images($val['goods_id'],60,60).'" height="60" title=""></a></span>';
						$html .= '<span style="width:40%"><a class="shop_name_ir" href="'.$goods_url.'" target="_blank">'.$goods_name.'</a></span>';
						$html .= '<span style="width:20%">'.$state[$val['status']].'</span></div>';
					}
					
					$res = array('flag'=>1,'data'=>$html);//如果售后服务单有多个，那就让用户选择投诉
					if(count($return_goods) == 1){
						$res = array('flag'=>1,'data' => $html);
						$return_goods = $return_goods[0];
						if($return_goods['status'] == -2){
							$res = array('flag'=>2,'msg'=>"该服务单会员自己选择了取消，建议您优先联系卖家客服解决。前往帮助中心了解纠纷发起规则。",'data'=>'');
						}
						if($return_goods['status'] == -1){
							$res = array('flag'=>1,'data'=> $html);
						}
						if($return_goods['status'] == 0){
							if(($return_goods['addtime']+48*3600)>time()){
								$res = array('flag'=>2,'msg'=>'该纠纷类型暂无法提交，您的该类型服务单还在等待卖家审核中');
							}
						}
						if($return_goods['status']>=1){
							if($complain_id == 10){
								if(empty($return_goods['delivery'])){
									$res = array('flag'=>2,'data'=>'','msg'=>"该纠纷类型暂无法提交，可能是您还未在服务单中上传物流信息，或服务单已处理完成，建议您优先联系卖家客服解决。前往帮助中心了解纠纷发起规则。");
								}elseif(($return_goods['receivetime']+48*3600)>time()){
									$res = array('flag'=>2,'data'=>'','msg'=>"该服务单还在等待卖家处理，并未超过48小时，建议您优先联系卖家客服解决。前往帮助中心了解纠纷发起规则。");
								}
							}
							if($complain_id == 9 && $return_goods['status']<4){
								$res = array('flag'=>2,'data'=>'','msg'=>"该服务单还在等待卖家处理，并未完成，建议您优先联系卖家客服解决。前往帮助中心了解纠纷发起规则。");
							}
						}
						//找不到退货退款服务单
						if($complain_id<4 && $return_goods['type']==1){
							$res = array('flag'=>2,'data'=>$mismatch,'msg'=>"该纠纷类型暂无法提交，可能是该订单下没有审核不通过的此类服务单，建议您选择其他类型，或联系卖家客服解决。前往帮助中心了解纠纷发起规则");
						}
						//找不到换货维修服务单
						if($complain_id>6 && $return_goods['type']==0){
							$res = array('flag'=>2,'data'=>$mismatch,'msg'=>"该纠纷类型暂无法提交，可能是该订单下没有审核不通过的此类服务单，建议您选择其他类型，或联系卖家客服解决。前往帮助中心了解纠纷发起规则");
						}
					}
				}
			}
		}
		return $res;
	}

	/**
	 * 获取订单 order_sn
	 * @return string
	 */
	public function get_order_sn()
	{
		$order_sn = null;
		// 保证不会有重复订单号存在
		while(true){
			$order_sn = date('YmdHis').rand(1000,9999); // 订单编号			
			$order_sn_count = M('order')->where("order_sn = '$order_sn' or master_order_sn = '$order_sn'")->count();
			if($order_sn_count == 0)
				break;
		}
		return $order_sn;
	}

    /**
     * 获取退货列表
     * @param type $keywords
     * @param type $addtime
     * @param type $status
     * @return type
     */
    public function getReturnGoodsList($keywords, $addtime, $status,$user_id)
	{
		if($keywords){
            $where['r.order_sn|o.goods_name'] = array('like',"%$keywords%");
    	}
    	if($status === '0' || !empty($status)){
            $where['r.status'] = $status;
    	}
    	if($addtime == 1){
            $where['r.addtime'] = array('gt',(time()-90*24*3600));
    	}
    	if($addtime == 2){
            $where['r.addtime'] = array('lt',(time()-90*24*3600));
    	}
    	$where['r.user_id'] = $user_id;
		$returnGoodsModel =new ReturnGoods();
        $count = $returnGoodsModel->alias('r')->field('r.*,o.goods_name')
                ->join('__ORDER_GOODS__ o', 'r.rec_id = o.rec_id AND o.deleted = 0')->where($where)->count();
    	$page = new Page($count,10);
    	$list = $returnGoodsModel->alias('r')->field('r.*,o.goods_name')
            ->join('__ORDER_GOODS__ o', 'r.rec_id = o.rec_id AND o.deleted = 0')->where($where)->order("id desc")->limit($page->firstRow, $page->listRows)->select();
    	$goods_id_arr = get_arr_column($list, 'goods_id');
    	if(!empty($goods_id_arr)) {
            $goodsList = M('goods')->where("goods_id in (".  implode(',',$goods_id_arr).")")->getField('goods_id,goods_name');
        }
        
        return [
            'goodsList' => $goodsList,
            'return_list' => $list,
            'page' => $page->show()
        ];
	}

    /**
     * 获取可申请退换货订单商品
     * @param $sale_t
     * @param $keywords
     * @param $user_id
     * @return array
     */
    public function getReturnGoodsIndex($sale_t, $keywords, $user_id)
    {
        if($keywords){
            $condition['order_sn'] = $keywords;
        }

		$auto_service_date = tpCache('shopping.auto_service_date'); //申请售后时间段
		$confirm_time = strtotime ( "-$auto_service_date day" );
    	$condition['user_id'] = $user_id;
    	$condition['pay_status'] = 1;
    	$condition['order_status'] = ['in',[1,2,4]];
    	$condition['shipping_status'] = 1;
    	$condition['deleted'] = 0;
    	$condition['confirm_time'] = ['gt',$confirm_time];

    	$count = M('order')->where($condition)->count();
    	$Page  = new \think\Page($count,10);
    	$show = $Page->show();
    	$order_list = Db::name('order')->where($condition)->order('order_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
    	foreach ($order_list as $k=>$v) {
            $data = M('order_goods')->where(['order_id'=>$v['order_id'],'is_send'=>['lt',2]])->select();  //订单没完成售后的商品
            if(!empty($data)){
                foreach ($data as $gk => $gv) {
                    $return_goods = M('return_goods')->where(['rec_id' => $gv['rec_id']])->count();  //商品有售后的
                    if ($return_goods > 0) {
                        unset($gv); //去除这个商品
                    } else {
                        $order_list[$k]['goods_list'][] = $gv;
                    }
                }
            }
            if(empty($data) || empty($order_list[$k]['goods_list'])){
                unset($order_list[$k]);  //除去没有可申请的订单
            }
    	}
        $store_id_list = get_arr_column($order_list, 'store_id');
        if(!empty($store_id_list))
            $store_list = M('store')->where("store_id in (".  implode(',', $store_id_list).")")->getField('store_id,store_name,store_qq');
        return [
            'order_list' => $order_list,
            'store_list'=>$store_list,
            'page' => $show
        ];
    }

    /**
     * 上传退换货图片，兼容小程序
     * @return array
     */
    public function uploadReturnGoodsImg()
    {
        $return_imgs = '';
        if ($_FILES['return_imgs']['tmp_name']) {
			$files = request()->file("return_imgs");
            if (is_object($files)) {
                $files = [$files]; //可能是一张图片，小程序情况
            }
			$image_upload_limit_size = config('image_upload_limit_size');
			$validate = ['size'=>$image_upload_limit_size,'ext'=>'jpg,png,gif,jpeg'];
			$dir = UPLOAD_PATH.'return_goods/';
			if (!($_exists = file_exists($dir))){
				$isMk = mkdir($dir);
			}
			$parentDir = date('Ymd');
			foreach($files as $key => $file){
				$info = $file->rule($parentDir)->validate($validate)->move($dir, true);
				if($info){
					$filename = $info->getFilename();
					$new_name = '/'.$dir.$parentDir.'/'.$filename;
					$return_imgs[]= $new_name;
				}else{
                    return ['status' => -1, 'msg' => $file->getError()];//上传错误提示错误信息
				}
			}
			if (!empty($return_imgs)) {
				$return_imgs = implode(',', $return_imgs);// 上传的图片文件
			}
		}
        
        return ['status' => 1, 'msg' => '操作成功', 'result' => $return_imgs];
    }

	/**
	 * 申请售后
	 * @param $rec_id
	 * @param $order
	 * @return array
	 */
    public function addReturnGoods($rec_id,$order)
    {
        $data = I('post.');
        $auto_service_date = tpCache('shopping.auto_service_date');
		$confirm_time = strtotime ( "-$auto_service_date day" );
        if ((time() - $order['confirm_time']) > $confirm_time && !empty($order['confirm_time'])) {
            return ['result'=>-1,'msg'=>'已经超过' . $auto_service_date . "天内退货时间"];
        }
        
        $img = $this->uploadReturnGoodsImg();
        if ($img['status'] !== 1) {
            return $img;
        }
        $data['imgs'] = $img['result'] ?: ($data['imgs'] ?: ''); //兼容小程序，多传imgs

        $data['addtime'] = time();
        $data['user_id'] = $order['user_id'];
        $data['store_id'] = $order['store_id'];
        $order_goods = M('order_goods')->where(array('rec_id'=>$rec_id))->find();
        if($data['type'] < 2){
            $useApplyReturnMoney = $order_goods['final_price']*$data['goods_num'];    //要退的总价 商品购买单价*申请数量
            $userExpenditureMoney = $order['goods_price']-$order['order_prom_amount']-$order['coupon_price'];    //订单实际，用户实际使用金额
            $rate = round($useApplyReturnMoney/$userExpenditureMoney,8);
            $data['refund_integral'] = floor($rate*$order['integral']);//该退积分支付
            $integralDeductionMoney = $data['refund_integral']/tpCache('shopping.point_rate') ;  //积分抵了多少钱，要扣掉
            if($order['order_amount']>0){
				$order_amount = $order['order_amount']+$order['paid_money'];   //三方支付总额，预售要退定金
                if($order_amount>$order['shipping_price']){
                    $data['refund_money'] = round($rate*($order_amount - $order['shipping_price']),2); //退款金额
                    $data['refund_deposit'] = $rate*$order['user_money'];
                }else{
                    $data['refund_deposit'] = round($rate*($order['user_money'] - $order['shipping_price']+$order['paid_money'])-$integralDeductionMoney,2);//该退余额支付部分
                }
            }else{
                $data['refund_deposit'] = round($useApplyReturnMoney-$integralDeductionMoney,2);//该退余额支付部分
            }
        }
        if(!empty($data['id'])){
        	$result = M('return_goods')->where(array('id'=>$data['id']))->save($data);
        }else{
        	$result = M('return_goods')->add($data);
        }
        
        if($result){
			// 向店家发送 售后提醒
			$message_store = new \app\common\logic\MessageStoreLogic();
			$message_store->setOrder($order, 'return')->sendMessage();
            return ['status'=>1,'msg'=>'申请成功'];
        }
        return ['status'=>-1,'msg'=>'申请失败'];
    }
    
    /**
     * 删除订单
     * @param type $order_id
     * @return type
     */
    public function delOrder($order_id)
    {
        $validate = validate('order');
        if (!$validate->scene('del')->check(['order_id' => $order_id])) {
            return ['status' => 0, 'msg' => $validate->getError()];
        }
        if(empty($this->user_id))return ['status'=>-1,'msg'=>'非法操作'];
        $row = M('order')->where(['user_id'=>$this->user_id,'order_id'=>$order_id])->update(['deleted'=>1]);
        if (!$row) {
            M('order_goods')->where(['order_id'=>$order_id])->update(['deleted'=>1]);
            return ['status'=>-1,'msg'=>'删除失败'];
        }
        return ['status'=>1,'msg'=>'删除成功'];
    }

    /**
     * 记录取消订单
     * @param $user_id
     * @param $order_id
     * @param $user_note
     * @param $consignee
     * @param $mobile
     * @return array
     */
    public function recordRefundOrder($user_id, $order_id, $user_note, $consignee, $mobile)
    {
        $order = M('order')->where(['order_id' => $order_id, 'user_id' => $user_id])->find();
        if (!$order) {
            return ['status' => -1, 'msg' => '订单不存在'];
        }
        if($order['shipping_status'] == 1){
        	return ['status' => -1, 'msg' => '该订单已经发货，请申请售后'];
        }
        $order_return_num = M('return_goods')->where(['order_id' => $order_id, 'user_id' => $user_id,'status'=>['neq',5]])->count();
        if($order_return_num > 0){
            return ['status' => -1, 'msg' => '该订单中有商品正在申请售后'];
        }
        $order_info = [
            'user_note' => $user_note,
            'consignee' => $consignee,
            'mobile'    => $mobile,
            'order_status'=> 3,
        ];

        $result = M('order')->where(['order_id' => $order_id])->update($order_info);
        if (!$result) {
            return ['status' => 0, 'msg' => '操作失败'];
        }
        if($order['prom_type']==5){  //虚拟订单要处理一下兑换码
            M('vr_order_code')->where(['order_id' => $order_id])->update(['refund_lock'=>1]);
        }
        $data['order_id'] = $order_id;
		$data['action_user'] = $user_id;
		$data['action_note'] = $user_note;
		$data['order_status'] = 3;
		$data['pay_status'] = $order['pay_status'];
		$data['shipping_status'] = $order['shipping_status'];
		$data['log_time'] = time();
		$data['user_type'] = 2;   //0管理员1商家2前台用户
		$data['status_desc'] = '用户取消已付款订单';
		M('order_action')->add($data);//订单操作记录
        $url = U('Mobile/Order/order_list');
        if ($order['prom_type']==5){
            $url = U('Mobile/Virtual/virtual_list');
        }
        Db::name('rebate_log')->where(['order_id'=>$order_id])->save(['status'=>4,'remark' => '订单取消，取消分成']);
		$message_logic = new \app\common\logic\MessageLogisticsLogic();
		$message_logic->sendRefundNotice($order, $order['order_amount']);
        return ['status' => 1, 'msg' => '提交成功','url'=>$url];
    }

	/**
	 * 	生成兑换码
	 * 长度 =3位 + 4位 + 2位 + 3位  + 1位 + 5位随机  = 18位
	 * @param $order
	 * @return mixed
	 */
	function make_virtual_code($order){
		$order_goods = M('order_goods')->where(array('order_id'=>$order['order_id']))->find();
		$goods = M('goods')->where(array('goods_id'=>$order_goods['goods_id']))->find();
		M('order')->where(array('order_id'=>$order['order_id']))->save(array('order_status'=>1,'shipping_time'=>time()));
		$perfix = mt_rand(100,999);
		$perfix .= sprintf('%04d', (int) $goods['store_id'] * $order['user_id'] % 10000)
				. sprintf('%02d', (int) $order['user_id'] % 100).sprintf('%03d', (float) microtime() * 1000);

		for ($i = 0; $i < $order_goods['goods_num']; $i++) {
			$order_code[$i]['order_id'] = $order['order_id'];
			$order_code[$i]['store_id'] = $goods['store_id'];
			$order_code[$i]['user_id'] = $order['user_id'];
			$order_code[$i]['vr_code'] = $perfix. sprintf('%02d', (int) $i % 100) . rand(5,1);
			$order_code[$i]['pay_price'] = $goods['shop_price'];
			$order_code[$i]['vr_indate'] = $goods['virtual_indate'];
			$order_code[$i]['vr_invalid_refund'] = $goods['virtual_refund'];
		}
		
		$res = checkEnableSendSms("7"); 

		//生成虚拟订单, 向用户发送短信提醒
		if($res && $res['status'] ==1){ 
		    $sender = $order['mobile'];
		    $goods_name = $goods['goods_name'];
		    $goods_name = getSubstr($goods_name, 0, 10);
	        $params = array('goods_name'=>$goods_name);
	        sendSms("7", $sender, $params);
		}
		
		return M('vr_order_code')->insertAll($order_code);
	}


    /**
     * 自动取消订单
     */
    public function  abolishOrder(){
        $abolishtime = time()-C('finally_pay_time');
        $order_where = [
            'user_id'      =>$this->user_id,
            'add_time'     =>['lt',$abolishtime],
            'pay_status'   =>0,
            'order_status' => 0,
			'prom_type'=>['NOTIN',[4]],//预售订单不走这个流程
        ];
        $order = Db::name('order')->where($order_where)->getField('order_id',true);
        foreach($order as $key =>$value){
            $result = $this->cancel_order($this->user_id,$value);
        }
        return $result;
    }

	/**
	 * 取消订单后改变库存，根据不同的规格，商品活动修改对应的库存
	 * @param $order|订单
	 * @param $rec_id|订单商品表id 如果有只返还订单某个商品的库存,没有返还整个订单
	 */
	public function alterReturnGoodsInventory($order, $rec_id='')
	{
		if($rec_id){
			$orderGoodsWhere['rec_id'] = $rec_id;
            $retunn_info = Db::name('return_goods')->where($orderGoodsWhere)->select(); //查找购买数量和购买规格
            $order_goods_prom = Db::name('order_goods')->where($orderGoodsWhere)->find(); //购买时参加的活动
            $order_goods = $retunn_info;
            $order_goods[0]['prom_type'] = $order_goods_prom['prom_type'];
            $order_goods[0]['prom_id'] = $order_goods_prom['prom_id'];
            $order_goods[0]['goods_name'] = $order_goods_prom['goods_name'];
            $order_goods[0]['spec_key_name'] = $order_goods_prom['spec_key_name'];
		}else{
            $orderGoodsWhere = ['order_id'=>$order['order_id']];
            $order_goods = Db::name('order_goods')->where($orderGoodsWhere)->select(); //查找购买数量和购买规格
        }
		foreach($order_goods as $key=>$val){
			if(!empty($val['spec_key'])){ // 先到规格表里面扣除数量 再重新刷新一个 这件商品的总数量
				$SpecGoodsPrice = new \app\common\model\SpecGoodsPrice();
				$specGoodsPrice = $SpecGoodsPrice::get(['goods_id' => $val['goods_id'], 'key' => $val['spec_key']]);
				$specGoodsPrice->where(['goods_id' => $val['goods_id'], 'key' => $val['spec_key']])->setInc('store_count', $val['goods_num']);//有规格则增加商品对应规格的库存
			}else{
				M('goods')->where(['goods_id' => $val['goods_id']])->setInc('store_count', $val['goods_num']);//没有规格则增加商品库存
			}
			update_stock_log($order['user_id'], $val['goods_num'], $val, $order['order_sn']);//库存日志

			Db::name('Goods')->where("goods_id", $val['goods_id'])->setDec('sales_sum', $val['goods_num']); // 减少商品销售量
			//更新活动商品购买量
			if ($val['prom_type'] == 1 || $val['prom_type'] == 2) {
				$GoodsPromFactory = new \app\common\logic\GoodsPromFactory();
				$goodsPromLogic = $GoodsPromFactory->makeModule($val, $specGoodsPrice);
				if($val['prom_type'] == 1){
					if($goodsPromLogic->checkActivityIsAble()){
						$flash_sale_queue =  Cache::get('flash_sale_queue');
						if(array_key_exists($val['prom_id'],$flash_sale_queue)){
							$flash_sale_queue[$val['prom_id']] = $flash_sale_queue[$val['prom_id']] + $val['goods_num'];
							Cache::set('flash_sale_queue',$flash_sale_queue);
							Db::name('flash_sale')->where("id", $val['prom_id'])->setInc('goods_num', $val['goods_num']);
						}
					}
				}
				$prom = $goodsPromLogic->getPromModel();
				if ($prom['status'] == 1 && $prom['is_end'] == 0) {
					$tb = $val['prom_type'] == 1 ? 'flash_sale' : 'group_buy';
					M($tb)->where("id", $val['prom_id'])->setDec('buy_num', $val['goods_num']);
					M($tb)->where("id", $val['prom_id'])->setDec('order_num',1);
				}
			}
			if($val['prom_type'] == 6){
				$team_activity = Db::name('team_activity')->where('team_id',$val['prom_id'])->find();
				if($team_activity['team_type'] != 2){
					Db::name('team_activity')->where('team_id',$val['prom_id'])->setDec('sales_sum', $val['goods_num']);
				}
			}
		}
	}
}