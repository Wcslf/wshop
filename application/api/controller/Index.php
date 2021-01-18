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
use app\common\logic\GoodsLogic;
use app\common\model\Store;
use app\common\model\team\TeamActivity;
use app\common\model\team\TeamFound;
use think\Db;
use think\Page;
class Index extends Base {

    public function index(){
        return $this->fetch();
    }

    //自定义首页内容
    public function block_index(){
        $arr = M('mobile_template')->where('is_index=1')->field('block_info')->find();
        if(!$arr){
            $this->ajaxReturn(array(
                'status'=>0,
                'msg'=>'获取失败',
                'result'=>'未设置自定义首页,请启用系统默认首页'
            ));
            exit();
        }

        $arr=$arr['block_info'];
        $info=json_decode(htmlspecialchars_decode(htmlspecialchars_decode($arr)));//这里不知道为毛url要反序列化两遍才正常 
        $info_array=$this->object_array($info);//转为数组

        //橱窗类型数组处理
        foreach ($info_array as $k => $v) {
            if(is_array($v)){
                if($v['block_type']==4){
                    if($v['window_style']==0){
                        unset($info_array[$k]['nav'][1]);
                        unset($info_array[$k]['nav'][2]);
                        unset($info_array[$k]['nav'][3]);
                    }
                    if($v['window_style']==1){
                        unset($info_array[$k]['nav'][2]);
                        unset($info_array[$k]['nav'][3]);
                    }
                    if(($v['window_style']>1) && ($v['window_style']<5)){
                        unset($info_array[$k]['nav'][3]);
                    }
                }
            }
        }

        foreach ($info_array as $k => $v) {
            if(is_array($v)){
                switch ($v['block_type']) {
                    case '0'://海报
                        $tmp=$this->get_url($v['url']);
                        $info_array[$k]['app_url']=$tmp['info'];
                        break;

                    case '1'://轮播广告
                        foreach ($v['nav'] as $k2 => $v2) {
                            $tmp=$this->get_url($v2['url']);
                            $info_array[$k]['nav'][$k2]['url_type']=$tmp['url_type'];
                            $info_array[$k]['nav'][$k2]['app_url']=$tmp['info'];
                        }
                        break;

                    case '2'://快捷入口
                        foreach ($v['nav'] as $k2 => $v2) {
                            $tmp=$this->get_url(htmlspecialchars_decode($v2['url']));
                            $info_array[$k]['nav'][$k2]['url_type']=$tmp['url_type'];
                            $info_array[$k]['nav'][$k2]['app_url']=$tmp['info'];
                        }
                        break;

                    case '3'://商品列表
                        foreach ($v['nav'] as $k2 => $v2) {
                            $tmp=$this->goods_list_block($v2['sql_where'],$v['num']);
                            $info_array[$k]['nav'][$k2]['goods_list']=$tmp;
                        }
                        break;

                    case '4'://橱窗    
                        foreach ($v['nav'] as $k2 => $v2) {
                            $tmp=$this->get_url(htmlspecialchars_decode($v2['url']));
                            $info_array[$k]['nav'][$k2]['url_type']=$tmp['url_type'];
                            $info_array[$k]['nav'][$k2]['app_url']=$tmp['info']; 
                        }
                        break;
                    case '10'://公告
                        foreach ($v['nav'] as $k2 => $v2) {
                            $tmp=$this->get_url(htmlspecialchars_decode($v2['url']));
                            $info_array[$k]['nav'][$k2]['url_type']=$tmp['url_type'];
                            $info_array[$k]['nav'][$k2]['app_url']=$tmp['info']; 
                        }
                        break;
                    case '6'://营销活动
                        if($v['activity_type']==0){//拼团
                            $info_array[$k]['team_list']=$this->team_list();
                        }
                        if($v['activity_type']==1){//秒杀
                            $tmp=$this->get_flash_sale_goods();
                            $info_array[$k]['flash_sale_list']=$tmp['list'];
                            $info_array[$k]['server_time']=time();
                            $info_array[$k]['start_time']=$tmp['time']['start_time'];
                            $info_array[$k]['end_time']=$tmp['time']['end_time'];
                        }
                        break;
                    case '5':
                        $tmp=$this->get_url(htmlspecialchars_decode($v['url']));
                        $info_array[$k]['url_type']=$tmp['url_type'];
                        $info_array[$k]['app_url']=$tmp['info'];
                        break;
                    default:

                        break;
                }
            }else{
                //不是数组时的处理
            }
        }

        foreach ($info_array as $k => $v) {
            if(is_array($v)){
                $info_array['blocks'][]=$v;
                unset($info_array[$k]);
            }
        }

        $this->ajaxReturn(array(
            'status'=>1,
            'msg'=>'获取成功',
            'result'=>$info_array
        ));
    }
 
