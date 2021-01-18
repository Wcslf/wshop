<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------
 *商业用途务必到官方购买正版授权, 使用盗版将严厉追究您的法律责任。
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * $Author: 二月鸟飞 2019-03-1 $
 */ 
namespace app\home\controller;
use think\Controller;
use think\Cookie;
use think\Session;
use think\Db;
class Base extends Controller {
    public $session_id;
    public $cateTrre = array();
	public $seo;
    public function __construct()
    {
        parent::__construct();
    }

    /*
     * 初始化操作
     */
    public function _initialize() {
        if (input("unique_id")) {           // 兼容手机app
            session_id(input("unique_id"));
            Session::start();
        }
        header("Cache-control: private");  // history.back返回后输入框值丢失问题 参考文章 http://www.phpyuanmashe.com/article_id_1465.html  http://blog.csdn.net/qinchaoguang123456/article/details/29852881
    	$this->session_id = session_id(); // 当前的 session_id
        define('SESSION_ID',$this->session_id); //将当前的session_id保存为常量，供其它方法调用        
        $first_leader = I('first_leader');
        if($first_leader) setcookie('first_leader',$first_leader);
        $this->public_assign();
        $this->get_seo();
        $this->redirect_store();
    }
    
    public  function redirect_store(){
       
        $site_domain = tpCache('shop_info.site_domain');
        //启用店铺二级域名
        if(!$site_domain)return;
                
        $secondary_prefix = get_secondary_prefix();
        //如果是主域名则直接访问
        if(empty($secondary_prefix) || $secondary_prefix === 'MASTER')return ;
         
        //如果是店铺二级域名首页则需要重定向
        if($_SERVER['REQUEST_URI'] == '/' || $_SERVER['REQUEST_URI'] == '/index.php'){
            $store_id = M('Store')->where(['store_domain'=>$secondary_prefix , 'domain_enable'=>1])->cache(true)->getField('store_id');
            !empty($store_id) && $redirectUrl = 'http://'.$_SERVER['HTTP_HOST'].U('Home/Store/index',['store_id'=>$store_id]);
        }else if(strtolower(CONTROLLER_NAME) != 'store'){
            //如果二级域名访问控制器不是stroe则重定向
            $redirectUrl = $site_domain.$_SERVER['REQUEST_URI'];
        }
   
        if($redirectUrl){
            header("Location:$redirectUrl");
            exit();
        }         
        
    }
    
    /**
     * 保存公告变量到 smarty中 比如 导航 
     */
    public function public_assign()
    {                             
       $tpshop_config = $this->get_tpshop_config();
       $this->assign('tpshop_config', $tpshop_config);
       $goods_category_tree = get_goods_category_tree();    
       $this->cateTrre = $goods_category_tree;
       $this->assign('goods_category_tree', $goods_category_tree);                     
       $brand_list = $this->get_brand_list();          
       $this->assign('brand_list', $brand_list);
       $user = session('user');
       $this->assign('username',$user['nickname']);
       $this->assign('user',$user);
       //PC端首页"手机端、APP二维码"
        $store_logo =  tpCache('shop_info.store_logo');
        $store_logo ? $head_pic = $store_logo: $head_pic ='/public/static/images/logo/pc_home_logo_default.png';
        $mobile_url = "http://{$_SERVER['HTTP_HOST']}".U('Mobile/index/app_down');
        $this->assign('head_pic', "http://{$_SERVER['HTTP_HOST']}/".$head_pic);
        $this->assign('mobile_url', $mobile_url);
    }
    
    private function get_seo(){
    	$seo_config = Db::name('seo')->cache(true,TPSHOP_CACHE_TIME)->getField('type,title,keywords,description');
    	if(isset($seo_config[ACTION_NAME])){
    		$store_name = tpCache('shop_info.store_name');
    		foreach ($seo_config[ACTION_NAME] as $key=>$value) {
    			$this->seo[$key] = str_replace(array('{sitename}'),array($store_name),$value);
    		}
    		$this->seo['title'] = preg_replace("/{.*}/siU",'',$this->seo['title']);
    		$this->seo['keywords'] = preg_replace("/{.*}/siU",'',$this->seo['keywords']);
    		$this->seo['description'] = preg_replace("/{.*}/siU",'',$this->seo['description']);
    	}else{
    		$this->seo['title'] =  tpCache('shop_info.store_title');
    		$this->seo['keywords'] =  tpCache('shop_info.store_keyword');
    		$this->seo['description'] = tpCache('shop_info.store_desc');
    	}
    	$this->assign('seo',$this->seo);
    }

    /*
     *
     */
    public function ajaxReturn($data){
        exit(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * 获取TPshop 配置信息
     * @return type
     */
    public function get_tpshop_config(){
        
       $tpshop_config = S('get_tpshop_config'); 
       if($tpshop_config) return $tpshop_config; 
       
       $tp_config = Db::name('config')->cache(true)->select();       
       foreach($tp_config as $k => $v)
       {
       	  if($v['name'] == 'hot_keywords'){
       	  	 $tpshop_config['hot_keywords'] = explode('|', $v['value']);
       	  }       	  
          $tpshop_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
       } 
       S('get_tpshop_config',$tpshop_config); 
       return $tpshop_config;
    }
    
    /**
     * 获取热门品牌
     */
    function get_brand_list(){
        
       $brand_list = S('base_get_brand_list'); 
       if($brand_list)
           return $brand_list;
       
       $brand_list =  Db::name('brand')->cache(true)->field('id,cat_id1,logo,is_hot')->where("cat_id1>0")->select(); 
       
       S('base_get_brand_list',$brand_list); 
       return $brand_list;  
    }
}