<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:41:"./template/mobile/default/user/login.html";i:1540447902;s:74:"/www/wwwroot/shop.zequninfo.com/template/mobile/default/public/header.html";i:1532948114;s:78:"/www/wwwroot/shop.zequninfo.com/template/mobile/default/public/header_nav.html";i:1540447902;}*/ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>登录--<?php echo $tpshop_config['shop_info_store_title']; ?></title>
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
<body class="">

<div class="classreturn">
    <div class="content">
        <div class="ds-in-bl return">
            <a id="[back]" <?php  if(request()->action() == 'userinfo' && $_GET["action"]==""){  ?>  href="<?php echo U('User/index'); ?>" <?php  }else{  ?> href="javascript:history.back(-1);" <?php  }  ?> ><img src="/template/mobile/default/static/images/return.png" alt="返回"></a>
        </div>
        <div class="ds-in-bl search center">
            <span>登录</span>
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
		<div class="flool loginsignup2">
            <!--LOGO-->
			<img src="<?php echo (isset($tpshop_config['shop_info_wap_login_logo']) && ($tpshop_config['shop_info_wap_login_logo'] !== '')?$tpshop_config['shop_info_wap_login_logo']:'/public/static/images/logo/wap_logo_default.png'); ?>" alt="LOGO"/>
		</div>
		<div class="loginsingup-input">
            <!--登录表单-s-->
			<form  onsubmit="return false;"  method="post"  >
				<div class="content30">
					<div class="lsu lsus">
						<p>账号</p>
						<input type="text" name="username" id="username" value=""  placeholder="请输入邮箱/手机号"/>
					</div>
					<div class="lsu lsus">
						<p>密码</p>
						<input type="password" name="password" id="password" value="" placeholder="请输入密码"/>
						<i></i>
					</div>
                    <?php if(!(empty($first_login) || (($first_login instanceof \think\Collection || $first_login instanceof \think\Paginator ) && $first_login->isEmpty()))): ?>
					<div class="lsu test lsus">
						<p>验证码</p>
						<input type="text" name="verify_code" id="verify_code" value="" placeholder="请输入验证码"/>
						<img  id="verify_code_img" src="<?php echo U('Mobile/User/verify'); ?>" onClick="verify()" style="width: 4rem;height:1.5rem;"/>
					</div>
                    <?php endif; ?>
					<div class="lsu submit">
						<input type="button"  value="登录"  onclick="submitverify()" class="btn_big1"  />
                        <input type="hidden" name="referurl" id="referurl" value="<?php echo $referurl; ?>">
					</div>
					<div class="radio">
                        <!--<lable>-->
                         <!--<span class="che check_t" onclick="remember(this)">-->
							<!--<i><input type="checkbox" name="remember" value="1" checked="" style="display: none"> </i>-->
							<!--<span>自动登录</span>-->
						<!--</span>-->
                        <!--</lable>-->
					</div>
					<div class="signup-find p">
						<div class="note fl">
							<img src="/template/mobile/default/static/images/not.png"/>
							<a href="<?php echo U('User/reg'); ?>"><span>快速注册</span></a>
						</div>
						<div class="note fr">
							<img src="/template/mobile/default/static/images/ru.png"/>
							<a href="<?php echo U('User/forget_pwd'); ?>"><span>找回密码</span></a>
						</div>
					</div>
				</div>
			</form>
            <!--登录表单-e-->
		</div>

        <!--第三方登陆-s-->
		<div class="thirdlogin">
			<h4>第三方登陆</h4>
			<ul>
                <?php
                                   
                                $md5_key = md5("select * from __PREFIX__plugin where type='login' AND status = 1");
                                $result_name = $sql_result_v = S("sql_".$md5_key);
                                if(empty($sql_result_v))
                                {                            
                                    $result_name = $sql_result_v = \think\Db::query("select * from __PREFIX__plugin where type='login' AND status = 1"); 
                                    S("sql_".$md5_key,$sql_result_v,31104000);
                                }    
                              foreach($sql_result_v as $k=>$v): ?>
                <!--    <?php if($v['code'] == 'weixin' AND is_weixin() != 1): ?>
                        <li>
                            <a class="ta-weixin" href="<?php echo U('LoginApi/login',array('oauth'=>'weixin')); ?>" target="_blank" title="weixin">
                                <div class="icon">
                                    <img src="/template/mobile/default/static/images/wechat.png"/>
                                    <p>微信登陆</p>
                                </div>
                            </a>
                        </li>
                    <?php endif; ?><?php echo SITE_URL; ?>/<?php echo U('LoginApi/login',array('oauth'=>'alipay')); ?>-->
                    <?php if($v['code'] == 'alipay' AND is_alipay() != 1 AND is_weixin() != 1): ?>
                        <li>
                            <a href="<?php echo U('LoginApi/login',array('oauth'=>'alipay')); ?>">
                                <div class="icon">
                                    <img src="/template/mobile/default/static/images/alpay.png"/>
                                    <p>支付宝</p>
                                </div>
                            </a>
                        </li>
                    <?php endif; if($v['code'] == 'alipaynew' AND is_alipay() != 1 AND is_weixin() != 1): ?>
                        <li>
                            <a href="alipays://platformapi/startapp?appId=20000067&url=<?php echo $alipay_url; ?>">
                                <div class="icon">
                                    <img src="/template/mobile/default/static/images/alpay.png"/>
                                    <p>支付宝</p>
                                </div>
                            </a>
                        </li>
                    <?php endif; if($v['code'] == 'qq' AND is_qq() != 1): ?>
                        <li>
                            <a class="ta-qq" href="<?php echo U('LoginApi/login',array('oauth'=>'qq')); ?>" target="_blank" title="QQ">
                                <div class="icon">
                                    <img src="/template/mobile/default/static/images/qq.png"/>
                                    <p>qq登陆</p>
                                </div>
                            </a>
                        </li>
                    <?php endif; endforeach; ?>
			</ul>
		</div>
        <!--第三方登陆-e-->
