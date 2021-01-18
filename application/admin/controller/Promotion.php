<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * Author: 二月鸟飞
 * 专题管理
 * Date: 2016-03-09
 */

namespace app\admin\controller;

use app\common\model\Goods;
use app\common\model\SpecGoodsPrice;
use think\Page;
use app\common\logic\GoodsPromFactory;
use app\common\model\PromGoods;
use think\Db;

class Promotion extends Base {
    public $begin;
    public $end;
    function _initialize(){
        $start_time = urldecode(I('start_time'));
        $end_time = urldecode(I('end_time'));
        if($end_time && $start_time){
            $this->begin = strtotime($start_time);
            $this->end   = strtotime($end_time)+86399;
            $this->assign('start_time', $start_time);
            $this->assign('end_time',$end_time);
        }
    }
    public function index(){
        return $this->fetch();
    }
    
        /**
         * 商品活动列表
         */
	public function prom_goods_list()
	{
        $promGoodsModel = new PromGoods();
		$parse_type = array('0'=>'直接打折','1'=>'减价优惠','2'=>'固定金额出售','3'=>'买就赠优惠券');
		$level = Db::name('user_level')->select();
		if($level){
			foreach ($level as $v){
				$lv[$v['level_id']] = $v['level_name'];
			}
		}
		$this->assign("parse_type",$parse_type);
		$condition = array();
		$title = I('title');
		$status = I('status',-1);
        if($this->begin && $this->end){
            $condition['start_time'] = array('gt',$this->begin);
            $condition['end_time'] = array('lt',$this->end);
        }
		if($title){
			$condition['title'] = array('like',"%$title%");
		}
        if ($status>-1)$condition['status']= $status;
        $count = $promGoodsModel->where($condition)->count();
        $Page  = new Page($count,10);
        $show = $Page->show();
        $promGoodsBbj = $promGoodsModel->where($condition)->limit($Page->firstRow.','.$Page->listRows)->order('start_time desc')->select();
        if($promGoodsBbj){
            $prom_list = collection($promGoodsBbj)->append(['state'])->toArray();
        }
		$this->assign('pager',$Page);
		$this->assign('status',$status);
        $this->assign('page',$show);// 赋值分页输出
		$this->assign('prom_list',$prom_list);
		return $this->fetch();
	}
	
	public function prom_goods_info()
	{
		$this->assign('min_date',date('Y-m-d'));
		$level = M('user_level')->select();
		$this->assign('level',$level);
		$prom_id = I('id');
		$info['start_time'] = date('Y-m-d');
		$info['end_time'] = date('Y-m-d',time()+3600*60*24);
		if($prom_id>0){
			$info = M('prom_goods')->where("id=$prom_id")->find();
			$info['start_time'] = date('Y-m-d',$info['start_time']);
			$info['end_time'] = date('Y-m-d',$info['end_time']);
			$prom_goods = M('goods')->where("prom_id=$prom_id and prom_type=3")->select();
			$this->assign('prom_goods',$prom_goods);
		}
		$this->assign('info',$info);
		$this->assign('min_date',date('Y-m-d'));
		$this->initEditor();
		return $this->fetch();
	}
	
	public function prom_goods_save()
	{
		$prom_id = I('id');
		$data = I('post.');
		$data['start_time'] = strtotime($data['start_time']);
		$data['end_time'] = strtotime($data['end_time']);
		$data['group'] = (empty($data['group'])) ? '': implode(',', $data['group']);
		if($prom_id){
			M('prom_goods')->where("id=$prom_id")->save($data);
			$last_id = $prom_id;
			adminLog("管理员修改了商品促销 ".I('name'));
		}else{
			$last_id = M('prom_goods')->add($data);
			adminLog("管理员添加了商品促销 ".I('name'));
		}
		
		if(is_array($data['goods_id'])){
			$goods_id = implode(',', $data['goods_id']);
			if($prom_id>0){
				M("goods")->where("prom_id=$prom_id and prom_type=3")->save(array('prom_id'=>0,'prom_type'=>0));
			}
			M("goods")->where("goods_id in($goods_id)")->save(array('prom_id'=>$last_id,'prom_type'=>3));
		}
		$this->success('编辑促销活动成功',U('Promotion/prom_goods_list'));
	}
	
