<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:48:"./template/mobile/default/index/ajaxGetMore.html";i:1532948114;}*/ ?>
<?php if(is_array($favourite_goods) || $favourite_goods instanceof \think\Collection || $favourite_goods instanceof \think\Paginator): if( count($favourite_goods)==0 ) : echo "" ;else: foreach($favourite_goods as $key=>$v): ?>  
        <li>
            <div class="similer-product products_kuang">
                <a href="<?php echo U('Mobile/Goods/goodsInfo',array('id'=>$v[goods_id])); ?>" title="<?php echo $v['goods_name']; ?>">
                <img src="<?php echo goods_thum_images($v[goods_id],400,400); ?>"/>
                <span class="similar-product-text"><?php echo $v[goods_name]; ?></span>
                </a>
                <span class="similar-product-price">
                    ¥<span class="big-price"><?php echo $v[shop_price]; ?></span>
                    <a href="<?php echo U('Goods/goodsList',['id'=>$v['cat_id3']]); ?>" title="<?php echo $v['goods_name']; ?>">
                        <span class="guess-button J_ping">看相似</span>
                    </a>
                </span>
            </div>
        </li>
<?php endforeach; endif; else: echo "" ;endif; ?>