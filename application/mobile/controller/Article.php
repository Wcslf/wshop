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
namespace app\mobile\controller;

use think\Db;
use app\common\model\WxNews;
 
class Article extends MobileBase
{
    /**
     * 文章内容页
     */
    public function detail()
    {
        $article_id = input('article_id/d', 1);
        $article = Db::name('article')->where("article_id", $article_id)->find();
        $this->assign('article', $article);
        return $this->fetch();
    }

    public function news()
    {
        $id = input('id');
        if (!$news = WxNews::get($id)) {
            $this->error('文章不存在了~', null, '', 100);
        }

        $news->content = htmlspecialchars_decode($news->content);
        $this->assign('news', $news);
        return $this->fetch();
    }
    
    public function agreement(){
    	$doc_code = I('doc_code','agreement');
    	$article = db::name('system_article')->where('doc_code',$doc_code)->find();
    	if(empty($article)) $this->error('抱歉，您访问的页面不存在！');
    	$this->assign('article',$article);
    	return $this->fetch();
    }
}