	public function prom_goods_del()
	{
		$prom_id = I('id');                
		$order_goods = M('order_goods')->where("prom_type = 3 and prom_id = $prom_id")->find();
		if(!empty($order_goods))
		{
			$this->error("该活动有订单参与不能删除!");
		}
		M("goods")->where("prom_id=$prom_id and prom_type=3")->save(array('prom_id'=>0,'prom_type'=>0));
        Db::name('spec_goods_price')->where(['prom_type'=>3,'prom_id'=>$prom_id])->save(array('prom_id'=>0,'prom_type'=>0));
        M('prom_goods')->where("id=$prom_id")->delete();
		$message_logic = new \app\common\logic\MessageActivityLogic([]);
		$message_logic->deletedMessage($prom_id, 3);
		$this->success('删除活动成功',U('Promotion/prom_goods_list'));
	}

	public function ajax_prom_goods_del()
	{
		$prom_id = I('id');
		$order_goods = M('order_goods')->where(array('prom_type' => 3, 'prom_id' => $prom_id))->find();
		if (!empty($order_goods)) {
			$this->ajaxReturn(array('status'=>0,'msg'=>'该活动有订单参与不能删除','result'=>''));
		}
		M("goods")->where(array('prom_type' => 3, 'prom_id' => $prom_id))->save(array('prom_id' => 0, 'prom_type' => 0));
		$r = M('prom_goods')->where(array('id' => $prom_id))->delete();
		if($r !== false){
			$message_logic = new \app\common\logic\MessageActivityLogic([]);
			$message_logic->deletedMessage($prom_id, 3);
			$this->ajaxReturn(array('status'=>1,'msg'=>'删除成功','result'=>''));
		}else{
			$this->ajaxReturn(array('status'=>0,'msg'=>'删除失败','result'=>''));
		}

	}
    

    
        /**
         * 活动列表
         */
	public function prom_order_list()
	{
		$parse_type = array('0'=>'满额打折','1'=>'满额优惠金额','2'=>'满额送积分','3'=>'满额送优惠券');
		$condition = array();
		$title = I('title');
		$status = I('status',-1);
        if($this->begin && $this->end){
            $condition['start_time'] = array('gt',$this->begin);
            $condition['end_time'] = array('lt',$this->end);
        }

        if($title){
			$condition['title'] = array('like',"%$title%");
		}
        switch ($status){
            case 0:
                $condition['status'] = $status;
                break;
            case 1:
                $condition['status']= $status;
                $condition['end_time'] = ['gt',time()];
                break;
            case 2:
                $condition['status']= 1;
                $condition['end_time'] = ['elt',time()];
                break;
        }
        $count = Db::name('prom_order')->where($condition)->count();
        $Page  = new Page($count,10);
        $show = $Page->show();               
		$res = Db::name('prom_order')->where($condition)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		if($res){
			foreach ($res as $val){
                if(time() > $val['start_time'] && time() < $val['end_time'] && $val['status'] == 1){
                    $val['state'] = '进行中';
                }elseif($val['status']==0){
                    $val['state'] = '管理员关闭';
                } else{
                    $val['state'] = '已过期';
                }
				$prom_list[] = $val;
			}
		}
        $this->assign('status',$status);
		$this->assign('pager',$Page);
        $this->assign('page',$show);// 赋值分页输出                  
        $this->assign("parse_type",$parse_type);
		$this->assign('prom_list',$prom_list);
		return $this->fetch();
	}
	
