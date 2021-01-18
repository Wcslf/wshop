<?php
/**
 * tpshop
 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * $Author: 二月鸟飞 2019-03-1 $
 */ 
namespace app\api\controller;
use app\common\logic\User;
use app\common\model\Coupon;
use app\common\util\TpshopException;
use think\Db;
use think\Page;

class Activity extends Base {

    public function group_list()
    {
        $page_size = I('page_size', 10);
        $p = I('p',1);
        $type = I('type', '');

        if ($type == 'new') {
            $orderBy = 'b.start_time';
        } elseif ($type == 'comment') {
            $orderBy = 'g.comment_count';
        } else {
            $orderBy = '';
        }

        $group_by_where = array(
            'b.start_time'  =>array('lt',time()),
            'b.end_time'    =>array('gt',time()),
            'b.status'      =>1,
            'b.is_end'      =>0,
            'g.is_on_sale'=>1,  //已上架的商品才能参与团购
            'b.recommend'=>1,//已推荐的才能显示
        );
        $groups = Db::name('group_buy')->alias('b')
            ->field('unix_timestamp(now()) as server_time,b.goods_id,b.rebate,b.virtual_num,b.buy_num,b.title,b.goods_price,b.end_time,b.price,g.comment_count,b.item_id,g.market_price')
            ->join('__GOODS__ g', 'b.goods_id=g.goods_id AND g.prom_type=2')
            ->where($group_by_where)->page($p, $page_size)
            ->order($orderBy, 'desc')
            ->select(); // 找出这个商品
         
        //查找广告
        $start_time = strtotime(date('Y-m-d H:00:00'));
        $end_time = strtotime(date('Y-m-d H:00:00')); 
        $adv = M("ad")->field(array('ad_link','ad_name','ad_code','media_type,pid'))->where("pid=534 AND enabled=1 and start_time< $start_time and end_time > $end_time")->find();
        if($adv && $adv['media_type'] == 4){//如果是分类, 截取最后一个分类
            $cats = explode('_',$adv['ad_link']);
            $count = count($cats);
            if($count != 0) {
                $adv['ad_link'] = $cats[$count-1];
            }
        } 
        $json = array(
            'status'=>1,
            'msg'=>'获取成功',
            'result'=> [
                'groups' => $groups,
                'ad' => empty($adv) ? "" : $adv,
                'server_current_time' => time()
            ]
        );
        $this->ajaxReturn($json);
    }

    /**
     * @author wangqh
     * 抢购活动时间节点
     */
    public function flash_sale_time()
    {
        $time_space = flash_sale_time_space();
        $times = array();
        foreach ($time_space as $k => $v){
            $times[] = $v;
        }
        
        $ad = M('ad')->field(['ad_link','ad_name','ad_code'])->where('pid', 2)->cache(true, TPSHOP_CACHE_TIME)->find();
        
         $return = array(
            'status'=>1,
            'msg'=>'获取成功',
            'result'=> [
                'time' => $times,
                'ad' => $ad
            ] ,
        );
        $this->ajaxReturn($return);
    }
    
 
    /**
     * @author wangqh
     * 抢购活动列表
     */
    public function flash_sale_list()
    {
        $p = I('p',1);
        $start_time = I('start_time');
        $end_time = I('end_time');
        $where = array(
            'f.status' => 1,
            'f.start_time'=>array('egt',$start_time),
            'f.recommend'=>1,
            'g.is_on_sale'=>1,
            'f.end_time'=>array('elt',$end_time)
        );
         
        $flash_sale_goods = M('flash_sale')
        ->field('f.goods_name,f.price,f.goods_id,f.price,g.shop_price,f.item_id,100*(FORMAT(f.buy_num/f.goods_num,2)) as percent')
        ->alias('f')
        ->join('__GOODS__ g','g.goods_id = f.goods_id')
        ->where($where)
        ->page($p,10)
        ->cache(true,TPSHOP_CACHE_TIME)
        ->select();
        
        $return = array(
            'status'=>1,
            'msg'=>'获取成功',
            'result'=>$flash_sale_goods ,
        );
        $this->ajaxReturn($return);
    }

