<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * $Author: 二月鸟飞 2016-01-09
 */

namespace app\mobile\controller;

use think\Db;
use think\Page;

use app\common\logic\wechat\WechatUtil;

class Index extends MobileBase
{

    public function index()
    {
        $diy_index = M('mobile_template')->where('is_index=1')->field('template_html,block_info')->find();
        if($diy_index){
            $html = htmlspecialchars_decode($diy_index['template_html']);
            $this->assign('html',$html);
            $this->assign('info',$diy_index['block_info']);
            return $this->fetch('index2');
            exit();
        }

        $hot_goods = M('goods')->where("is_hot=1 and is_on_sale=1")->order('goods_id DESC')->limit(20)->cache(true, TPSHOP_CACHE_TIME)->select();//首页热卖商品
        $thems = M('goods_category')->where('level=1')->order('sort_order')->limit(9)->cache(true, TPSHOP_CACHE_TIME)->select();
        $this->assign('thems', $thems);
        $this->assign('hot_goods', $hot_goods);
        $favourite_goods = M('goods')->where("is_recommend=1 and is_on_sale=1 and goods_state=1")->order('sort DESC')->limit(20)->cache(true, TPSHOP_CACHE_TIME)->select();//首页推荐商品
        //秒杀商品
        $now_time = time();  //当前时间
        if (is_int($now_time / 7200)) {      //双整点时间，如：10:00, 12:00
            $start_time = $now_time;
        } else {
            $start_time = floor($now_time / 7200) * 7200; //取得前一个双整点时间
        }
        $end_time = $start_time + 7200;   //结束时间
        $flash_sale_list = M('goods')->alias('g')
            ->field('g.goods_id,f.price,s.item_id')
            ->join('__FLASH_SALE__ f', 'g.goods_id = f.goods_id', 'LEFT')
            ->join('__SPEC_GOODS_PRICE__ s', 's.prom_id = f.id AND g.goods_id = s.goods_id', 'LEFT')
            ->where('f.status', 1)
            ->where("f.start_time >= $start_time and f.end_time <= $end_time and f.recommend=1")
            ->limit(3)->select();
        $this->assign('flash_sale_list', $flash_sale_list);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('favourite_goods', $favourite_goods);
        return $this->fetch();
    }

    public function index2(){
        $id=I('post.id');
        if($id){
            $arr=M('mobile_template')->where('id='.$id)->field('template_html,block_info')->find();
        }else{
            $arr=M('mobile_template')->where('is_index=1')->field('template_html,block_info')->find();
        }

        $html=htmlspecialchars_decode($arr['template_html']);
        $this->assign('html',$html);
        $this->assign('info',$arr['block_info']);
        return $this->fetch();
    }

    //商品列表板块参数设置
    public function goods_list_block(){
        $data=I('post.');
        $count=I('post.num');

        if($data['ids']){
            $ids = substr($data['ids'],0,strlen($data['ids'])-1);   //ids是前台传递过来的商品2级分类
        }
        
        if($ids){
            $ids="(".$ids.")";
            //此处前台传递的是2级分类id 需要获取它的3级分类
            $cat_ids=Db::name('goods_category')->where("parent_id in".$ids." and is_show=1")->getField('id',true);  
        }
        if($cat_ids){
            $str="(".implode(",",$cat_ids).")";
        }
        
        $where='is_on_sale=1';
        if($cat_ids){
            $where.=" and cat_id3 in".$str;
        }
        if($data['label']){
            $where.=" and ".$data['label']."=1";
        }
        if($data['min_price']){
            $where.=" and shop_price>".$data['min_price'];
        }
        if($data['max_price']){
            $where.=" and shop_price<".$data['max_price'];
        }
        if($data['goods']){
            $goods_id = substr($data['goods'],0,strlen($data['goods'])-1);
            $goods_id = "(".$goods_id.")";
            $where.=" and goods_id in".$goods_id;
        }


        switch ($data['order']) {
            case '0':
                $order_str="sales_sum DESC";
                break;
            
            case '1':
                $order_str="sales_sum ASC";
                break;

            case '2':
                $order_str="shop_price DESC";
                break;

            case '3':
                $order_str="shop_price ASC";
                break;

            case '4':
                $order_str="last_update DESC";
                break;

            case '5':
                $order_str="last_update ASC";
                break;
        }

        $goodsList = M('Goods')->where($where)->order($order_str)->limit(0,$count)->select();

        $html='';
        foreach ($goodsList as $k => $v) {
            $html.='<li>';
            $html.='<a class="tpdm-goods-pic" href="/Mobile/Goods/goodsInfo/id/'.$v["goods_id"].'"><img src="'.$v["original_img"].'" alt="" /></a>';
            $html.='<a href="/Mobile/Goods/goodsInfo/id/'.$v["goods_id"].'" class="tpdm-goods-name">'.$v["goods_name"].'</a>';
            $html.='<div class="tpdm-goods-des">';
            $html.='<div class="tpdm-goods-price">￥'.$v['shop_price'].'</div>'; 
            $html.='<a class="tpdm-goods-like" href="/Mobile/Goods/goodsList/id/'.$v["cat_id3"].'">看相似</a>'; 
            $html.='</div>';
            $html.='</li>';
        }
        $this->ajaxReturn(['status' => 1, 'msg' => '成功', 'result' =>$html]);
    }


