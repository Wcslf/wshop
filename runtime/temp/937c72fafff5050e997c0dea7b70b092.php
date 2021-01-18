<?php if (!defined('THINK_PATH')) exit(); /*a:5:{s:42:"./template/mobile/default/index/index.html";i:1540447902;s:74:"/www/wwwroot/shop.zequninfo.com/template/mobile/default/public/header.html";i:1532948114;s:74:"/www/wwwroot/shop.zequninfo.com/template/mobile/default/public/footer.html";i:1610685247;s:78:"/www/wwwroot/shop.zequninfo.com/template/mobile/default/public/footer_nav.html";i:1540447902;s:76:"/www/wwwroot/shop.zequninfo.com/template/mobile/default/public/wx_share.html";i:1532948114;}*/ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>首页--<?php echo $tpshop_config['shop_info_store_title']; ?></title>
    <link rel="stylesheet" href="/template/mobile/default/static/css/style.css">
    <link rel="stylesheet" type="text/css" href="/template/mobile/default/static/css/iconfont.css"/>
    <script src="/template/mobile/default/static/js/jquery-3.1.1.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="/template/mobile/default/static/js/mobile-util.js" type="text/javascript" charset="utf-8"></script>
    <script src="/public/js/global.js"></script>
    <script src="/template/mobile/default/static/js/layer/layer.js" type="text/javascript" charset="utf-8"></script>
    <script src="/template/mobile/default/static/js/swipeSlide.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="/public/js/mobile_common.js"></script>
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo (isset($tpshop_config['shop_info_store_ico']) && ($tpshop_config['shop_info_store_ico'] !== '')?$tpshop_config['shop_info_store_ico']:'/public/static/images/logo/storeico_default.png'); ?>" media="screen"/>
</head>
<body class="[body]">

    <!--顶部搜索栏-s-->
    <header>
        <div class="content">
            <div class="ds-in-bl search">
                <div class="sea-box">
                    <div class="logo">
                        <a href=""><img src="<?php echo (isset($tpshop_config['shop_info_wap_home_logo']) && ($tpshop_config['shop_info_wap_home_logo'] !== '')?$tpshop_config['shop_info_wap_home_logo']:'/public/static/images/logo/wap_home_logo_default.png'); ?>" alt="LOGO"></a>
                    </div>
                    <span ></span>
                    <form action=""  method="post">
                        <div class="sear-input">
                            <a href="<?php echo U('Goods/ajaxSearch'); ?>">
                                <input type="text" name="q" id="search_text" class="search_text"   value="" placeholder="请输入您所搜索的商品">
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="ds-in-bl login">
                <?php if($user_id > 0): ?> <a href="<?php echo U('User/index'); ?>"><?php else: ?>
                <a href="<?php echo U('User/login'); ?>"><?php endif; ?>
                <span><?php if($user_id > 0): ?><img class="after_login" src="/template/mobile/default/static/images/my.png"><?php else: ?>登录<?php endif; ?></span>
                </a>
            </div>
        </div>
    </header>
    <!--顶部搜索栏-e-->

    <!--顶部滚动广告栏-s-->
    <div class="banner ban1">
        <div class="mslide" id="slideTpshop">
            <ul>
                <!--广告表-->
                <?php $pid =2;$ad_position = M("ad_position")->cache(true,TPSHOP_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("pid=$pid  and enabled = 1 and start_time < 1610683200 and end_time > 1610683200 ")->order("orderby desc")->cache(true,TPSHOP_CACHE_TIME)->limit("5")->select();
if(is_array($ad_position) && !in_array($pid,array_keys($ad_position)) && $pid)
{
  M("ad_position")->insert(array(
         "position_id"=>$pid,
         "position_name"=>CONTROLLER_NAME."页面自动增加广告位 $pid ",
         "is_open"=>1,
         "position_desc"=>CONTROLLER_NAME."页面",
  ));
  delFile(RUNTIME_PATH); // 删除缓存
  \think\Cache::clear();  
}


$c = 5- count($result); //  如果要求数量 和实际数量不一样 并且编辑模式
if($c > 0 && I("get.edit_ad"))
{
    for($i = 0; $i < $c; $i++) // 还没有添加广告的时候
    {
      $result[] = array(
          "ad_code" => "/public/images/not_adv.jpg",
          "ad_link" => "/index.php?m=Admin&c=Ad&a=ad&pid=$pid",
          "title"   =>"暂无广告图片",
          "not_adv" => 1,
          "target" => 0,
      );  
    }
}
foreach($result as $key=>$v):       
    
    $v[position] = $ad_position[$v[pid]]; 
    if(I("get.edit_ad") && $v[not_adv] == 0 )
    {
        $v[style] = "filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5"; // 广告半透明的样式
        $v[ad_link] = "/index.php?m=Admin&c=Ad&a=ad&act=edit&ad_id=$v[ad_id]";        
        $v[title] = $ad_position[$v[pid]][position_name]."===".$v[ad_name];
        $v[target] = 0;
    }
    ?>
                    <li><a href="<?php echo $v['ad_link']; ?>">
                        <img src="<?php echo $v[ad_code]; ?>" title="<?php echo $v[title]; ?>" style="<?php echo $v[style]; ?>" alt="">
                    </a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <!--顶部滚动广告栏-e-->

    <!--菜单-start-->
    <div class="floor dh">
        <nav>
            <a href="<?php echo U('Goods/categoryList'); ?>">
                <span>
                    <img src="/template/mobile/default/static/images/icon_03.png" alt="全部分类" /><br />
                    <span>全部分类</span>
                </span>
            </a>
            <a href="<?php echo U('Index/street'); ?>">
                <span>
                    <img src="/template/mobile/default/static/images/icon_05.png" alt="店铺街" /><br />
                    <span>店铺街</span>
                </span>
            </a>
            <a href="<?php echo U('Index/brand'); ?>">
                <span>
                    <img src="/template/mobile/default/static/images/icon_07.png" alt="品牌街" /><br />
                    <span>品牌街</span>
                </span>
            </a>
            <a href="<?php echo U('Activity/promote_goods'); ?>">
                <span>
                    <img src="/template/mobile/default/static/images/icon_09.png" alt="优惠活动" /><br />
                    <span>优惠活动</span>
                </span>
            </a>
            <a href="<?php echo U('Activity/group_list'); ?>">
                <span>
                    <img src="/template/mobile/default/static/images/icon_15.png" alt="团购" /><br />
                    <span>团购</span>
                </span>
            </a>
            <a href="<?php echo U('Activity/coupon_list'); ?>">
                <span>
                    <img src="/template/mobile/default/static/images/icon_16.png" alt="领券中心" /><br />
                    <span>领券中心</span>
                </span>
            </a>
            <!--<a href="shopcar.html">-->
            <a href="<?php echo U('Goods/integralMall'); ?>">
                <span>
                    <img src="/template/mobile/default/static/images/icon_17.png" alt="积分商城" /><br />
                    <span>积分商城</span>
                </span>
            </a>
            <!--<a href="my.html">-->
			<!--code_15拼团链接-->
            <a href="<?php echo U('Team/index'); ?>">
			<!--code_15拼团链接-->
                <span>
                    <img src="/template/mobile/default/static/images/icon_19.png" alt="我要拼团" /><br />
                    <span>我要拼团</span>
                </span>
            </a>
        </nav>
    </div>
    <!--菜单-end-->

<!--秒杀-start-->
<div class="floor secondkill">
    <div class="content">
        <div class="time p">
            <div class="djs lightning fl">
                <span class="add">秒杀</span>
                <span class="red" id=""><?php echo date('H',$start_time); ?>点场</span>
                <span class="hms"></span>
            </div>
            <div class="xsxl fr">
                <a href="<?php echo U('Mobile/Activity/flash_sale_list'); ?>">
                    <span>限时限量<img src="/template/mobile/default/static/images/or.png" alt="" /></span>
                </a>
            </div>
        </div>
        <div class="shop p">
            <?php if(count($flash_sale_list) == nll): ?>
                <div style="text-align: center;">暂无抢购商品。。。。</div>
            <?php endif; if(is_array($flash_sale_list) || $flash_sale_list instanceof \think\Collection || $flash_sale_list instanceof \think\Paginator): if( count($flash_sale_list)==0 ) : echo "" ;else: foreach($flash_sale_list as $key=>$v): ?>
                <!--<a href="<?php echo U('Mobile/Goods/goodsInfo',array('id'=>$v[goods_id],'item_id'=>$v[item_id])); ?>">-->
                <a href="<?php echo U('Mobile/Activity/flash_sale_list'); ?>">
                    <div class="timerBox shopnum">
                        <img src="<?php echo goods_thum_images($v[goods_id],200,200); ?>"/>
                        <p>￥<span><?php echo $v[price]; ?></span>元</p>
                    </div>
                </a>
            <?php endforeach; endif; else: echo "" ;endif; ?>
        </div>
    </div>
</div>
<!--秒杀-end-->

    <!--广告位-start-->
    <div class="banner ma-to-20">
        <?php $pid =400;$ad_position = M("ad_position")->cache(true,TPSHOP_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("pid=$pid  and enabled = 1 and start_time < 1610683200 and end_time > 1610683200 ")->order("orderby desc")->cache(true,TPSHOP_CACHE_TIME)->limit("1")->select();
if(is_array($ad_position) && !in_array($pid,array_keys($ad_position)) && $pid)
{
  M("ad_position")->insert(array(
         "position_id"=>$pid,
         "position_name"=>CONTROLLER_NAME."页面自动增加广告位 $pid ",
         "is_open"=>1,
         "position_desc"=>CONTROLLER_NAME."页面",
  ));
  delFile(RUNTIME_PATH); // 删除缓存
  \think\Cache::clear();  
}


$c = 1- count($result); //  如果要求数量 和实际数量不一样 并且编辑模式
if($c > 0 && I("get.edit_ad"))
{
    for($i = 0; $i < $c; $i++) // 还没有添加广告的时候
    {
      $result[] = array(
          "ad_code" => "/public/images/not_adv.jpg",
          "ad_link" => "/index.php?m=Admin&c=Ad&a=ad&pid=$pid",
          "title"   =>"暂无广告图片",
          "not_adv" => 1,
          "target" => 0,
      );  
    }
}
foreach($result as $key=>$v):       
    
    $v[position] = $ad_position[$v[pid]]; 
    if(I("get.edit_ad") && $v[not_adv] == 0 )
    {
        $v[style] = "filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5"; // 广告半透明的样式
        $v[ad_link] = "/index.php?m=Admin&c=Ad&a=ad&act=edit&ad_id=$v[ad_id]";        
        $v[title] = $ad_position[$v[pid]][position_name]."===".$v[ad_name];
        $v[target] = 0;
    }
    ?>
            <a href="<?php echo $v['ad_link']; ?>">
            <img src="<?php echo $v[ad_code]; ?>" title="<?php echo $v[title]; ?>" style="<?php echo $v[style]; ?>"/>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="floor advertisement">
        <div class="content">
            <div class="le lefhe fl">
                <?php $pid =301;$ad_position = M("ad_position")->cache(true,TPSHOP_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("pid=$pid  and enabled = 1 and start_time < 1610683200 and end_time > 1610683200 ")->order("orderby desc")->cache(true,TPSHOP_CACHE_TIME)->limit("1")->select();
if(is_array($ad_position) && !in_array($pid,array_keys($ad_position)) && $pid)
{
  M("ad_position")->insert(array(
         "position_id"=>$pid,
         "position_name"=>CONTROLLER_NAME."页面自动增加广告位 $pid ",
         "is_open"=>1,
         "position_desc"=>CONTROLLER_NAME."页面",
  ));
  delFile(RUNTIME_PATH); // 删除缓存
  \think\Cache::clear();  
}


$c = 1- count($result); //  如果要求数量 和实际数量不一样 并且编辑模式
if($c > 0 && I("get.edit_ad"))
{
    for($i = 0; $i < $c; $i++) // 还没有添加广告的时候
    {
      $result[] = array(
          "ad_code" => "/public/images/not_adv.jpg",
          "ad_link" => "/index.php?m=Admin&c=Ad&a=ad&pid=$pid",
          "title"   =>"暂无广告图片",
          "not_adv" => 1,
          "target" => 0,
      );  
    }
}
foreach($result as $key=>$v):       
    
    $v[position] = $ad_position[$v[pid]]; 
    if(I("get.edit_ad") && $v[not_adv] == 0 )
    {
        $v[style] = "filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5"; // 广告半透明的样式
        $v[ad_link] = "/index.php?m=Admin&c=Ad&a=ad&act=edit&ad_id=$v[ad_id]";        
        $v[title] = $ad_position[$v[pid]][position_name]."===".$v[ad_name];
        $v[target] = 0;
    }
    ?>
                    <a href="<?php echo $v['ad_link']; ?>">
                        <div class="td">
                            <img src="<?php echo $v[ad_code]; ?>" title="<?php echo $v[title]; ?>" style="<?php echo $v[style]; ?>">
                        </div>
                    </a>
                <?php endforeach; $pid =302;$ad_position = M("ad_position")->cache(true,TPSHOP_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("pid=$pid  and enabled = 1 and start_time < 1610683200 and end_time > 1610683200 ")->order("orderby desc")->cache(true,TPSHOP_CACHE_TIME)->limit("1")->select();
if(is_array($ad_position) && !in_array($pid,array_keys($ad_position)) && $pid)
{
  M("ad_position")->insert(array(
         "position_id"=>$pid,
         "position_name"=>CONTROLLER_NAME."页面自动增加广告位 $pid ",
         "is_open"=>1,
         "position_desc"=>CONTROLLER_NAME."页面",
  ));
  delFile(RUNTIME_PATH); // 删除缓存
  \think\Cache::clear();  
}


$c = 1- count($result); //  如果要求数量 和实际数量不一样 并且编辑模式
if($c > 0 && I("get.edit_ad"))
{
    for($i = 0; $i < $c; $i++) // 还没有添加广告的时候
    {
      $result[] = array(
          "ad_code" => "/public/images/not_adv.jpg",
          "ad_link" => "/index.php?m=Admin&c=Ad&a=ad&pid=$pid",
          "title"   =>"暂无广告图片",
          "not_adv" => 1,
          "target" => 0,
      );  
    }
}
foreach($result as $key=>$v):       
    
    $v[position] = $ad_position[$v[pid]]; 
    if(I("get.edit_ad") && $v[not_adv] == 0 )
    {
        $v[style] = "filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5"; // 广告半透明的样式
        $v[ad_link] = "/index.php?m=Admin&c=Ad&a=ad&act=edit&ad_id=$v[ad_id]";        
        $v[title] = $ad_position[$v[pid]][position_name]."===".$v[ad_name];
        $v[target] = 0;
    }
    ?>
                    <a href="<?php echo $v['ad_link']; ?>">
                        <div class="td">
                            <img src="<?php echo $v[ad_code]; ?>" title="<?php echo $v[title]; ?>" style="<?php echo $v[style]; ?>">
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="le re fr">
                <?php $pid =300;$ad_position = M("ad_position")->cache(true,TPSHOP_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("pid=$pid  and enabled = 1 and start_time < 1610683200 and end_time > 1610683200 ")->order("orderby desc")->cache(true,TPSHOP_CACHE_TIME)->limit("1")->select();
if(is_array($ad_position) && !in_array($pid,array_keys($ad_position)) && $pid)
{
  M("ad_position")->insert(array(
         "position_id"=>$pid,
         "position_name"=>CONTROLLER_NAME."页面自动增加广告位 $pid ",
         "is_open"=>1,
         "position_desc"=>CONTROLLER_NAME."页面",
  ));
  delFile(RUNTIME_PATH); // 删除缓存
  \think\Cache::clear();  
}


$c = 1- count($result); //  如果要求数量 和实际数量不一样 并且编辑模式
if($c > 0 && I("get.edit_ad"))
{
    for($i = 0; $i < $c; $i++) // 还没有添加广告的时候
    {
      $result[] = array(
          "ad_code" => "/public/images/not_adv.jpg",
          "ad_link" => "/index.php?m=Admin&c=Ad&a=ad&pid=$pid",
          "title"   =>"暂无广告图片",
          "not_adv" => 1,
          "target" => 0,
      );  
    }
}
foreach($result as $key=>$v):       
    
    $v[position] = $ad_position[$v[pid]]; 
    if(I("get.edit_ad") && $v[not_adv] == 0 )
    {
        $v[style] = "filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5"; // 广告半透明的样式
        $v[ad_link] = "/index.php?m=Admin&c=Ad&a=ad&act=edit&ad_id=$v[ad_id]";        
        $v[title] = $ad_position[$v[pid]][position_name]."===".$v[ad_name];
        $v[target] = 0;
    }
    ?>
                    <a href="<?php echo $v['ad_link']; ?>">
                        <div class="td">
                            <img src="<?php echo $v[ad_code]; ?>" title="<?php echo $v[title]; ?>" style="<?php echo $v[style]; ?>">
                        </div>
                    </a>
                <?php endforeach; ?>

            </div>
        </div>
    </div>
    <!--广告位-end-->

    <!--新品上市-start-->
    <div class="floor newshop">
        <div class="banner banner_imgs">
            <img src="/template/mobile/default/static/images/tp-zuinews.png" alt="新品上市"/>
        </div>
        <div class="advertisement">
            <div class="content">
                <div class="le re fl">
                    <?php $pid =303;$ad_position = M("ad_position")->cache(true,TPSHOP_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("pid=$pid  and enabled = 1 and start_time < 1610683200 and end_time > 1610683200 ")->order("orderby desc")->cache(true,TPSHOP_CACHE_TIME)->limit("1")->select();
if(is_array($ad_position) && !in_array($pid,array_keys($ad_position)) && $pid)
{
  M("ad_position")->insert(array(
         "position_id"=>$pid,
         "position_name"=>CONTROLLER_NAME."页面自动增加广告位 $pid ",
         "is_open"=>1,
         "position_desc"=>CONTROLLER_NAME."页面",
  ));
  delFile(RUNTIME_PATH); // 删除缓存
  \think\Cache::clear();  
}


$c = 1- count($result); //  如果要求数量 和实际数量不一样 并且编辑模式
if($c > 0 && I("get.edit_ad"))
{
    for($i = 0; $i < $c; $i++) // 还没有添加广告的时候
    {
      $result[] = array(
          "ad_code" => "/public/images/not_adv.jpg",
          "ad_link" => "/index.php?m=Admin&c=Ad&a=ad&pid=$pid",
          "title"   =>"暂无广告图片",
          "not_adv" => 1,
          "target" => 0,
      );  
    }
}
foreach($result as $key=>$v):       
    
    $v[position] = $ad_position[$v[pid]]; 
    if(I("get.edit_ad") && $v[not_adv] == 0 )
    {
        $v[style] = "filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5"; // 广告半透明的样式
        $v[ad_link] = "/index.php?m=Admin&c=Ad&a=ad&act=edit&ad_id=$v[ad_id]";        
        $v[title] = $ad_position[$v[pid]][position_name]."===".$v[ad_name];
        $v[target] = 0;
    }
    ?>
                        <a href="<?php echo $v[ad_link]; ?>" >
                            <div class="td">
                                <img src="<?php echo $v[ad_code]; ?>" title="<?php echo $v[title]; ?>" style="<?php echo $v[style]; ?>"/>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="le lefhe fr">
                    <?php $pid =304;$ad_position = M("ad_position")->cache(true,TPSHOP_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("pid=$pid  and enabled = 1 and start_time < 1610683200 and end_time > 1610683200 ")->order("orderby desc")->cache(true,TPSHOP_CACHE_TIME)->limit("2")->select();
if(is_array($ad_position) && !in_array($pid,array_keys($ad_position)) && $pid)
{
  M("ad_position")->insert(array(
         "position_id"=>$pid,
         "position_name"=>CONTROLLER_NAME."页面自动增加广告位 $pid ",
         "is_open"=>1,
         "position_desc"=>CONTROLLER_NAME."页面",
  ));
  delFile(RUNTIME_PATH); // 删除缓存
  \think\Cache::clear();  
}


$c = 2- count($result); //  如果要求数量 和实际数量不一样 并且编辑模式
if($c > 0 && I("get.edit_ad"))
{
    for($i = 0; $i < $c; $i++) // 还没有添加广告的时候
    {
      $result[] = array(
          "ad_code" => "/public/images/not_adv.jpg",
          "ad_link" => "/index.php?m=Admin&c=Ad&a=ad&pid=$pid",
          "title"   =>"暂无广告图片",
          "not_adv" => 1,
          "target" => 0,
      );  
    }
}
foreach($result as $key=>$v):       
    
    $v[position] = $ad_position[$v[pid]]; 
    if(I("get.edit_ad") && $v[not_adv] == 0 )
    {
        $v[style] = "filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5"; // 广告半透明的样式
        $v[ad_link] = "/index.php?m=Admin&c=Ad&a=ad&act=edit&ad_id=$v[ad_id]";        
        $v[title] = $ad_position[$v[pid]][position_name]."===".$v[ad_name];
        $v[target] = 0;
    }
    ?>
                        <a href="<?php echo $v[ad_link]; ?>" >
                            <div class="td">
                                <img src="<?php echo $v[ad_code]; ?>" title="<?php echo $v[title]; ?>" style="<?php echo $v[style]; ?>"/>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <!--新品上市-end-->

    <!--热销商品-start-->
    <div class="floor hotshop index_hot">
        <div class="banner banner_imgs">
            <img src="/template/mobile/default/static/images/tp-huosp.png" alt="热销商品"/>
        </div>
                <div class="hotsome">
                    <div class="bloc hottop">
                        <?php $pid =305;$ad_position = M("ad_position")->cache(true,TPSHOP_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("pid=$pid  and enabled = 1 and start_time < 1610683200 and end_time > 1610683200 ")->order("orderby desc")->cache(true,TPSHOP_CACHE_TIME)->limit("1")->select();
if(is_array($ad_position) && !in_array($pid,array_keys($ad_position)) && $pid)
{
  M("ad_position")->insert(array(
         "position_id"=>$pid,
         "position_name"=>CONTROLLER_NAME."页面自动增加广告位 $pid ",
         "is_open"=>1,
         "position_desc"=>CONTROLLER_NAME."页面",
  ));
  delFile(RUNTIME_PATH); // 删除缓存
  \think\Cache::clear();  
}


$c = 1- count($result); //  如果要求数量 和实际数量不一样 并且编辑模式
if($c > 0 && I("get.edit_ad"))
{
    for($i = 0; $i < $c; $i++) // 还没有添加广告的时候
    {
      $result[] = array(
          "ad_code" => "/public/images/not_adv.jpg",
          "ad_link" => "/index.php?m=Admin&c=Ad&a=ad&pid=$pid",
          "title"   =>"暂无广告图片",
          "not_adv" => 1,
          "target" => 0,
      );  
    }
}
foreach($result as $key=>$v):       
    
    $v[position] = $ad_position[$v[pid]]; 
    if(I("get.edit_ad") && $v[not_adv] == 0 )
    {
        $v[style] = "filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5"; // 广告半透明的样式
        $v[ad_link] = "/index.php?m=Admin&c=Ad&a=ad&act=edit&ad_id=$v[ad_id]";        
        $v[title] = $ad_position[$v[pid]][position_name]."===".$v[ad_name];
        $v[target] = 0;
    }
    ?>
                            <div class="le fl">
                                <a href="<?php echo $v[ad_link]; ?>" >
                                    <div class="td">
                                        <img src="<?php echo $v[ad_code]; ?>" title="<?php echo $v[title]; ?>" style="<?php echo $v[style]; ?>"/>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; $pid =306;$ad_position = M("ad_position")->cache(true,TPSHOP_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("pid=$pid  and enabled = 1 and start_time < 1610683200 and end_time > 1610683200 ")->order("orderby desc")->cache(true,TPSHOP_CACHE_TIME)->limit("1")->select();
if(is_array($ad_position) && !in_array($pid,array_keys($ad_position)) && $pid)
{
  M("ad_position")->insert(array(
         "position_id"=>$pid,
         "position_name"=>CONTROLLER_NAME."页面自动增加广告位 $pid ",
         "is_open"=>1,
         "position_desc"=>CONTROLLER_NAME."页面",
  ));
  delFile(RUNTIME_PATH); // 删除缓存
  \think\Cache::clear();  
}


$c = 1- count($result); //  如果要求数量 和实际数量不一样 并且编辑模式
if($c > 0 && I("get.edit_ad"))
{
    for($i = 0; $i < $c; $i++) // 还没有添加广告的时候
    {
      $result[] = array(
          "ad_code" => "/public/images/not_adv.jpg",
          "ad_link" => "/index.php?m=Admin&c=Ad&a=ad&pid=$pid",
          "title"   =>"暂无广告图片",
          "not_adv" => 1,
          "target" => 0,
      );  
    }
}
foreach($result as $key=>$v):       
    
    $v[position] = $ad_position[$v[pid]]; 
    if(I("get.edit_ad") && $v[not_adv] == 0 )
    {
        $v[style] = "filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5"; // 广告半透明的样式
        $v[ad_link] = "/index.php?m=Admin&c=Ad&a=ad&act=edit&ad_id=$v[ad_id]";        
        $v[title] = $ad_position[$v[pid]][position_name]."===".$v[ad_name];
        $v[target] = 0;
    }
    ?>
                            <div class="le fr">
                                <a href="<?php echo $v[ad_link]; ?>" >
                                    <div class="td">
                                        <img src="<?php echo $v[ad_code]; ?>" title="<?php echo $v[title]; ?>" style="<?php echo $v[style]; ?>"/>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="bloc">
                        <div class="le foura">
                            <?php $pid =307;$ad_position = M("ad_position")->cache(true,TPSHOP_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("pid=$pid  and enabled = 1 and start_time < 1610683200 and end_time > 1610683200 ")->order("orderby desc")->cache(true,TPSHOP_CACHE_TIME)->limit("4")->select();
if(is_array($ad_position) && !in_array($pid,array_keys($ad_position)) && $pid)
{
  M("ad_position")->insert(array(
         "position_id"=>$pid,
         "position_name"=>CONTROLLER_NAME."页面自动增加广告位 $pid ",
         "is_open"=>1,
         "position_desc"=>CONTROLLER_NAME."页面",
  ));
  delFile(RUNTIME_PATH); // 删除缓存
  \think\Cache::clear();  
}


$c = 4- count($result); //  如果要求数量 和实际数量不一样 并且编辑模式
if($c > 0 && I("get.edit_ad"))
{
    for($i = 0; $i < $c; $i++) // 还没有添加广告的时候
    {
      $result[] = array(
          "ad_code" => "/public/images/not_adv.jpg",
          "ad_link" => "/index.php?m=Admin&c=Ad&a=ad&pid=$pid",
          "title"   =>"暂无广告图片",
          "not_adv" => 1,
          "target" => 0,
      );  
    }
}
foreach($result as $key=>$v):       
    
    $v[position] = $ad_position[$v[pid]]; 
    if(I("get.edit_ad") && $v[not_adv] == 0 )
    {
        $v[style] = "filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5"; // 广告半透明的样式
        $v[ad_link] = "/index.php?m=Admin&c=Ad&a=ad&act=edit&ad_id=$v[ad_id]";        
        $v[title] = $ad_position[$v[pid]][position_name]."===".$v[ad_name];
        $v[target] = 0;
    }
    ?>
                                <a href="<?php echo $v[ad_link]; ?>" >
                                    <img src="<?php echo $v[ad_code]; ?>" title="<?php echo $v[title]; ?>" style="<?php echo $v[style]; ?>"/>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
    </div>
    <!--热销商品-end-->

    <!--猜您喜欢-start-->
    <div class="floor guesslike">
        <div class="banner banner_imgs">
            <img src="/template/mobile/default/static/images/tp-cainixih.png" alt="猜您喜欢"/>
        </div>
        <div class="likeshop">
            <div id="J_ItemList">
                <ul class="product single_item info">
                </ul>
                <a href="javascript:;" class="get_more" style="text-align:center; display:block;">
                    <img src='/template/mobile/default/static/images/category/loader.gif' width="12" height="12">
                </a>
            </div>
        </div>
        <!--<div class="add" onClick="getGoodsList()">点击继续加载</div>-->
        <div class="loadbefore">
            <img class="ajaxloading" src="/template/mobile/default/static/images/category/loader.gif" alt="loading...">
        </div>
        <div id="getmore"  style="font-size:.32rem;text-align: center;color:#888;padding:.25rem .24rem .4rem; clear:both;display: none"><a >已显示完所有记录</a></div>
    </div>
    <!--猜您喜欢-end-->

    <!--底部-start-->
    <footer>
    <div class="flool1">
        <ul>
            <?php if(!empty($_COOKIE['user_id'])): ?>
                <li><a href="<?php echo U('Mobile/User/index'); ?>"><?php echo getSubstr(urldecode($_COOKIE['uname']),0,10);?></a></li>
                <li id="logout"><a href="<?php echo U('Mobile/User/logout'); ?>">退出</a></li>
            <?php else: ?>
                <li><a href="<?php echo U('Mobile/User/login'); ?>">登录</a></li>
                <li><a href="<?php echo U('Mobile/User/reg'); ?>">注册</a></li>
            <?php endif; ?>
            <li><a href="tel:<?php echo $tpshop_config['shop_info_phone']; ?>">反馈</a></li>
            <li class="comebackTop">回到顶部</li>
        </ul>
    </div>
    <!--<div class="flool2">-->
        <!--<ul>-->
            <!--<li>-->
                <!--<a href="?">-->
                    <!--<div class="icon">-->
                        <!--<img src="/template/mobile/default/static/images/ind_70.png"/>-->
                        <!--<p>客户端</p>-->
                    <!--</div>-->
                <!--</a>-->
            <!--</li>-->
            <!--<li>-->
                <!--<a href="<?php echo U('Mobile/Index/index'); ?>">-->
                    <!--<div class="icon black">-->
                        <!--<img src="/template/mobile/default/static/images/ind_72.png"/>-->
                        <!--<p>触屏版</p>-->
                    <!--</div>-->
                <!--</a>-->
            <!--</li>-->
            <!--<li>-->
                <!--<a href="<?php echo U('Home/Index/index'); ?>">-->
                    <!--<div class="icon">-->
                        <!--<img src="/template/mobile/default/static/images/ind_74.png"/>-->
                        <!--<p>电脑版</p>-->
                    <!--</div>-->
                <!--</a>-->
            <!--</li>-->
        <!--</ul>-->
    <!--</div>-->
    <div class="flool3">
        <p>Copyright © 2016-2025 <?php echo (isset($tpshop_config['shop_info_store_name']) && ($tpshop_config['shop_info_store_name'] !== '')?$tpshop_config['shop_info_store_name']:'www.phpyuanmashe'); ?>版权所有  备案号:<?php echo $tpshop_config['shop_info_record_no']; ?></p>
    </div>
    <a href="javascript:void (0);" onclick="$('html,body').animate({'scrollTop':0},600);" style="display: block;width: 40px;height: 40px;position: fixed; bottom: 70px;right: 8px; background-color: rgba(243,241,241,0.5);border: 1px solid #CCC;-webkit-border-radius: 50%;-moz-border-radius: 50%;border-radius: 50%;">
    	<img src="/template/mobile/default/static/images/topup.png" style="display: block;width: 40px;height: 40px;">
    </a>
</footer>
<script type="text/javascript">
$(function(){
    var ua = window.navigator.userAgent.toLowerCase();
    if(ua.match(/MicroMessenger/i) == 'micromessenger'){
        $('#logout').remove();
    }else{
        return false;
    }
})
</script>
    <!--底部-end-->

    <!--底部导航-start-->
    <div class="foohi">
    <div class="footer">
        <ul>
            <li>
                <a <?php if(CONTROLLER_NAME == 'Index'): ?>class="yello" <?php endif; ?>  href="<?php echo U('Index/index'); ?>">
                    <div class="icon">
                        <div class="icon_tp1 icon_tps"><img src="/template/mobile/default/static/images/home1.png"/> </div>
                        <div class="icon_tp2 icon_tps"><img src="/template/mobile/default/static/images/home2.png"/> </div>
                        <p>首页</p>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?php echo U('Goods/categoryList'); ?>">
                    <div class="icon">
                    	<div class="icon_tp1 icon_tps"><img src="/template/mobile/default/static/images/category1.png"/> </div>
                        <div class="icon_tp2 icon_tps"><img src="/template/mobile/default/static/images/category2.png"/> </div>
                        <p>分类</p>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?php echo U('Cart/index'); ?>">
                    <div class="icon">
                    	<div class="icon_tp1 icon_tps"><img src="/template/mobile/default/static/images/cart1.png"/> </div>
                        <div class="icon_tp2 icon_tps"><img src="/template/mobile/default/static/images/cart2.png"/> </div>
                        <p>购物车</p>
                    </div>
                </a>
            </li>
            <li>
                <a <?php if(CONTROLLER_NAME == 'User'): ?>class="yello" <?php endif; ?> href="<?php echo U('User/index'); ?>">
                    <div class="icon">
                     	<div class="icon_tp1 icon_tps"><img src="/template/mobile/default/static/images/user1.png"/> </div>
                        <div class="icon_tp2 icon_tps"><img src="/template/mobile/default/static/images/user2.png"/> </div>
                        <p>我的</p>
                    </div>
                </a>
            </li>
        </ul>
    </div>
</div>
<script src="/public/js/jqueryUrlGet.js"></script><!--获取get参数插件-->
<script type="text/javascript">
$(document).ready(function(){
	  var cart_cn = getCookie('cn');
	  if(cart_cn == ''){
		$.ajax({
			type : "GET",
			url:"/index.php?m=Home&c=Cart&a=header_cart_list",//+tab,
			success: function(data){								 
				cart_cn = getCookie('cn');
				$('#cart_quantity').html(cart_cn);						
			}
		});	
	  }
	  $('#cart_quantity').html(cart_cn);
});

set_first_leader();//设置推荐人
//切换图标
if($(".footer ul li a").hasClass("yello")){
	$(".footer ul li .yello").find(".icon_tp2").show();
	$(".footer ul li .yello").find(".icon_tp1").hide();
}
</script>
<!-- 微信浏览器 调用微信 分享js-->
<script type="text/javascript" src="http://res.wx.qq.com/open/js/jweixin-1.2.0.js"></script>
<script type="text/javascript">
var httpPrefix = "http://<?php echo \think\Request::instance()->server('SERVER_NAME'); ?>";
<?php if(ACTION_NAME == 'goodsInfo'): ?>
   var ShareLink = httpPrefix+"/index.php?m=Mobile&c=Goods&a=goodsInfo&id=<?php echo $goods[goods_id]; ?>"; //默认分享链接
   var ShareImgUrl = httpPrefix+"<?php echo goods_thum_images($goods[goods_id],400,400); ?>"; // 分享图标
   var ShareTitle = "<?php echo (isset($goods['goods_name']) && ($goods['goods_name'] !== '')?$goods['goods_name']:$tpshop_config['shop_info_store_title']); ?>"; // 分享标题
   var ShareDesc = "<?php echo getSubstr($goods['goods_remark'],0,30); ?>"; // 分享描述
<?php elseif(ACTION_NAME == 'info'): ?>
	var ShareLink = "<?php echo $team['bd_url']; ?>"; //默认分享链接
	var ShareImgUrl = "<?php echo $team['bd_pic']; ?>"; //分享图标
	var ShareTitle = "<?php echo $team[share_title]; ?>"; //分享标题
	var ShareDesc = "<?php echo $team[share_desc]; ?>"; //分享描述
<?php elseif(ACTION_NAME == 'my_store'): ?>
	var ShareLink = httpPrefix+"/index.php?m=Mobile&c=Distribut&a=my_store"; 
	var ShareImgUrl = httpPrefix+"<?php echo $tpshop_config['shop_info_store_logo']; ?>"; 
	var ShareTitle = "<?php echo $share_title; ?>"; 
	var ShareDesc = httpPrefix+"/index.php?m=Mobile&c=Distribut&a=my_store}"; 
<?php elseif(ACTION_NAME == 'found'): ?>
	var ShareLink = httpPrefix+"/index.php?m=Mobile&c=Team&a=found&id=<?php echo $teamFound[found_id]; ?>"; //默认分享链接
	var ShareImgUrl = "<?php echo $team[bd_pic]; ?>"; //分享图标
	var ShareTitle = "<?php echo $team[share_title]; ?>"; //分享标题
	var ShareDesc = "<?php echo $team[share_desc]; ?>"; //分享描述
<?php else: ?>
   var ShareLink = httpPrefix+"/index.php?m=Mobile&c=Index&a=index"; //默认分享链接
   var ShareImgUrl = httpPrefix+"<?php echo $tpshop_config['shop_info_wap_home_logo']; ?>"; //分享图标
   var ShareTitle = "<?php echo $tpshop_config['shop_info_store_title']; ?>"; //分享标题
   var ShareDesc = "<?php echo $tpshop_config['shop_info_store_desc']; ?>"; //分享描述
<?php endif; ?>

var is_distribut = getCookie('is_distribut'); // 是否分销代理
var user_id = getCookie('user_id'); // 当前用户id
var subscribe = getCookie('subscribe'); // 当前用户是否关注了公众号
 
// 如果已经登录了, 并且是分销商
if(parseInt(is_distribut) == 1 && parseInt(user_id) > 0)
{									
	ShareLink = ShareLink + "&first_leader="+user_id;									
}

$(function() {
	if(isWeiXin() && parseInt(user_id)>0){
		$.ajax({
			type : "POST",
			url:"/index.php?m=Mobile&c=Index&a=ajaxGetWxConfig&t="+Math.random(),
			data:{'askUrl':encodeURIComponent(location.href.split('#')[0])},		
			dataType:'JSON',
			success: function(res)
			{
				//微信配置
				wx.config({
				    debug: true, 
				    appId: res.appId,
				    timestamp: res.timestamp, 
				    nonceStr: res.nonceStr, 
				    signature: res.signature,
				    jsApiList: ['onMenuShareTimeline', 'onMenuShareAppMessage','onMenuShareQQ','onMenuShareQZone','hideOptionMenu'] // 功能列表，我们要使用JS-SDK的什么功能
				});
			},
			error:function(res){
				console.log("wx.config error:");
				console.log(res);
				return false;
			}
		}); 

		// config信息验证后会执行ready方法，所有接口调用都必须在config接口获得结果之后，config是一个客户端的异步操作，所以如果需要在 页面加载时就调用相关接口，则须把相关接口放在ready函数中调用来确保正确执行。对于用户触发时才调用的接口，则可以直接调用，不需要放在ready 函数中。
		wx.ready(function(){
		    // 获取"分享到朋友圈"按钮点击状态及自定义分享内容接口
		    wx.onMenuShareTimeline({
		        title: ShareTitle, // 分享标题
		        link:ShareLink,
		        desc: ShareDesc,
		        imgUrl:ShareImgUrl // 分享图标
		    });

		    // 获取"分享给朋友"按钮点击状态及自定义分享内容接口
		    wx.onMenuShareAppMessage({
		        title: ShareTitle, // 分享标题
		        desc: ShareDesc, // 分享描述
		        link:ShareLink,
		        imgUrl:ShareImgUrl // 分享图标
		    });
			// 分享到QQ
			wx.onMenuShareQQ({
		        title: ShareTitle, // 分享标题
		        desc: ShareDesc, // 分享描述
		        link:ShareLink,
		        imgUrl:ShareImgUrl // 分享图标
			});	
			// 分享到QQ空间
			wx.onMenuShareQZone({
		        title: ShareTitle, // 分享标题
		        desc: ShareDesc, // 分享描述
		        link:ShareLink,
		        imgUrl:ShareImgUrl // 分享图标
			});

		   <?php if(CONTROLLER_NAME == 'User'): ?> 
				wx.hideOptionMenu();  // 用户中心 隐藏微信菜单
		   <?php endif; ?>	
		});
	}
	
	if(!isWeiXin() || subscribe == 1){
		$('.guide').hide(); // 非微信浏览 不提示关注公众号		
	}

});

function isWeiXin(){
    var ua = window.navigator.userAgent.toLowerCase();
    if(ua.match(/MicroMessenger/i) == 'micromessenger'){
        return true;
    }else{
        return false;
    }
}
</script>
<!--微信关注提醒 start-->
<?php if(\think\Session::get('subscribe') == 0): ?>
<button class="guide" onclick="follow_wx()">关注公众号</button>
<style type="text/css">
.guide{width:0.627rem;height:2.83rem;text-align: center;border-radius: 8px ;font-size:0.512rem;padding:8px 0;border:1px solid #adadab;color:#000000;background-color: #fff;position: fixed;right: 6px;bottom: 200px;z-index: 99;}
#cover{display:none;position:absolute;left:0;top:0;z-index:18888;background-color:#000000;opacity:0.7;}
#guide{display:none;position:absolute;top:5px;z-index:19999;}
#guide img{width: 70%;height: auto;display: block;margin: 0 auto;margin-top: 10px;}
div.layui-m-layerchild h3{font-size:0.64rem;height:1.24rem;line-height:1.24rem;}
.layui-m-layercont img{height:8.96rem;width:8.96rem;}
</style>
<script type="text/javascript">
  //关注微信公众号二维码	 
function follow_wx()
{
	layer.open({
		type : 1,  
		title: '关注公众号',
		content: '<img src="<?php echo $wechat_config['qr']; ?>">',
		style: ''
	});
}
</script> 
<?php endif; ?>
<!--微信关注提醒  end-->
<!-- 微信浏览器 调用微信 分享js  end-->
    <!--底部导航-end-->

<script type="text/javascript" src="/template/mobile/default/static/js/sourch_submit.js"></script>
<script src="/template/mobile/default/static/js/style.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript">
    /**
     * 秒杀模块倒计时
     * */
    function GetRTime(end_time){
        var NowTime = new Date();
        var t = (end_time*1000) - NowTime.getTime();
        var d=Math.floor(t/1000/60/60/24);
        var h=Math.floor(t/1000/60/60%24);
        var m=Math.floor(t/1000/60%60);
        var s=Math.floor(t/1000%60);
        if(s >= 0)
            return (d * 24 + h) + '时' + m + '分' +s+'秒';
    }

    function GetRTime2(){
        var text = GetRTime('<?php echo $end_time; ?>');
        if (text== 0){
            $(".hms").text('活动已结束');
        }else{
            $(".hms").text(text);
        }
    }
    setInterval(GetRTime2,1000);

    /**
     * 继续加载猜您喜欢
     * */

    var before_request = 1; // 上一次请求是否已经有返回来, 有才可以进行下一次请求
     var page = 0;
     function ajax_sourch_submit(){
         if(before_request == 0)// 上一次请求没回来 不进行下一次请求
             return false;
         before_request = 0;
         ++page;
         $('.get_more').show();
         $.ajax({
             type : "get",
             url:"/index.php?m=Mobile&c=Index&a=ajaxGetMore&p="+page,
             success: function(data)
             {
                 if(data){
                     $("#J_ItemList>ul").append(data);
                     $('.get_more').hide();
                     before_request = 1;
                 }else{
                     $('.get_more').hide();
                 }
             }
         });
     }
    //轮播
    $(function(){
        $('#slideTpshop').swipeSlide({
            continuousScroll:true,
            speed : 3000,
            transitionType : 'cubic-bezier(0.22, 0.69, 0.72, 0.88)',
            firstCallback : function(i,sum,me){
                me.find('.dot').children().first().addClass('cur');
            },
            callback : function(i,sum,me){
                me.find('.dot').children().eq(i).addClass('cur').siblings().removeClass('cur');
            }
        });
        //圆点
        var ed = $('.mslide ul li').length - 2;
        $('.mslide').append("<div class=" + "dot" + "></div>");
        for(var i = 0; i<ed ;i++){
            $('.mslide .dot').append("<span></span>");
        };
        $('.mslide .dot span:first').addClass('cur');
        var wid = - ($('.mslide .dot').width() / 2);
        $('.mslide .dot').css('position','absolute').css('left','50%').css('margin-left',wid);
    });
</script>
	</body>
</html>