	public function prom_order_info(){
		$this->assign('min_date',date('Y-m-d'));
		$level = M('user_level')->select();
		$this->assign('level',$level);
		$prom_id = I('id');
		$info['start_time'] = date('Y-m-d');
		$info['end_time'] = date('Y-m-d',time()+3600*24*60);
		if($prom_id>0){
			$info = M('prom_order')->where("id=$prom_id")->find();
			$info['start_time'] = date('Y-m-d',$info['start_time']);
			$info['end_time'] = date('Y-m-d',$info['end_time']);
		}
		$this->assign('info',$info);
		$this->assign('min_date',date('Y-m-d'));
		$this->initEditor();
		return $this->fetch();
	}
	
	public function prom_order_save(){
		$prom_id = I('id');
		$data = I('post.');
		$data['start_time'] = strtotime($data['start_time']);
		$data['end_time'] = strtotime($data['end_time']);
		$data['group'] = implode(',', $data['group']);
		if($prom_id){
			M('prom_order')->where("id=$prom_id")->save($data);
			adminLog("管理员修改了商品促销 ".I('name'));
		}else{
			M('prom_order')->add($data);
			adminLog("管理员添加了商品促销 ".I('name'));
		}
		$this->success('编辑促销活动成功',U('Promotion/prom_order_list'));
	}
	
	public function prom_order_del()
	{
		$prom_id = I('id');                                
                $order = M('order')->where("order_prom_id = $prom_id")->find();
                if(!empty($order))
                {
                    $this->error("该活动有订单参与不能删除!");    
                }
                                
		M('prom_order')->where("id=$prom_id")->delete();
		$message_logic = new \app\common\logic\MessageActivityLogic([]);
		$message_logic->deletedMessage($prom_id, 9);
		$this->success('删除活动成功',U('Promotion/prom_order_list'));
	}

	public function ajax_prom_order_del()
	{
		$prom_id = I('id');
		$order = M('order')->where("order_prom_id = $prom_id")->find();
		if (!empty($order)) {
			$this->ajaxReturn(array('status' => 0, 'msg' => '该活动有订单参与不能删除!', 'result' => ''));
		}
		$r = M('prom_order')->where("id=$prom_id")->delete();
		if($r !== false){
			$message_logic = new \app\common\logic\MessageActivityLogic([]);
			$message_logic->deletedMessage($prom_id, 9);
			$this->ajaxReturn(array('status' => 1, 'msg' => '删除活动成功!', 'result' => ''));
		}else{
			$this->ajaxReturn(array('status' => 0, 'msg' => '删除活动失败!', 'result' => ''));
		}
	}
	
    public function group_buy_list(){
    	$Ad =  M('group_buy');
    	$condition = array();
    	$title = I('title');
    	$status = I('status');
        if($this->begin && $this->end){
            $condition['start_time'] = array('gt',$this->begin);
            $condition['end_time'] = array('lt',$this->end);
        }
    	if($title){
    		$condition['title'] = array('like',"%$title%");
    	}
    	if($status=='0' || $status>0){
    		$condition['status'] = $status;
    	}
    	
    	$count = $Ad->where($condition)->count();
    	$Page = new Page($count,20);
    	$res = $Ad->where($condition)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
    	if($res){
    		foreach ($res as $val){
    			$val['start_time'] = date('Y-m-d',$val['start_time']);
    			$val['end_time'] = date('Y-m-d',$val['end_time']);
    			$list[] = $val;
    		}
    	}
    	$this->assign('list',$list);
    	$this->assign('state',array('审核中','正常','未通过','管理员关闭'));
    	$show = $Page->show();
    	$this->assign('page',$show);
		$this->assign('pager',$Page);
    	return $this->fetch();
    }
    