    //自定义页面获取秒杀商品数据
    public function get_flash(){
        $now_time = time();  //当前时间
        if(is_int($now_time/7200)){      //双整点时间，如：10:00, 12:00
            $start_time = $now_time;
        }else{
            $start_time = floor($now_time/7200)*7200; //取得前一个双整点时间
        }
        $end_time = $start_time+7200;   //结束时间
        $flash_sale_list = M('goods')->alias('g')
            ->field('g.goods_id,g.original_img,g.shop_price,f.price,s.item_id')
            ->join('flash_sale f','g.goods_id = f.goods_id','LEFT')
            ->join('__SPEC_GOODS_PRICE__ s','s.prom_id = f.id AND g.goods_id = s.goods_id','LEFT')
            ->where("start_time = $start_time and end_time = $end_time")
            ->limit(4)->select();
        $str='';
        if($flash_sale_list){
            foreach ($flash_sale_list as $k => $v) {
                $str.='<a href="'.U('Mobile/Activity/flash_sale_list').'">';
                $str.='<img src="'.$v['original_img'].'" alt="" />';
                $str.='<span>￥'.$v['price'].'</span>';
                $str.='<i>￥'.$v['shop_price'].'</i></a>';
            }
        }
        $time=date('H',$start_time);
        $this->ajaxReturn(['status' => 1, 'msg' => '成功','html' => $str, 'start_time'=>$time, 'end_time'=>$end_time]);
    }

    /**
     * 分类列表显示
     */
    public function categoryList()
    {
        return $this->fetch();
    }

    /**
     * 模板列表
     */
    public function mobanlist()
    {
        $arr = glob("D:/wamp/www/svn_tpshop/mobile--html/*.html");
        foreach ($arr as $key => $val) {
            $html = end(explode('/', $val));
            echo "<a href='http://www.php.com/svn_tpshop/mobile--html/{$html}' target='_blank'>{$html}</a> <br/>";
        }
    }

    /**
     * 商品列表页
     */
    public function goodsList()
    {
        $id = I('get.id/d', 0); // 当前分类id
        $lists = getCatGrandson($id);
        $this->assign('lists', $lists);
        return $this->fetch();
    }

    public function ajaxGetMore()
    {
        $p = I('p/d', 1);
        $where = [
            //'is_recommend' => 1,
            'is_on_sale' => 1,
            'goods_state' => 1,
            'virtual_indate' => ['exp', ' = 0 OR virtual_indate > ' . time()],
            'exchange_integral'=>0
        ];
        $favourite_goods = Db::name('goods')->where($where)->order('sort DESC,goods_id DESC')->page($p, 10)->cache(true, TPSHOP_CACHE_TIME)->select();//首页推荐商品
        $this->assign('favourite_goods', $favourite_goods);
        echo $this->fetch();
    }

    /**
     * 店铺街
     * @author dyr
     * @time 2016/08/15
     */
    public function street()
    {
        $store_class = M('store_class')->select();
        $this->assign('store_class', $store_class);//店铺分类
        return $this->fetch();
    }

