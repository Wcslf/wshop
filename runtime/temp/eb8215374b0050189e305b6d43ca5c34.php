<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:52:"./application/admin/view/store/store_class_info.html";i:1532948112;s:73:"/www/wwwroot/shop.zequninfo.com/application/admin/view/public/layout.html";i:1559450922;}*/ ?>
<!doctype html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<!-- Apple devices fullscreen -->
<meta name="apple-mobile-web-app-capable" content="yes">
<!-- Apple devices fullscreen -->
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link href="/public/static/css/main.css" rel="stylesheet" type="text/css">
<link href="/public/static/css/page.css" rel="stylesheet" type="text/css">
<link href="/public/static/font/css/font-awesome.min.css" rel="stylesheet" />
<!--[if IE 7]>
  <link rel="stylesheet" href="/public/static/font/css/font-awesome-ie7.min.css">
<![endif]-->
<link href="/public/static/js/jquery-ui/jquery-ui.min.css" rel="stylesheet" type="text/css"/>
<link href="/public/static/js/perfect-scrollbar.min.css" rel="stylesheet" type="text/css"/>
<style type="text/css">html, body { overflow: visible;}</style>
<script type="text/javascript" src="/public/static/js/jquery.js"></script>
<script type="text/javascript" src="/public/static/js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="/public/static/js/layer/layer.js"></script><!-- 弹窗js 参考文档 http://layer.layui.com/-->
<script type="text/javascript" src="/public/static/js/admin.js"></script>
<script type="text/javascript" src="/public/static/js/jquery.validation.min.js"></script>
<script type="text/javascript" src="/public/static/js/perfect-scrollbar.min.js"></script>
<script type="text/javascript" src="/public/static/js/jquery.mousewheel.js"></script>
<script src="/public/js/myFormValidate.js"></script>
<script src="/public/js/myAjax2.js"></script>
<script src="/public/js/global.js"></script>
<script type="text/javascript">
function delfunc(obj){
	layer.confirm('确认删除？', {
		  btn: ['确定','取消'] //按钮
		}, function(){
			$.ajax({
				type : 'post',
				url : $(obj).attr('data-url'),
				data : {act:'del',del_id:$(obj).attr('data-id')},
				dataType : 'json',
				success : function(data){
					layer.closeAll();
					if(data.status==1){
                        $(obj).parent().parent().parent().html('');
						layer.msg('操作成功', {icon: 1});
					}else{
						layer.msg('删除失败', {icon: 2,time: 2000});
					}
				}
			})
		}, function(index){
			layer.close(index);
		}
	);
}

function delAll(obj,name){
	var a = [];
	$('input[name*='+name+']').each(function(i,o){
		if($(o).is(':checked')){
			a.push($(o).val());
		}
	})
	if(a.length == 0){
		layer.alert('请选择删除项', {icon: 2});
		return;
	}
	layer.confirm('确认删除？', {btn: ['确定','取消'] }, function(){
			$.ajax({
				type : 'get',
				url : $(obj).attr('data-url'),
				data : {act:'del',del_id:a},
				dataType : 'json',
				success : function(data){
					layer.closeAll();
					if(data == 1){
						layer.msg('操作成功', {icon: 1});
						$('input[name*='+name+']').each(function(i,o){
							if($(o).is(':checked')){
								$(o).parent().parent().remove();
							}
						})
					}else{
						layer.msg(data, {icon: 2,time: 2000});
					}
				}
			})
		}, function(index){
			layer.close(index);
			return false;// 取消
		}
	);	
}

//表格列表全选反选
$(document).ready(function(){
	$('.hDivBox .sign').click(function(){
	    var sign = $('#flexigrid > table>tbody>tr');
	   if($(this).parent().hasClass('trSelected')){
	       sign.each(function(){
	           $(this).removeClass('trSelected');
	       });
	       $(this).parent().removeClass('trSelected');
	   }else{
	       sign.each(function(){
	           $(this).addClass('trSelected');
	       });
	       $(this).parent().addClass('trSelected');
	   }
	})
});

//获取选中项
function getSelected(){
	var selectobj = $('.trSelected');
	var selectval = [];
    if(selectobj.length > 0){
        selectobj.each(function(){
        	selectval.push($(this).attr('data-id'));
        });
    }
    return selectval;
}

function selectAll(name,obj){
    $('input[name*='+name+']').prop('checked', $(obj).checked);
}   

function get_help(obj){

	window.open("http://www.phpyuanmashe.com/");
	return false;

    layer.open({
        type: 2,
        title: '帮助手册',
        shadeClose: true,
        shade: 0.3,
        area: ['70%', '80%'],
        content: $(obj).attr('data-url'), 
    });
}