    public function group_buy(){
    	$act = I('GET.act','add');
    	$groupbuy_id = I('get.id');
    	$group_info = array();
    	$group_info['start_time'] = date('Y-m-d');
    	$group_info['end_time'] = date('Y-m-d',time()+3600*365);
    	if($groupbuy_id){
    		$group_info = D('group_buy')->where('id='.$groupbuy_id)->find();
    		$group_info['start_time'] = date('Y-m-d',$group_info['start_time']);
    		$group_info['end_time'] = date('Y-m-d',$group_info['end_time']);
    		$act = 'edit';
    	}
    	$this->assign('min_date',date('Y-m-d'));
    	$this->assign('info',$group_info);
    	$this->assign('act',$act);
    	return $this->fetch();
    }
    
    public function groupbuyHandle(){
    	$data = I('post.');
    	if($data['act'] == 'del'){
    		$r = M('group_buy')->where(array('id'=>$data['id']))->delete();
    		M('goods')->where(array('prom_type'=>2,'prom_id'=>$data['id']))->save(array('prom_id'=>0,'prom_type'=>0));
    		if($r){
				$message_logic = new \app\common\logic\MessageActivityLogic([]);
				$message_logic->deletedMessage($data['id'], 2);
    			exit(json_encode(1));
    		}else{
    			exit(json_encode('删除失败'));
    		}
    	}
    }
    