    //object类型转化为array类型方便处理
    public function object_array($array){
        if(is_object($array)){
            $array = (array)$array;
        }
        if(is_array($array)){
            foreach($array as $key=>$value){
                $array[$key] = $this->object_array($value);
            }
        }
        return $array;
    }

    //获取url(无url_type参数时)
    public function get_url($url=''){
        $url=htmlspecialchars_decode($url);
        $arr=array('url_type'=>'','info'=>'');
        $header=substr($url,0,10);

        if($header=='/index.php'){
            if((strpos($url,'goodsList/id'))!==false){//属于分类链接
                $a=explode('/id',$url);
                $a=explode('.', $a[1]);
                $arr['url_type']=2;
                $arr['info']=substr($a[0],1);
            }elseif((strpos($url,'a=goodsInfo'))!==false){//属于商品详情链接
                $a=explode('id=', $url);
                $arr['url_type']=3;
                $arr['info']=$a[1];
            }elseif((strpos($url,'index2/id'))!==false){//自定义页面链接
                $a=explode('/id', $url);
                $a=explode('/', $a[1]);
                $arr['url_type']=4;
                $arr['info']=$a[1];
            }else{
                $arr['url_type']=1;
                $arr['info']=$url; 
            }
        }else{
            $arr['url_type']=0;
            $arr['info']=$url;  
        }
        return $arr;
    }

    //获取商品列表
    public function goods_list_block($data=array(),$count){
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
        return $goodsList;
    }

    //获取拼团数据
    public function team_list(){
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
                $result=array();
                return $result;
                exit();
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
        return(collection($list)->toArray());
    }

    //获取抢购数据
    public function get_flash_sale_goods(){
        $time_space = flash_sale_time_space();
        //dump($time_space);exit();

        $time_arr = $time_space[1];//获取当前时间节点的请购信息

        
        $goodsLogic = new GoodsLogic();
        $flash_sale_goods = $goodsLogic->getFlashSaleGoods(3 ,1 , $time_arr['start_time'], $time_arr['end_time']);
        $arr=array();
        $arr['time']=$time_arr;
        $arr['list']=$flash_sale_goods;

        return $arr;
    }