    /**
     * ajax 获取店铺街
     */
    public function ajaxStreetList()
    {
        $sc_id = I('sc_id/d', 0);
        $province_id = I('province_id');
        $city_id = I('city_id');
        $district_id = I('district_id');
        $order = I('order', 0);
        $all = I('all', 0);
        $storeWhere = ['store_state' => 1,'deleted'=>0,'store_recommend'=>1];
        $orderBy = [];
        if (empty($province_id) && empty($city_id) && empty($district_id) && $all != 1) {
            $province_id = cookie('province_id');
            $city_id = cookie('city_id');
            $district_id = cookie('district_id');
        }
        if($province_id){
            $storeWhere['province_id'] = $province_id;
        }
        if($city_id){
            $storeWhere['city_id'] = $city_id;
        }
        if($sc_id > 0){
            $storeWhere['sc_id'] = $sc_id;
        }
        if($order){
            $orderBy['store_sales'] = 'asc';
        }else{
            $orderBy['store_sales'] = 'desc';
        }
        $store_count = Db::name('store')->where($storeWhere)->count();
        $page = new Page($store_count, 10);
        $store_list = Db::name('store')
            ->field("store_id,store_avatar,store_name,store_collect,store_desccredit,store_servicecredit,store_deliverycredit")
            ->where($storeWhere)->order($orderBy)->limit($page->firstRow, $page->listRows)->select();
        if($store_list){
            $store_list = collection($store_list)->toArray();
            foreach ($store_list as $key => $value) {
                $store_list[$key]['goods_list'] = Db::name('goods')->field("goods_id,goods_name,shop_price,is_virtual")
                    ->where(['is_on_sale'=>1, 'goods_state'=>1,'store_id'=>$value['store_id']])->limit(3)->order('sort desc')->select();
                if(cookie('user_id') > 0){
                    $store_list[$key]['log_id'] = Db::name('store_collect')
                        ->where(['user_id'=>cookie('user_id'),'store_id'=>$value['store_id']])->value('log_id');
                }

            }
        }
        $this->assign('province_id', $province_id);
        $this->assign('city_id', $city_id);
        $this->assign('district_id', $district_id);
        $this->assign('store_list', $store_list);
        echo $this->fetch();

    }

    /**
     * 品牌街
     * @author dyr
     * @time 2016/08/15
     */
    public function brand()
    {
        $brand_where['status'] = 0;
        $brand_where['is_hot'] = 1;
        $goods = M('goods')->field('goods_id,shop_price,market_price')->where(['is_on_sale' => 1, 'is_recommend' => 1])->limit(3)->order('sort desc')->select();
        $brand_list = M('brand')->field('id,name,logo,url')->order(array('sort' => 'desc'))->cache(true)->where($brand_where)->select();
        for ($i = 0; $i < 3; $i++) {
            $Goods_group[] = array_slice($goods, $i * 3, 3);//每三个一组，取三组
            if (!empty($Goods_group[$i])) { //去掉空的
                $recommendGoods = $Goods_group;
            }
        }
        $this->assign('brand_list', $brand_list);//品牌列表
        $this->assign('recommendGoods', $recommendGoods);//品牌列表
        return $this->fetch();
    }

    //微信Jssdk 操作类 用分享朋友圈 JS
    public function ajaxGetWxConfig()
    {
        $askUrl = input('askUrl');//分享URL
        $askUrl = urldecode($askUrl);

        $wechat = new WechatUtil;
        $signPackage = $wechat->getSignPackage($askUrl);
        if (!$signPackage) {
            exit($wechat->getError());
        }

        $this->ajaxReturn($signPackage);
    }
    
    /**
     * APP下载地址, 如果APP不存在则显示WAP端地址
     * @return \think\mixed
     */
    public function app_down(){
         
        $server_host = 'http://'.$_SERVER['HTTP_HOST'];
        $showTip = false;
        if(tpCache('ios.app_path') && strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){
            //苹果:直接指向AppStore下载
            $down_url = tpCache('ios.app_path');
        }else if(tpCache('android.app_path') && strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){
            // 安卓:需要拼接下载地址
            $down_url = $server_host.'/'.tpCache('android.app_path');
            //如果是安卓手机微信打开, 则显示"其他浏览器打开"提示
            (strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') && strpos($_SERVER['HTTP_USER_AGENT'], 'Android')) && $showTip = true;
        } 
        
        $wap_url = $server_host.'/Mobile';
       /*  echo "down_url : ".$down_url;
        echo "wap_url : ".wap_url;
        echo "<br/>showTip : ".$showTip; */
        $this->assign('showTip' , $showTip);
        $this->assign('down_url' , $down_url);
        $this->assign('wap_url' , $wap_url);
        return $this->fetch();
    }
}