    public function get_goods(){
    	$prom_id = I('id');
    	$count = M('goods')->where("prom_id=$prom_id and prom_type=3")->count(); 
    	$Page  = new Page($count,10);
    	$goodsList = M('goods')->where("prom_id=$prom_id and prom_type=3")->order('goods_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	$show = $Page->show();
		$this->assign('pager',$Page);
    	$this->assign('page',$show);
    	$this->assign('goodsList',$goodsList);
    	return $this->fetch();
    }   
    
    public function search_goods(){
    	$GoodsLogic = new \app\admin\logic\GoodsLogic;
    	$brandList = $GoodsLogic->getSortBrands();
    	$this->assign('brandList',$brandList);
    	$categoryList = $GoodsLogic->getSortCategory();
    	$this->assign('categoryList',$categoryList);
    	
    	$goods_id = I('goods_id');
    	$where = ' is_on_sale = 1 and prom_type=0 and store_count>0 ';//搜索条件
    	if(!empty($goods_id)){
    		$where .= " and goods_id not in ($goods_id) ";
    	}
    	I('intro')  && $where = "$where and ".I('intro')." = 1";
    	if(I('cat_id')){
    		$this->assign('cat_id',I('cat_id'));
    		$grandson_ids = getCatGrandson(I('cat_id'));
    		$where = " $where  and cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
    	}
    	if(I('brand_id')){
    		$this->assign('brand_id',I('brand_id'));
    		$where = "$where and brand_id = ".I('brand_id');
    	}
    	if(!empty($_REQUEST['keywords']))
    	{
    		$this->assign('keywords',I('keywords'));
    		$where = "$where and (goods_name like '%".I('keywords')."%' or keywords like '%".I('keywords')."%')" ;
    	}
    	$count = M('goods')->where($where)->count();
    	$Page  = new Page($count,10);
    	$goodsList = M('goods')->where($where)->order('goods_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	$show = $Page->show();//分页显示输出
    	$this->assign('page',$show);//赋值分页输出
    	$this->assign('goodsList',$goodsList);
    	$tpl = I('get.tpl','search_goods');
    	return $this->fetch($tpl);
    }
    
    //限时抢购
    public function flash_sale(){
    	$condition = array();
    	$title = I('title');
    	$status = I('status');
        if($this->begin && $this->end){
            $condition['start_time'] = array('gt',$this->begin);
            $condition['end_time'] = array('lt',$this->end);
        }
    	if($title){
    		$condition['title'] = array('like',"%$title%");
    	}
    	if($status=='0' || $status>0){
    		$condition['status'] = $status;
            $status==1 &&$condition['end_time'] = array('gt',time());
    	}
    	$model = M('flash_sale');
    	$count = $model->where($condition)->count();
    	$Page  = new Page($count,10);
    	$show = $Page->show();
    	$prom_list = $model->where($condition)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
    	$this->assign('state',array('待审核','正常','未通过','管理员关闭','已售馨'));
    	$this->assign('prom_list',$prom_list);
    	$this->assign('page',$show);// 赋值分页输出
		$this->assign('pager',$Page);
    	return $this->fetch();
    }
    
    public function flash_sale_info(){
    	if(IS_POST){
    		$data = I('post.');
    		$data['start_time'] = strtotime($data['start_time']);
    		$data['end_time'] = strtotime($data['end_time']);
    		if(empty($data['id'])){
    			$r = M('flash_sale')->add($data);
    			M('goods')->where("goods_id=".$data['goods_id'])->save(array('prom_id'=>$r,'prom_type'=>1));
    			adminLog("管理员添加抢购活动 ".$data['name']);
    		}else{
    			$r = M('flash_sale')->where("id=".$data['id'])->save($data);
    			M('goods')->where("prom_type=1 and prom_id=".$data['id'])->save(array('prom_id'=>0,'prom_type'=>0));
    			M('goods')->where("goods_id=".$data['goods_id'])->save(array('prom_id'=>$data['id'],'prom_type'=>1));
    		}
    		if($r){
    			$this->success('编辑抢购活动成功',U('Promotion/flash_sale'));
    			exit;
    		}else{
    			$this->error('编辑抢购活动失败',U('Promotion/flash_sale'));
    		}
    	}
    	$id = I('id');
    	$info['start_time'] = date('Y-m-d');
    	$info['end_time'] = date('Y-m-d',time()+3600*24*60);
    	if($id>0){
    		$info = M('flash_sale')->where("id=$id")->find();
    		$info['start_time'] = date('Y-m-d',$info['start_time']);
    		$info['end_time'] = date('Y-m-d',$info['end_time']);
    	}
    	$this->assign('info',$info);
    	$this->assign('min_date',date('Y-m-d'));
    	return $this->fetch();
    }
    
    public function flash_sale_del(){
    	$id = I('del_id');
    	if($id){
            Db::name('flash_sale')->where("id=$id")->delete();
            Db::name('goods')->where("prom_type=1 and prom_id=$id")->save(array('prom_id'=>0,'prom_type'=>0));
            Db::name('spec_goods_price')->where(['prom_id'=>$id])->save(array('prom_id'=>0,'prom_type'=>0));
			$message_logic = new \app\common\logic\MessageActivityLogic([]);
			$message_logic->deletedMessage($id, 1);
    		exit(json_encode(1));
    	}else{
    		exit(json_encode(0));
    	}
    }
    
    public function activity_handle(){
		$tab = I('tab');
		$id = I('id');
		$status = I('status');
		$prom_type = I('prom_type');
		$goods_prom = Db::name($tab)->where(array('id'=>$id))->find();
		if(!$goods_prom){
			$this->ajaxReturn(['status'=>0,'msg'=>'非法操作，活动不存在！']);
		};
		if(Db::name($tab)->where(array('id'=>$id))->save(array('status'=>$status))){
			$goodsPromFactory = new GoodsPromFactory();
			$message_logic = new \app\common\logic\MessageActivityLogic();
			if($status!=1){
				if($goodsPromFactory->checkPromType($prom_type)) {
					if($goods_prom['item_id']){    //有商品规格的活动
						Db::name('spec_goods_price')->where(['item_id'=>$goods_prom['item_id']])->save(array('prom_id'=>0,'prom_type'=>0));
					}elseif ($prom_type == 3){  //促销
						Db::name('spec_goods_price')->where(['prom_type'=>$prom_type,'prom_id'=>$id])->save(array('prom_id'=>0,'prom_type'=>0));
					}
					Db::name('goods')->where(['prom_type'=>$prom_type,'goods_id'=>$goods_prom['goods_id']])->update(['prom_id'=>0,'prom_type'=>0]);
				}else{
					Db::name('goods')->where(['prom_type'=>$prom_type,'prom_id'=>$id])->update(array('prom_id'=>0,'prom_type'=>0));
				}
				switch($tab){
					case 'flash_sale':
						$message_logic->deletedMessage($id, 1);
						break;
					case 'group_buy':
						$message_logic->deletedMessage($id, 2);
						break;
				}
			} else {
				// 通知消息
				$message_logic->sendMessageById($id, $tab);
			}
			$this->ajaxReturn(['status'=>1,'msg'=>'操作成功']);
		}else{
			$this->ajaxReturn(['status'=>0,'msg'=>'操作失败']);
		}
	}
    
    private function initEditor()
    {
    	$this->assign("URL_upload", U('Admin/Ueditor/imageUp',array('savepath'=>'promotion')));
    	$this->assign("URL_fileUp", U('Admin/Ueditor/fileUp',array('savepath'=>'promotion')));
    	$this->assign("URL_scrawlUp", U('Admin/Ueditor/scrawlUp',array('savepath'=>'promotion')));
    	$this->assign("URL_getRemoteImage", U('Admin/Ueditor/getRemoteImage',array('savepath'=>'promotion')));
    	$this->assign("URL_imageManager", U('Admin/Ueditor/imageManager',array('savepath'=>'promotion')));
    	$this->assign("URL_imageUp", U('Admin/Ueditor/imageUp',array('savepath'=>'promotion')));
    	$this->assign("URL_getMovie", U('Admin/Ueditor/getMovie',array('savepath'=>'promotion')));
    	$this->assign("URL_Home", "");
    }

	/**
	 * 只能关闭出促销活动以外的活动
	 * 关闭活动
	 */
	public function closeProm()
	{
		$goods_id = input('goods_id');
		$prom_id = input('prom_id');
		$goodsPromFactory = new GoodsPromFactory();
		$goods = Goods::get($goods_id);
		if(empty($goods_id) || empty($goods->prom_type)){
			$this->ajaxReturn(['status' => 0, 'msg' => '参数错误']);
		}

		//如果单商品直接关闭商品活动
        if($goods->prom_type && $goods->prom_id){
            $goodsPromLogic = $goodsPromFactory->makeModule($goods, null);
            $promId = $goodsPromLogic->getPromId();
            if ($promId == $prom_id) {
                $goodsPromLogic->closeProm();
                $goodsPromLogic->initProm();
                $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
            } else {
                $this->ajaxReturn(['status' => 0, 'msg' => '参数错误']);
            }
        }

        //如果商品带有规格 循环将商品规格的活动置零
        if($goods->prom_type && !$goods->prom_id){
            db('spec_goods_price')->where('goods_id',$goods_id)->where('prom_id',$prom_id)->update(['prom_type'=>0,'prom_id'=>0]);
            db('team_goods_item')->where('team_id',$prom_id)->update(['deleted'=>1]);
            db('team_activity')->where('team_id',$prom_id)->update(['status'=>3]);
        }
        $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
	}

	/**
	 * 促销活动关闭活动
	 */
	public function closePromGoods()
	{
		$prom_id = input('prom_id');
		if(empty($prom_id)){
			$this->ajaxReturn(['status' => 0, 'msg' => '参数错误']);
		}
		$promGoods = PromGoods::get($prom_id);
		if(empty($promGoods)){
			$this->ajaxReturn(['status' => 0, 'msg' => '参数错误']);
		}
		$promGoods['status'] = 0;
		$close = $promGoods->save();
		Db::name('goods')->where(['prom_type'=>3,'prom_id' =>$prom_id])->save(['prom_type' => 0, 'prom_id' => 0]);

		Db::name('spec_goods_price')->where(['prom_type'=>3,'prom_id' =>$prom_id])->save(['prom_type' => 0, 'prom_id' => 0]);
		if($close !== false){
			$this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
		}else{
			$this->ajaxReturn(['status' => 0, 'msg' => '操作失败']);
		}
	}
    
}