    /**
     * 获取首页数据
     */
    public function homePage()
    {
        $new_ad = I('new_ad',0); 
        $goodsLogic = new GoodsLogic(); 
        if($new_ad == 1){//新版新增广告模式  
            $banners =  $goodsLogic->getAppHomeAdv(true);
            foreach ($banners as $k => $v){
                if($v['media_type'] == 4){//如果是分类, 截取最后一个分类
                    $cats = explode('_',$v['ad_link']);
                    $count = count($cats);
                    if($count == 0)continue;
                    $v['ad_link'] = $cats[$count-1];
                    $banners[$k] = $v;
                }
            }
            $advs =  $goodsLogic->getAppHomeAdv(false);
            foreach ($advs as $k => $v){
                if($v['media_type'] == 4){//如果是分类, 截取最后一个分类
                    $cats = explode('_',$v['ad_link']);
                    $count = count($cats); 
                    if($count == 0)continue;
                    $v['ad_link'] = $cats[$count-1];
                    $advs[$k] = $v;
                }
            }
           
            $time_space = flash_sale_time_space();
            $time_arr = $time_space[1];//获取当前时间节点的请购信息
             
            $flash_sale_goods = $goodsLogic->getFlashSaleGoods(3 ,1 , $time_arr['start_time'], $time_arr['end_time']);
            $this->ajaxReturn(array(
                'status'=>1,
                'msg'=>'获取成功 11',
                'result'=>array(
                    'banner'=>$banners,
                    'ad'=>empty($advs) ? array() : $advs,
                    'flash_sale_goods' => $flash_sale_goods,
                    'server_time'=>time(),
                ),
            ));
        } 
        
        $promotion_goods = $goodsLogic->getPromotionGoods();
        $high_quality_goods = $goodsLogic->getRecommendGoods(1);
        $flash_sale_goods = $goodsLogic->getFlashSaleGoods(3);
        $new_goods = $goodsLogic->getNewGoods();
        $advs =  $goodsLogic->getHomeAdv();
        foreach ($advs as &$adv) {
            $adv['ad_code'] = SITE_URL.$adv['ad_code'];
        }
        $this->ajaxReturn(array(
            'status'=>1,
            'msg'=>'获取成功',
            'result'=>array(
               'promotion_goods'=>$promotion_goods,
               'high_quality_goods'=>$high_quality_goods,
               'flash_sale_goods' => $flash_sale_goods,
               'new_goods'=>$new_goods,
                'server_time'=>time(),
               'ad'=>$advs
            ),
        ));
    }
    
  
    /**
     * 推荐的商品列表
     */
    public function recommend()
    {
        $p = I('p/d',1);
        $goodsLogic = new GoodsLogic();
        $json = [
            'status'=>1,
            'msg'=>'获取成功',
            'result' => $goodsLogic->getRecommendGoods($p),
        ];
       $this->ajaxReturn($json);
    }

