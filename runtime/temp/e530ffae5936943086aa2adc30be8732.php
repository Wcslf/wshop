<?php if (!defined('THINK_PATH')) exit(); /*a:5:{s:49:"./template/mobile/default/goods/categoryList.html";i:1532948114;s:74:"/www/wwwroot/shop.zequninfo.com/template/mobile/default/public/header.html";i:1532948114;s:75:"/www/wwwroot/shop.zequninfo.com/template/mobile/default/public/top_nav.html";i:1532948114;s:78:"/www/wwwroot/shop.zequninfo.com/template/mobile/default/public/footer_nav.html";i:1540447902;s:76:"/www/wwwroot/shop.zequninfo.com/template/mobile/default/public/wx_share.html";i:1532948114;}*/ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>所有分类--<?php echo $tpshop_config['shop_info_store_title']; ?></title>
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

    <div class="classreturn">
        <div class="content">
            <div class="ds-in-bl return">
                <a href="javascript:history.back(-1);"><img src="/template/mobile/default/static/images/return.png" alt="返回"></a>
            </div>
            <div class="ds-in-bl search">
                <form action="" method="post">
                    <div class="sear-input">
                        <a href="<?php echo U('Goods/ajaxSearch'); ?>">
                            <input type="text" value="" placeholder="请输入您所搜索的商品">
                        </a>
                    </div>
                </form>
            </div>
            <div class="ds-in-bl menu">
                <a href="javascript:void(0);"><img src="/template/mobile/default/static/images/class1.png" alt="菜单"></a>
            </div>
        </div>
    </div>

    <!--顶部隐藏菜单-s-->
    <div class="flool up-tpnavf-wrap tpnavf [cla]">
    <div class="footer up-tpnavf-head">
    	<div class="up-tpnavf-i"> </div>
        <ul>
            <li>
                <a class="yello" href="<?php echo U('Index/index'); ?>">
                    <div class="icon">
                        <i class="icon-shouye iconfont"></i>
                        <p>首页</p>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?php echo U('Goods/categoryList'); ?>">
                    <div class="icon">
                        <i class="icon-fenlei iconfont"></i>
                        <p>分类</p>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?php echo U('Cart/index'); ?>">
                    <div class="icon">
                        <i class="icon-gouwuche iconfont"></i>
                        <p>购物车</p>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?php echo U('User/index'); ?>">
                    <div class="icon">
                        <i class="icon-wode iconfont"></i>
                        <p>我的</p>
                    </div>
                </a>
            </li>
        </ul>
    </div>
</div>
<script type="text/javascript">
$(function(){	
	$(window).scroll(function() {		
		if($(window).scrollTop() >= 1){ 
			$('.tpnavf').hide()
		}
	})
})
</script>
    <!--顶部隐藏菜单-e-->

    <div class="flool classlist">
        <div class="fl category1">
            <ul>
                <?php $m = '0'; if(is_array($goods_category_tree) || $goods_category_tree instanceof \think\Collection || $goods_category_tree instanceof \think\Paginator): if( count($goods_category_tree)==0 ) : echo "" ;else: foreach($goods_category_tree as $k=>$vo): if($vo[level] == 1): ?>
                        <li >
                           <a href="javascript:void(0);" <?php if($m == 0): endif; ?> data-id="<?php echo $m++; ?>"><?php echo getSubstr($vo['mobile_name'],0,12); ?></a>
                        </li>
                    <?php endif; endforeach; endif; else: echo "" ;endif; ?>
            </ul>
        </div>
        <div class="fr category2">
            <?php $j = '0'; if(is_array($goods_category_tree) || $goods_category_tree instanceof \think\Collection || $goods_category_tree instanceof \think\Paginator): if( count($goods_category_tree)==0 ) : echo "" ;else: foreach($goods_category_tree as $kk=>$vo): ?>
                <div class="branchList" >
                	<div class="branchList-cont">
                    <!--广告图-s-->
                    <div class="tp-bann"  data-id="<?php echo $j++; ?>">
                        <?php $pid =401;$ad_position = M("ad_position")->cache(true,TPSHOP_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("pid=$pid  and enabled = 1 and start_time < 1610683200 and end_time > 1610683200 ")->order("orderby desc")->cache(true,TPSHOP_CACHE_TIME)->limit("1")->select();
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
                            <a href="<?php echo $v['ad_link']; ?>" <?php if($v['target'] == 1): ?>target="_blank"<?php endif; ?> >
                                <img src="<?php echo $v[ad_code]; ?>" title="<?php echo $v[title]; ?>" style="<?php echo $v[style]; ?>">
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <!--广告图-e-->
                    <!--分类-s-->
                    <div class="tp-class-list">
                        <?php if(is_array($vo['tmenu']) || $vo['tmenu'] instanceof \think\Collection || $vo['tmenu'] instanceof \think\Paginator): if( count($vo['tmenu'])==0 ) : echo "" ;else: foreach($vo['tmenu'] as $k2=>$v2): ?>
                                <h4><a href="<?php echo U('Mobile/Goods/goodsList',array('id'=>$v2[id])); ?>" ><?php echo $v2['name']; ?></a></h4>
                                <ul class="tp-category">
                                    <?php if(is_array($v2['sub_menu']) || $v2['sub_menu'] instanceof \think\Collection || $v2['sub_menu'] instanceof \think\Paginator): if( count($v2['sub_menu'])==0 ) : echo "" ;else: foreach($v2['sub_menu'] as $key=>$v3): ?>
                                            <li>
                                                <a href="<?php echo U('Mobile/Goods/goodsList',array('id'=>$v3[id])); ?>">
                                                    <img src="<?php echo (isset($v3['image']) && ($v3['image'] !== '')?$v3['image']:'/template/mobile/default/static/images/zy.png'); ?>"/>
                                                    <p><?php echo $v3['name']; ?></p>
                                                </a>
                                            </li>
                                    <?php endforeach; endif; else: echo "" ;endif; ?>
                                </ul>
                        <?php endforeach; endif; else: echo "" ;endif; ?>
                    </div>
                    <!--分类-e-->
                    </div>
                </div>
            <?php endforeach; endif; else: echo "" ;endif; ?>
        </div>
    </div>
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
<script src="/template/mobile/default/static/js/style.js" type="text/javascript" charset="utf-8"></script>
<script>
    $(function () {
        //点击切换2 3级分类
        var array=new Array();
        $('.category1 li').each(function(){
            array.push($(this).position().top-0);
        });
        $('.branchList').eq(0).show().siblings().hide();
        $('.category1 li').click(function() {
            var index = $(this).index() ;
            $('.category1').delay(200).animate({scrollTop:array[index]},300);
            $(this).addClass('cur').siblings().removeClass();
            $('.branchList').eq(index).show().siblings().hide();
            $('.category2').scrollTop(0);
        });
         $("html,body").css("overflow","hidden");
    });
</script>
	</body>
</html>