    /**
     * 领券列表：与手机网页版的接口一样
     */
    public function coupon_list()
    {
        $type = I('type', 1);
        $p = I('p', 1);

        $time = time();
        $where = array('type' => 2,'status'=>1,'send_start_time'=>['elt', $time],'send_end_time'=>['egt', $time]);
        $order = array('id' => 'desc');
        if ($type == 2) {
            //即将过期
            $order = ['spacing_time' => 'asc'];
            $where["send_end_time-'$time'"] = ['egt', 0];
        } elseif ($type == 3) {
            //面值最大
            $order = ['money' => 'desc'];
        }
        $Coupon = new Coupon();
        $coupon_list = $Coupon->field("*,send_end_time-'$time' as spacing_time")->where($where)->page($p, 15)->order($order)->select();
        if ($coupon_list) {
            $coupon_list = collection($coupon_list)->append(['use_type_title'])->toArray();
            $store_id_arr = get_arr_column($coupon_list, 'store_id');
            $store_arr = Db::name('store')->where("store_id", "in", array_unique($store_id_arr))->getField('store_id,store_name,store_logo');
            if ($this->user_id) {
                $user_coupon = Db::name('coupon_list')->where(['uid' => $this->user_id, 'type' => 2])->getField('cid',true);
            }
            if (!empty($user_coupon)) {
                foreach ($coupon_list as $k => $val) {
                    $coupon_list[$k]['isget'] = 0;
                    if (in_array($val['id'],$user_coupon)) {
                        $coupon_list[$k]['isget'] = 1;
                    }
                    $coupon_list[$k]['use_scope'] = $coupon_list[$k]['use_type_title'];
                }
            }
        }
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => [
            'store_arr' => $store_arr ?: [],
            'coupon_list' => $coupon_list,
        ]]);
    }
    
    /**
     * 领券中心
     */
    public function coupon_center()
    {
        $p = I('get.p', 1);
        $cat_id = I('get.cat_id', 0);
        /* 获取优惠券列表 */
        $cur_time = time();
        $coupon_where = ['type'=>2, 'status'=>1, 'send_start_time'=>['elt',time()], 'send_end_time'=>['egt',time()]];
        $query = M('coupon')->alias('c')
            ->field('c.id,c.name,c.use_type,c.money,c.condition,c.createnum,c.send_num,c.store_id,c.send_end_time-'.$cur_time.' as spacing_time')
            ->where('((createnum-send_num>0 AND createnum>0) OR (createnum=0))')    //领完的也不要显示了
            ->where($coupon_where)->page($p, 15)
            ->order('spacing_time', 'asc');

        if ($cat_id > 0) {
            $query = $query->join('__GOODS_COUPON__ gc', 'gc.coupon_id=c.id AND gc.goods_category_id='.$cat_id);
        }
        $coupon_list = $query->select();

        if (empty($coupon_list)) {
            $this->ajaxReturn(['status' => 1,'msg' => '获取成功', 'result' => []]);
        }

        $store_id_arr = get_arr_column($coupon_list, 'store_id');
        $store_arr = M('store')->where('store_id', 'in', $store_id_arr)->getField('store_id,store_name,store_logo');

        $user_coupon = [];
        if ($this->user_id) {
            $user_coupon = M('coupon_list')->where(['uid' => $this->user_id, 'type' => 2])->column('cid');
        }

        foreach ($coupon_list as $k => &$coupon) {
            /* 是否已获取 */
            $coupon['isget'] = 0;
            if (in_array($coupon['id'], $user_coupon)) {
                $coupon_list[$k]['isget'] = 1;
            }

            /* 构造封面和标题 */
            $store_id = $coupon['store_id'];
            if ($store_id > 0) {
                $coupon['image'] = $store_arr[$store_id]['store_logo'] ?: '';
                //use_type:0全店通用 1指定商品可用 2指定分类商品可用
                if ($coupon['use_type'] == 0) {
                    $coupon['title'] = '可在'.$store_arr[$store_id]['store_name'].'通用购买';
                } elseif ($coupon['use_type'] == 1) {
                    $coupon['title'] = '可购买'.$store_arr[$store_id]['store_name'].'指定的商品';
                } else {
                    $coupon['title'] = '可购买'.$store_arr[$store_id]['store_name'].'指定分类的商品';
                }
            } else {
                $coupon['image'] = '';
                $coupon['title'] = '可用于全平台的商品';
            }
        }
        $this->ajaxReturn(['status' => 1,'msg' => '获取成功', 'result' => $coupon_list]);
    }
    
    /**
     * 优惠券类型列表
     */
    public function coupon_type_list()
    {
        $p = I('get.p', 1);
        $goods_category_id = Db::name('coupon')->alias('c')
            ->join('__GOODS_COUPON__ gc', 'gc.coupon_id=c.id AND gc.goods_category_id!=0')
            ->where(['type' => 2, 'status' => 1])
            ->column('gc.goods_category_id');
        $category_list = [0=>['id'=>0, 'mobile_name'=>'精选']];
        $goods_category_list = Db::name('goods_category')->field('id, mobile_name')->where("id", "IN", $goods_category_id)->page($p, 15)->select();
        if($goods_category_list){
            $category_list = array_merge($category_list, $goods_category_list);
        }
        $this->ajaxReturn([ 'status' => 1,'msg' => '获取成功', 'result' => $category_list]);
    }
    
    /**
     * 领取优惠券
     */
    public function get_coupon()
    {
        $coupon_id = I('coupon_id/d');
        if(empty($coupon_id)){
            $this->ajaxReturn(['status'=>0,'msg'=>'参数错误']);
        }
        $user = new User();
        $user->setUserById($this->user_id);
        try{
            $user->getCouponByID($coupon_id);
        }catch (TpshopException $t){
            $this->ajaxReturn($t->getErrorArr());
        }
        $this->ajaxReturn(['status' => 1, 'msg' => '恭喜您，抢到优惠券!']);
    }
    
    /**
     *  促销活动页面
     */ 
    public function promote_list()
    {
        $pageSize = I('page_size/d' , 10);
        $goods_where['p.start_time']  = array('lt',time());
        $goods_where['p.end_time']  = array('gt',time());
        $goods_where['p.status']  = 1;
        $goods_where['p.is_end']  = 0;
        $goods_where['p.recommend']  = 1;
        $goods_where['g.prom_type']  = 3;
        $goods_where['g.is_on_sale']  = 1;
        $goods_where['g.is_virtual']  = 0;
        $goodsCount = M('goods')
        ->alias('g')
        ->join('__PROM_GOODS__ p', 'g.prom_id = p.id')
        ->join('__SPEC_GOODS_PRICE__ s','g.prom_id = s.prom_id AND s.goods_id = g.goods_id','LEFT')
        ->where($goods_where)
        ->group('g.goods_id')
        ->count('g.goods_id');
        $Page  = new Page($goodsCount,$pageSize); //分页类
        $goodsList = M('Goods')
            ->field('g.goods_id,g.goods_name,g.shop_price,g.click_count ,g.market_price ,p.end_time,s.item_id')
            ->alias('g')
            ->join('__PROM_GOODS__ p', 'g.prom_id = p.id')
            ->join('__SPEC_GOODS_PRICE__ s','g.prom_id = s.prom_id AND s.goods_id = g.goods_id','LEFT')
            ->where($goods_where)
            ->limit($Page->firstRow.','.$Page->listRows)
            ->group('g.goods_id')
            ->order('p.id desc')
            ->cache(true,10)
            ->select();
        $server_time = time();
        $return_list = array();
        foreach ($goodsList as $v) {
            $v["server_time"] = $server_time;
            $return_list[] = $v;
        }
         
        $return = array(
            'status' => 1,
            'msg' => '获取成功',
            'result' => $return_list ,
        );
        $this->ajaxReturn($return);
    }
}