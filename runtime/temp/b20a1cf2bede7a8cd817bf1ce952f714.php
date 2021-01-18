<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:43:"./template/mobile/default/index/street.html";i:1532948114;s:74:"/www/wwwroot/shop.zequninfo.com/template/mobile/default/public/header.html";i:1532948114;s:78:"/www/wwwroot/shop.zequninfo.com/template/mobile/default/public/header_nav.html";i:1540447902;}*/ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>店铺街--<?php echo $tpshop_config['shop_info_store_title']; ?></title>
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
            <a id="[back]" <?php  if(request()->action() == 'userinfo' && $_GET["action"]==""){  ?>  href="<?php echo U('User/index'); ?>" <?php  }else{  ?> href="javascript:history.back(-1)" <?php  }  ?> ><img src="/template/mobile/default/static/images/return.png" alt="返回"></a>
        </div>
        <div class="ds-in-bl search center">
            <span>店铺街</span>
        </div>
        <div class="ds-in-bl menu">
            <a href="javascript:void(0);"><img src="/template/mobile/default/static/images/class1.png" alt="菜单"></a>
        </div>
    </div>
</div>
<div class="flool up-tpnavf-wrap tpnavf [top-header]">
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
		<!--banner1-start-->
		<div class="banner">
            <?php $pid =501;$ad_position = M("ad_position")->cache(true,TPSHOP_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("pid=$pid  and enabled = 1 and start_time < 1610686800 and end_time > 1610686800 ")->order("orderby desc")->cache(true,TPSHOP_CACHE_TIME)->limit("1")->select();
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
                <img src="<?php echo $v[ad_code]; ?>" title="<?php echo $v[title]; ?>" style="height: 6.2rem;<?php echo $v[style]; ?>" alt="">
            <?php endforeach; ?>
		</div>
		<!--banner1-end-->
		<nav class="storenav p">
			<ul>
				<li>
                    <a href="javascript:void(0)" onclick="locationaddress(this);">
                        <script type="text/javascript">
                            function locationaddress(e){
                                $('.container').animate({width: '14.4rem', opacity: 'show'}, 'normal',function(){
                                    $('.container').show();
                                });
                                if(!$('.container').is(":hidden")){
                                    $('body').css('overflow','hidden')
                                    cover();
                                    $('.mask-filter-div').css('z-index','9999');
                                }
                            }
                            function closelocation(){
                                var province_div = $('.province-list');
                                var city_div = $('.city-list');
                                var area_div = $('.area-list');
                                if(area_div.is(":hidden") == false){
                                    area_div.hide();
                                    city_div.show();
                                    province_div.hide();
                                    return;
                                }
                                if(city_div.is(":hidden") == false){
                                    area_div.hide();
                                    city_div.hide();
                                    province_div.show();
                                    return;
                                }
                                if(province_div.is(":hidden") == false){
                                    area_div.hide();
                                    city_div.hide();
                                    $('.container').animate({width: '0', opacity: 'show'}, 'normal',function(){
                                        $('.container').hide();
                                    });
                                    undercover();
                                    $('.mask-filter-div').css('z-index','inherit');
                                    return;
                                }
                            }
                        </script><span class="dq" ></span></a>
                        <i></i>
                        <!--地区获取输出-s-->
                        <div class="dqs" style="display:none;">
                            <input id="address" type="text" readonly="" placeholder="城市选择"  value=""/>
                            <input id="province_id" type="hidden" value=""/>
                            <input id="city_id"     type="hidden" value=""/>
                            <input id="district_id" type="hidden" value=""/>
                            <input id="all" type="hidden" value="1"/>
                            <input id="area" type="hidden" value="0"/>
                            <input id="p" type="hidden" value="0"/>
                        </div>
                        <!--地区获取输出-e-->
                    </li>
                <li>
                    <span class="lb">类别</span>
                    <i></i>
                </li>
				<li>
                    <a id="allStreet" onclick="showAllStreet()">
                        <span>全部店铺</span>
                    </a>
				</li>
			</ul>
		</nav>
		<div class="lb_showhide p">
            <i class="closer"></i>
			<ul style="margin-top: 1rem;">
                <li><a href="javascript:setCat_id(0);">全部分类</a></li>
                <?php if(is_array($store_class) || $store_class instanceof \think\Collection || $store_class instanceof \think\Paginator): $i = 0; $__LIST__ = $store_class;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$sc): $mod = ($i % 2 );++$i;?>
                    <li><a href="javascript:setCat_id(<?php echo $sc['sc_id']; ?>);"><?php echo $sc['sc_name']; ?></a></li>
                <?php endforeach; endif; else: echo "" ;endif; ?>
			</ul>
		</div>
		<div class="store_info" id="store_list">
		
		</div>
		<!--选择地区-s-->
        <div class="container" >
            <div class="city">
                <div class="screen_wi_loc">
                    <div class="classreturn loginsignup">
                        <div class="content">
                            <div class="ds-in-bl return seac_retu">
                                <a href="javascript:void(0);" onclick="closelocation();"><img src="/template/mobile/default/static/images/return.png" alt="返回"></a>
                            </div>
                            <div class="ds-in-bl search center">
                                <span class="sx_jsxz">选择地区</span>
                            </div>
                            <div class="ds-in-bl suce_ok">
                                <a href="javascript:void(0);">&nbsp;</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="province-list"></div>
                <div class="city-list" style="display:none"></div>
                <div class="area-list" style="display:none"></div>
            </div>
        </div>
        <!--选择地区-e-->
		<!--底部导航-start-->
		</include file="public/footer_nav"/>
		<!--底部导航-end-->
		<div class="mask-filter-div"></div>
		<script type="text/javascript" src="/template/mobile/default/static/js/mobile-location.js"></script>
		<script type="text/javascript" src="/template/mobile/default/static/js/sourch_submit.js"></script>
		<script src="/template/mobile/default/static/js/style.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript">
        var cat_id = '';//店铺分类id
        var ajax_return_status=1;
        //初始化列表
        function initializeList(){
            $('#p').val('1')
            $("#store_list").html('');
            ajax_return_status = 1;
        }

        //选择地址回调
        function select_area_callback(province_name , city_name , district_name , province_id , city_id , district_id){
            var area = province_name+','+city_name+','+district_name;
            if(area !=''){
                $('.dq').text(area.substring(0,8));
                $("#area").val(area);
                $("input[name=province]").val(province_id);
                $("input[name=city]").val(city_id);
                $("input[name=district]").val(district_id);
            }else{
                $('.dq').text('选择地区');
            }
        }
	    /**
	     * 加载分类店铺
	     */
	    function setCat_id(cid) {
            initializeList()
            var all = $('#all').val(0);
	        undercover();
	        $('.lb_showhide').hide();
	        cat_id = cid;
            ajax_sourch_submit();
	    }
		$(function(){
            select_area_callback(province_name , city_name , district_name);
            $('#allStreet').trigger('click').parent().addClass('red');
            $(document).on("click", '.area-list p', function (e) {
                cat_id = '';
                initializeList()
                ajax_sourch_submit();
            });
            $(document).on("click", '.closer', function (e) {
                undercover();
                $('.lb_showhide').hide();
            });

			$('.storenav ul li').click(function(){
                ajax_return_status = 1;
				$(this).addClass('red').siblings('li').removeClass('red')
			});
			
			$('.storenav ul li .lb').click(function(){
				$('.lb_showhide').show();
				cover();
			});
			
			$('.storenav ul li .dq').click(function(){
				$(this).siblings('.dqs').find('#dq').click();
			});
		});
        /**
         * 加载店铺
         */
        function ajax_sourch_submit() {
            if (ajax_return_status == 0){
                return false;
            }
            ajax_return_status = 0;
            var province_id = $('#province_id').val();
            var city_id = $('#city_id').val();
            var district_id =$('#district_id').val();
            var all = $('#all').val();
            var page = parseInt($('#p').val());
            $.ajax({
                type: "post",
                url: "/index.php?m=Mobile&c=Index&a=ajaxStreetList&",
                dataType: 'html',
                data:{'p':page,'sc_id':cat_id,'province_id':province_id,'city_id':city_id,'district_id':district_id,'all':all},
                success: function (data) {
                    if (data) {
                        ajax_return_status = 1;
                        $('#p').val(page+1);
                        $("#store_list").append(data);
                        $('.get_more').hide();
                    } else {
                        $('#getmore').show();
                        return false;
                    }
                }
            });
        }

        function showAllStreet() {
            $('.dq').text('选择地区')
            $('#all').val(1)
            $("#store_list").html('');
            $('#province_id').attr('value',0);
            $('#city_id').attr('value',0);
            $('#district_id').attr('value',0);
            cat_id = 0;
            initializeList()
            ajax_sourch_submit()
        }
        //收藏店铺
        function favoriteStore(id) {
            if(getCookie('user_id')<=0 || getCookie('user_id')==''){
                layer.open({content:'请先登录',time:1});
                return false;
            }
            var old_num = parseInt($('.store_collect_'+id).text());
            $.ajax({
                type: 'post',
                dataType: 'json',
                data: {store_id: id},
                url: "/index.php/Home/Store/collect_store",
                success: function (data) {
                    if (data.status == 1) {
                        layer.open({content:data.msg,time:1});
                        $('.store_collect_'+id).text(old_num+1);
                        $('#store_'+id).html('<a href="javascript:void(0)" class="collect">已关注</a>')
                    } else {
                        layer.open({content:data.msg,time:1});
                    }
                }
            });
        }
</script>
</body>
</html>