    /**
     * 猜你喜欢: 根据经纬度, 返回距离由近到远的商品
     */
    public function favourite()
    {
       $p = I('p',1);
        
        $lng =trim(I('lng/s',114.067345));  //经度
        $lat =trim(I('lat/s',22.632611));    //纬度   
  
        $count= Db::query("SELECT COUNT(store_id) as num  FROM __PREFIX__store WHERE store_state = 1");//正常店铺
        $Page=new Page($count[0]['num'],10);
        $firstRow = ($p-1)*10;
        $goods_list = Db::query("SELECT g.goods_id, goods_name,is_virtual,shop_price,cat_id3, s.store_id , ROUND(SQRT((POW((($lng - longitude)* 111),2))+ (POW((($lat - latitude)* 111),2))),2) AS distance FROM __PREFIX__goods AS g INNER JOIN __PREFIX__store AS s
                                            ON g.`store_id` = s.store_id  AND store_state=1 AND is_recommend=1 AND g.goods_state=1 AND  g.is_on_sale=1 ORDER BY distance ASC  LIMIT {$firstRow},{$Page->listRows} ");
        
        $json = array(
            'status'=>1,
            'msg'=>'获取成功',
            'result' => array(
                'favourite_goods'=>$goods_list,
            ),
        );    
        
       $this->ajaxReturn($json);
    }

    /**
     * 获取服务器配置
     */
    public function getConfig()
    {
        $data = M('plugin')->where("type='login' and code in ('weixin','qq')")->select();
        $arr = array();
        foreach($data as $k=>$v){
            unset( $data[$k]['config']);
        
			if(!$v['config_value']){
				$data[$k]['config_value'] = "";
			}else{
				$data[$k]['config_value'] = unserialize($v['config_value']);
			}
		 
            if($data[$k]['type'] == 'login'){
                $arr['login'][] =  $data[$k];
            }
        } 
        $is_block_index = M('mobile_template')->where('is_index=1')->count();
        $is_block_index=array('id'=>'','name'=>'is_block_index','value'=>$is_block_index,'inc_type'=>'','desc'=>'');
        
        $config_name = ['im_choose','qq', 'qq2', 'qq3', 'store_name', 'point_rate', 'phone',
            'address','hot_keywords', 'app_test', 'sms_time_out', 'regis_sms_enable', 
            'forget_pwd_sms_enable', 'bind_mobile_sms_enable','integral_use_enable' , 'wap_home_logo' ,  'auto_service_date'];
        $inc_type = ['ios','app'];
        $config = M('config')->where('name', 'IN', $config_name)->whereOr('inc_type' , 'IN' , $inc_type)->select();
        $config[]=$is_block_index;
        $result = ['config' => $config] + $arr;
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result]);
    }

    /**
     * 店铺街
     * @author dyr
     * @time 2016/08/15
     * 根据百度坐标，获取周边商家
     *  $lng 经度
     *  $lat 纬度
     *  $scope 范围  千米
     *  $fourpoint
     * */
    public function store_street()
    {
        $sc_id = I('get.sc_id/d', '');
        $p = I('get.p',1);
        $lng =trim(I('lng/s',114.067345));  //经度
        $lat =trim(I('lat/s',22.632611));    //纬度
        $order = I('sale_order', 0);
        $search_key = I('search_key', 0);//搜索关键词
        $city = I('city', '');
          
        if($sc_id > 0){
            $storeWhere['sc_id'] = $sc_id;
        }
        $storeWhere['sc_id'] = $sc_id;
        if($order){
            $orderBy['store_sales'] = 'asc';
        }else{
            $orderBy['store_sales'] = 'desc';
        }
        
        $storeWhere = ['store_state' => 1,'deleted'=>0,'store_recommend'=>1];
         
        //查找城市对应的地区id
        if(!empty($city)){
            if(strpos($city,"市") > 0){
                $cityOr = str_replace('市','',$city);
            }else{
                $cityOr = $city.'市';
            }
            $cityRegionId = M('Region')->where(['name'=>$city])->whereOr(['name'=>$cityOr])->getField('id');
            //地区ID,目前搜索时只精确到市
            $storeWhere['city_id'] = $cityRegionId;
        }
        if(!empty($search_key)){
            $storeWhere['store_name'] = ['like' , "%$search_key%"];
        }
        $Store = new Store();
        $store_list = $Store->field('store_id,store_avatar,store_name,store_collect,store_desccredit,province_id,city_id,district'.'
                store_servicecredit,longitude,latitude,store_deliverycredit,round(SQRT((POW((('.$lng.' - longitude)* 111),2))+  (POW((('.$lat.' - latitude)* 111),2))),2) AS distance')
            ->where($storeWhere)->page($p,10)->order($orderBy)->select();
        if($store_list){
            $store_list = collection($store_list)->toArray();
//            $distance = convert_arr_key($store_list,"store_id");
            //遍历获取店铺的四个商品数据
            foreach ($store_list as $key => $value) {
                $region = Db::name('region')->where('id','in',[$value['province_id'],$value['city_id'],$value['district']])->order('level asc')->select();
                $store_list[$key]['province_name'] = $region[0]['name'];
                $store_list[$key]['city_name'] = $region[0]['name'];
                $store_list[$key]['district_name'] = $region[0]['name'];
                $store_list[$key]['cartList'] = Db::name('goods')->field("goods_id,goods_name,shop_price,is_virtual")
                    ->where([ 'is_on_sale'=>1, 'goods_state'=>1,'store_id'=>$value['store_id']])->limit(4)->order('sort desc')->select();
                $store_list[$key]['store_count'] = Db::name('goods')->where(['store_id'=>$value['store_id']])->count();
                $log_id = Db::name('store_collect')
                    ->where(['user_id'=>$this->user_id,'store_id'=>$value['store_id']])->value('log_id');
                $store_list[$key]['is_collect'] = $log_id ? 1 : 0;
                if ($value['longitude']<=0 && $value['latitude']<=0){
                    $store_list[$key]['distance'] = 0;
                }
            }
        }

        $result['store_list'] = $store_list;

        if ($p <= 1) {
            $result['store_class'] = M('store_class')->field('sc_id,sc_name')->select();
            array_unshift($result['store_class'], ['sc_id' => 0, 'sc_name' => '全部分类']);

            //查找广告
            $start_time = strtotime(date('Y-m-d H:00:00'));
            $end_time = strtotime(date('Y-m-d H:00:00'));
            $adv = M("ad")->field(array('ad_link','ad_name','ad_code','media_type,pid'))->where("pid=535 AND enabled=1 and start_time< $start_time and end_time > $end_time")->find();
            if($adv && $adv['media_type'] == 4){//如果是分类, 截取最后一个分类
                $cats = explode('_',$adv['ad_link']);
                $count = count($cats);
                if($count != 0){
                    $adv['ad_link'] = $cats[$count-1];
                }
            }

            $result['ad'] = empty($adv) ? "" : $adv ;
        }

        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result]);
    }

    /**
     * 小程序的店铺街
     */
    public function store_street_list()
    {
        $p = I('p',1);
        $sc_id = I('get.sc_id/d',0);
        $province_id = I('province_id', 0);
        $city_id = I('city_id', 0);
        $order = I('sale_order', 0);

        //地区ID,目前搜索时只精确到市
        $storeWhere = [];
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
        $Store = new Store();
        $store_list = $Store->where($storeWhere)->order($orderBy)->select();
        foreach($store_list as $storeKey=>$storeVal){
            $store_list[$storeKey]['cartList'] = Db::name('goods')->field("goods_id,goods_name,shop_price,is_virtual")
                ->where([ 'is_on_sale'=>1, 'goods_state'=>1,'store_id'=>$storeVal['store_id']])->limit(4)->order('sort desc')->select();
            $store_list[$storeKey]['store_count'] = Db::name('goods')->where(['store_id'=>$storeVal['store_id']])->count();
            $region = Db::name('region')->where('id','in',[$storeVal['province_id'],$storeVal['city_id'],$storeVal['district']])->order('level asc')->select();
            $store_list[$storeKey]['province_name'] = $region[0]['name'];
            $store_list[$storeKey]['city_name'] = $region[0]['name'];
            $store_list[$storeKey]['district_name'] = $region[0]['name'];
            $log_id = Db::name('store_collect')
                ->where(['user_id'=>$this->user_id,'store_id'=>$storeVal['store_id']])->value('log_id');
            $store_list[$storeKey]['is_collect'] = $log_id ? 1 : 0;
            $store['distance'] = 0;
        }

        $result['store_list'] = $store_list;
        
        if ($p <= 1) {
            $result['store_class'] = M('store_class')->field('sc_id,sc_name')->select();
            array_unshift($result['store_class'], ['sc_id' => 0, 'sc_name' => '全部分类']);
            $result['ad'] = M('ad')->field(['ad_link','ad_name','ad_code'])->where('pid', 2)->cache(true, TPSHOP_CACHE_TIME)->find();
        }
        
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result]);
    }

    /**
     * 店铺分类
     */
    public function store_class()
    {
        $store_class = M('store_class')->field('sc_id,sc_name')->select();
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $store_class]);
    }

    /**
     * 品牌街
     * @author dyr
     * @time 2016/08/15
     */
    public function brand_street()
    {
        $p = I('get.p', 1);
        
        $brand_list = M('brand')->field('id,name,logo,url')
                ->where(['is_hot' => 1])
                ->order(['sort' => 'desc', 'id' => 'asc'])
                ->where('status', 0)
                ->page($p, 30)
                ->select();
        $result['brand_list'] = $brand_list;
        
        if ($p <= 1) {
            $goodsLogic = new GoodsLogic();
            //查找广告
            $start_time = strtotime(date('Y-m-d H:00:00'));
            $end_time = strtotime(date('Y-m-d H:00:00'));
            $adv = M("ad")->field(array('ad_link','ad_name','ad_code','media_type,pid'))->where("pid=533 AND enabled=1 and start_time< $start_time and end_time > $end_time")->find();
            if($adv && $adv['media_type'] == 4){//如果是分类, 截取最后一个分类
                    $cats = explode('_',$adv['ad_link']);
                    $count = count($cats);
                    if($count != 0){
                        $adv['ad_link'] = $cats[$count-1];
                    }
             }
        
            $result['ad'] = empty($adv) ? "" : $adv ;
            $result['hot_list'] = $goodsLogic->getBrandGoods(12);
        }

        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result]);
    }

    /**
     * 获取区域地址列表，region_id=0是获取所有省份
     */
    public function get_region()
    {
        $parent_id = I('get.parent_id/d', 0);
        $data = M('region')->field('id,name')->where("parent_id", $parent_id)->select();
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $data]);
    }
}