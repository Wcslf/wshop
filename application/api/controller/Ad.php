<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * $Author: 二月鸟飞 2019-03-1 $ 广告相关API
 */ 
namespace app\api\controller;
 
class Ad extends Base {
   
    public function ad_home()
    {
        /**
         * TPshop APP端广告位PID 区间是: 500 ~ 549
         * 首页: 500 -> 520
         * 分类: 531 ;  店铺街:532; 品牌街:533;   团购:534;  积分商城:535;
         * media_type: 3:商品;4:分类;5:url
         */
        
        return $this->fetch();
    }
    
    public function ad_category()
    {
        return $this->fetch();
    }
    
    public function ad_common()
    { 
        return $this->fetch();
    }
    
}