<script src="/template/mobile/default/static/js/style.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript">
    function verify(){
        $('#verify_code_img').attr('src','/index.php?m=Mobile&c=User&a=verify&r='+Math.random());
    }

    //复选框状态
    function remember(obj){
         var che= $(obj).attr("class");
        if ( che == 'che'){
            $('#remember').val(1);
        }else if(che == 'che check_t'){
            $('#remember').val(0);
        }
    }
    function submitverify()
    {
        var username = $.trim($('#username').val());
        var password = $.trim($('#password').val());
        var remember = $('#remember').val();
        var referurl = $('#referurl').val();
        var verify_code = $.trim($('#verify_code').val());
        if(username == ''){
            showErrorMsg('用户名不能为空!');
            return false;
        }
        if(!checkMobile(username) && !checkEmail(username)){
            showErrorMsg('账号格式不匹配!');
            return false;
        }
        if(password == ''){
            showErrorMsg('密码不能为空!');
            return false;
        }
        var codeExist = $('#verify_code').length;
        if (codeExist && verify_code == ''){
            showErrorMsg('验证码不能为空!');
            return false;
        }

        var data = {username:username,password:password,referurl:encodeURIComponent(referurl)};
        if (codeExist) {
            data.verify_code = verify_code;
        }
        $.ajax({
            type : 'post',
            url : '/index.php?m=Mobile&c=User&a=do_login&t='+Math.random(),
            data : data,
            dataType : 'json',
            success : function(res){
                if(res.status == 1){
                    var url = decodeURIComponent(data.referurl);
                    if (url.indexOf('user') >= 0 && url.indexOf('login') >= 0 || url == '') {
                        window.location.href = '/index.php/mobile/';
                    }
                    window.location.href = url;
                }else{
                    showErrorMsg(res.msg);
                    if (codeExist) {
                        verify();
                    } else {
                        location.reload();
                    }
                }
            },
            error : function(XMLHttpRequest, textStatus, errorThrown) {
                showErrorMsg('网络失败，请刷新页面后重试');
            }
        })
    }
        //切换密码框的状态
        $(function(){
            $('.loginsingup-input .lsu i').click(function(){
                $(this).toggleClass('eye');
                if ($(this).hasClass('eye')) {
                    $(this).siblings('input').attr('type','text')
                } else{
                    $(this).siblings('input').attr('type','password')
                }
            });
        })
    /**
     * 提示弹窗
     * @param msg
     */
    function showErrorMsg(msg){
        layer.open({content:msg,time:2});
    }
    </script>
</body>
</html>
