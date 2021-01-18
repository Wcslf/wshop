<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * $Author: wangqh 2018-04-10 $ 移动端相关协议
 */ 
namespace app\api\controller;
use  think\Db;
 
class Article extends Base {
   
    /**
     * @param doc_id agreement:用户服务协议, open_store:开店协议 
     * @return \think\mixed
     */
    public function service_agreement(){
        $doc_code = I('doc_code/s', '');
        $article = Db::name('system_article')->where('doc_code',$doc_code)->find();
        $this->assign("article" , $article);
        return $this->fetch();
    }
   
    
}