//
///**
// * 全选
// * @param obj
// */
//function checkAllSign(obj){
//    $(obj).toggleClass('trSelected');
//    if($(obj).hasClass('trSelected')){
//        $('#flexigrid > table>tbody >tr').addClass('trSelected');
//    }else{
//        $('#flexigrid > table>tbody >tr').removeClass('trSelected');
//    }
//}
/**
 * 批量公共操作（删，改）
 * @returns {boolean}
 */
function publicHandleAll(type){
    var ids = '';
    $('#flexigrid .trSelected').each(function(i,o){
//            ids.push($(o).data('id'));
        ids += $(o).data('id')+',';
    });
    if(ids == ''){
        layer.msg('至少选择一项', {icon: 2, time: 2000});
        return false;
    }
    publicHandle(ids,type); //调用删除函数
}
/**
 * 公共操作（删，改）
 * @param type
 * @returns {boolean}
 */
function publicHandle(ids,handle_type){
    layer.confirm('确认当前操作？', {
                btn: ['确定', '取消'] //按钮
            }, function () {
                // 确定
                $.ajax({
                    url: $('#flexigrid').data('url'),
                    type:'post',
                    data:{ids:ids,type:handle_type},
                    dataType:'JSON',
                    success: function (data) {
                        layer.closeAll();
                        if (data.status == 1){
                            layer.msg(data.msg, {icon: 1, time: 2000},function(){
                                location.href = data.url;
                            });
                        }else{
                            layer.msg(data.msg, {icon: 2, time: 3000});
                        }
                    }
                });
            }, function (index) {
                layer.close(index);
            }
    );
}
</script>
</head>
<script type="text/javascript" src="/public/static/js/perfect-scrollbar.min.js"></script>
<body style="background-color: #FFF; overflow: auto;">
<div id="toolTipLayer" style="position: absolute; z-index: 9999; display: none; visibility: visible; left: 95px; top: 573px;"></div>
<div id="append_parent"></div>
<div id="ajaxwaitid"></div>
<div class="page">
	<div class="fixed-bar">
		<div class="item-title"><a class="back" href="javascript:history.back();" title="返回列表"><i class="fa fa-arrow-circle-o-left"></i></a>
			<div class="subject">
				<h3>店铺管理 - 编辑店铺<?php echo $store['store_name']; ?>的经营类目</h3>
			</div>
		</div>
	</div>
	<div class="flexigrid" >
		<div class="mDiv">
			<div class="ftitle">
				<h3>经营类目列表</h3>
				<h5>(共<?php echo count($bind_class_list); ?>条记录)</h5>
			</div>
			<a href=""><div title="刷新数据" class="pReload"><i class="fa fa-refresh"></i></div></a>
		</div>
		<div class="hDiv">
			<div class="hDivBox">
				<table cellspacing="0" cellpadding="0">
					<thead>
					<tr>
						<th class="sign" axis="col0">
							<div style="width: 24px;"><i class="ico-check"></i></div>
						</th>
						<th align="left" abbr="article_title" axis="col3" class="">
							<div style="text-align: left; width: 120px;" class="">分类1</div>
						</th>
						<th align="left" abbr="ac_id" axis="col4" class="">
							<div style="text-align: left; width: 120px;" class="">分类2</div>
						</th>
						<th align="left" abbr="article_show" axis="col5" class="">
							<div style="text-align: center; width: 120px;" class="">分类3</div>
						</th>
						<th align="center" abbr="article_time" axis="col6" class="">
							<div style="text-align: center; width: 80px;" class="">分佣比例</div>
						</th>
                        <th align="center" abbr="article_time" axis="col6" class="">
                            <div style="text-align: center; width: 80px;" class="">状态</div>
                        </th>
						<th align="center" axis="col1" class="handle">
							<div style="text-align: center; width: 150px;">操作</div>
						</th>
						<th style="width:100%" axis="col7">
							<div></div>
						</th>
					</tr>
					</thead>
				</table>
			</div>
		</div>
		<div class="bDiv" style="height: auto;min-height: 0px;">
			<div id="flexigrid" cellpadding="0" cellspacing="0" border="0">
				<table>
					<tbody>
					<?php if(is_array($bind_class_list) || $bind_class_list instanceof \think\Collection || $bind_class_list instanceof \think\Paginator): if( count($bind_class_list)==0 ) : echo "" ;else: foreach($bind_class_list as $k=>$vo): ?>
						<tr>
							<td class="sign">
								<div style="width: 24px;"><i class="ico-check"></i></div>
							</td>
							<td align="left" class="">
								<div style="text-align: left; width: 120px;"><?php echo $vo['class_1_name']; ?></div>
							</td>
							<td align="left" class="">
								<div style="text-align: left; width: 120px;"><?php echo $vo['class_2_name']; ?></div>
							</td>
							<td align="left" class="">
								<div style="text-align: center; width: 120px;">
									<?php echo $vo['class_3_name']; ?>
								</div>
							</td>
							<td align="center" class="">
								<div style="text-align: center; width: 80px;"><?php echo $vo['commis_rate']; ?></div>
							</td>
                            <td align="center" class="">
                                <div style="text-align: center; width: 80px;"><?php if($vo[state] == 1): ?>已通过<?php elseif($vo[state] == 2): ?>被拒绝<?php else: ?>待审核<?php endif; ?></div>
                            </td>
							<td align="center" class="handle">
								<div style="text-align: center; width: 170px; max-width:170px;">
									<a class="btn red"  href="javascript:void(0)" data-url="<?php echo U('Store/apply_class_save'); ?>" data-id="<?php echo $vo['bid']; ?>" onClick="delfun(this)"><i class="fa fa-trash-o"></i>删除</a>
								</div>
							</td>
							<td align="" class="" style="width: 100%;">
								<div>&nbsp;</div>
							</td>
						</tr>
					<?php endforeach; endif; else: echo "" ;endif; ?>
					</tbody>
				</table>
			</div>
			<div class="iDiv" style="display: none;"></div>
		</div>
	 </div>
	<div class="flexigrid" >
		<div class="mDiv">
			<div class="ftitle">
				<h3>添加经营类目</h3>
			</div>
		</div>
	</div>
	<form class="form-horizontal" action="" id="class_info" method="post">
		<div class="ncap-form-default">
			<dl class="row">
				<dt class="tit">
					<label>选择分类</label>
				</dt>
				<dd class="opt">
					<select name="class_1" id="cat_id" onblur="get_category(this.value,'cat_id_2','0');" class="form-control">
						<option value="0">选择分类</option>
						<?php if(is_array($cat_list) || $cat_list instanceof \think\Collection || $cat_list instanceof \think\Paginator): if( count($cat_list)==0 ) : echo "" ;else: foreach($cat_list as $k=>$v): ?>
							<option value="<?php echo $v['id']; ?>" <?php if($v['id'] == $level_cat['1']): ?>selected="selected"<?php endif; ?> >
							<?php echo $v['name']; ?>
							</option>
						<?php endforeach; endif; else: echo "" ;endif; ?>
					</select>
					<select name="class_2" id="cat_id_2" onblur="get_category(this.value,'cat_id_3','0');" class="form-control">
						<option value="0">选择分类</option>
					</select>
					<select name="class_3" id="cat_id_3" class="form-control">
						<option value="0">选择分类</option>
					</select>
					<span class="err"></span>
					<p class="notic"></p>
				</dd>
			</dl>
			<!--<dl class="row">-->
				<!--<dt class="tit">-->
					<!--<label>分佣比例</label>-->
				<!--</dt>-->
				<!--<dd class="opt">-->
					<!--<input type="text" name="commis_rate"  onkeyup="this.value=this.value.replace(/[^\d.]/g,'')" onpaste="this.value=this.value.replace(/[^\d.]/g,'')" class="input-txt">-->
					<!--<span class="err"></span>-->
					<!--<p class="notic">必须为0-100的整数</p>-->
				<!--</dd>-->
			<!--</dl>-->
			<div class="bot"><a href="JavaScript:void(0);" onClick="actsubmit(<?php echo $store['store_id']; ?>)" class="ncap-btn-big ncap-btn-green" id="submitBtn">确认提交</a></div>
		</div>
		<input type="hidden" name="store_id" value="<?php echo $store['store_id']; ?>">
	</form>
</div>
<script type="text/javascript">
	function actsubmit(store_id){
        $.ajax({
            type: 'post',
            url: "{U('admin/Store/store_class_info')}",
            data : $('#class_info').serialize(),
            dataType: 'json',
            success: function (data) {
                if (data.status == 1) {
                    layer.alert('添加成功', {icon: 1});
                    location.href="/index.php/admin/Store/store_class_info/store_id/"+store_id;
                } else {
                    layer.alert(data.msg, {icon: 2});
                }
            },
            error:function(){
                layer.alert('网络连接失败，请稍后再试', {icon: 2});
            }
        })
	}
	function delfun(obj) {
		// 删除按钮
		layer.confirm('确认删除？', {
			btn: ['确定', '取消'] //按钮
		}, function () {
			$.ajax({
				type: 'post',
				url: $(obj).attr('data-url'),
				data : {act:'del',del_id:$(obj).attr('data-id')},
				dataType: 'json',
				success: function (data) {
					layer.closeAll();
					if (data) {
						$(obj).parent().parent().parent().remove();
					} else {
						layer.alert('删除失败', {icon: 2});  //alert('删除失败');
					}
				}
			})
		}, function () {
			layer.closeAll();
		});
	}
</script>
</body>
</html>