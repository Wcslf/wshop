<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 *
 * Date: 2019-03-1
 */

namespace app\seller\logic;

use think\Model;
use think\Db;

/**
 * 分类逻辑定义
 * Class CatsLogic
 * @package Home\Logic
 */
class GoodsLogic extends Model
{
    /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     * @param int $goods_id 商品id
     * @param int $type_id 商品属性类型id
     * @return string
     */
    public function getAttrInput($goods_id, $type_id)
    {
        header("Content-type: text/html; charset=utf-8");
        $GoodsAttribute = D('GoodsAttribute');
        $attributeList = $GoodsAttribute->where(["type_id"=>$type_id,'attr_index'=>1])->select();

        foreach ($attributeList as $key => $val) {

            $curAttrVal = $this->getGoodsAttrVal(NULL, $goods_id, $val['attr_id']);
            //促使他 循环
            if (count($curAttrVal) == 0)
                $curAttrVal[] = array('goods_attr_id' => '', 'goods_id' => '', 'attr_id' => '', 'attr_value' => '', 'attr_price' => '');
            foreach ($curAttrVal as $k => $v) {
                $str .= "<tr class='attr_{$val['attr_id']}'>";
                $addDelAttr = ''; // 加减符号
                // 单选属性 或者 复选属性
                if ($val['attr_type'] == 1 || $val['attr_type'] == 2) {
                    if ($k == 0)
                        $addDelAttr .= "<a onclick='addAttr(this)' href='javascript:void(0);'>[+]</a>&nbsp&nbsp";
                    else
                        $addDelAttr .= "<a onclick='delAttr(this)' href='javascript:void(0);'>[-]</a>&nbsp&nbsp";
                }

                $str .= "<td>$addDelAttr {$val['attr_name']}</td> <td>";
 
                // 手工录入
                if ($val['attr_input_type'] == 2) {
                    $str .= "<input type='text' size='40' value='{$v['attr_value']}' name='attr_{$val['attr_id']}[]' />";
                }
                // 从下面的列表中选择（一行代表一个可选值）
                if ($val['attr_input_type'] == 1) {
                    $str .= "<select name='attr_{$val['attr_id']}[]'>";
                    $tmp_option_val = explode(',', $val['attr_values']);

                    $str .= "<option value=''>不限</option>";
                    foreach ($tmp_option_val as $k2 => $v2) {
                        // 编辑的时候 有选中值
                        $v2 = preg_replace("/\s/", "", $v2);
                        if ($v['attr_value'] == $v2)
                            $str .= "<option selected='selected' value='{$v2}'>{$v2}</option>";
                        else
                            $str .= "<option value='{$v2}'>{$v2}</option>";
                    }
                    $str .= "</select>";
                    //$str .= "属性价格<input type='text' maxlength='10' size='5' value='{$v['attr_price']}' name='attr_price_{$val['attr_id']}[]'>";
                }
                $str .= "</td></tr>";
                //$str .= "<br/>";
            }

        }
        return $str;
    }

    /**
     * 获取 tp_goods_attr 表中指定 goods_id  指定 attr_id  或者 指定 goods_attr_id 的值 可是字符串 可是数组
     * @param int $goods_attr_id tp_goods_attr表id
     * @param int $goods_id 商品id
     * @param int $attr_id 商品属性id
     * @return array 返回数组
     */
    public function getGoodsAttrVal($goods_attr_id = 0, $goods_id = 0, $attr_id = 0)
    {
        $GoodsAttr = D('GoodsAttr');
        if ($goods_attr_id > 0)
            return $GoodsAttr->where("goods_attr_id", $goods_attr_id)->select();
        if ($goods_id > 0 && $attr_id > 0)
            return $GoodsAttr->where(['goods_id' => $goods_id, 'attr_id' => $attr_id])->select();
    }

    /**
     *  给指定商品添加属性 或修改属性 更新到 tp_goods_attr
     * @param int $goods_id 商品id
     * @param int $goods_type 商品类型id
     */
    public function saveGoodsAttr($goods_id, $goods_type)
    {
        $GoodsAttr = M('GoodsAttr');
        //$Goods = M("Goods");

        // 属性类型被更改了 就先删除以前的属性类型 或者没有属性 则删除
        if ($goods_type == 0) {
            $GoodsAttr->where('goods_id', $goods_id)->delete();
            return;
        }

        $GoodsAttrList = $GoodsAttr->where('goods_id', $goods_id)->select();

        $old_goods_attr = array(); // 数据库中的的属性  以 attr_id _ 和值的 组合为键名
        foreach ($GoodsAttrList as $k => $v) {
            $old_goods_attr[$v['attr_id'] . '_' . $v['attr_value']] = $v;
        }

        // post 提交的属性  以 attr_id _ 和值的 组合为键名
        $post_goods_attr = array();
        foreach ($_POST as $k => $v) {
            $attr_id = str_replace('attr_', '', $k);
            if (!strstr($k, 'attr_') || strstr($k, 'attr_price_'))
                continue;
            foreach ($v as $k2 => $v2) {
                $v2 = str_replace('_', '', $v2); // 替换特殊字符
                $v2 = str_replace('@', '', $v2); // 替换特殊字符
                $v2 = trim($v2);

                if (empty($v2))
                    continue;


                $tmp_key = $attr_id . "_" . $v2;
                $attr_price = $_POST["attr_price_$attr_id"][$k2];
                $attr_price = $attr_price ? $attr_price : 0;
                if (array_key_exists($tmp_key, $old_goods_attr)) // 如果这个属性 原来就存在
                {
                    if ($old_goods_attr[$tmp_key]['attr_price'] != $attr_price) // 并且价格不一样 就做更新处理
                    {
                        $goods_attr_id = $old_goods_attr[$tmp_key]['goods_attr_id'];
                        $GoodsAttr->where("goods_attr_id", $goods_attr_id)->save(array('attr_price' => $attr_price));
                    }
                } else // 否则这个属性 数据库中不存在 说明要做删除操作
                {
                    $GoodsAttr->add(array('goods_id' => $goods_id, 'attr_id' => $attr_id, 'attr_value' => $v2, 'attr_price' => $attr_price));
                }
                unset($old_goods_attr[$tmp_key]);
            }

        }
        //file_put_contents("b.html", print_r($post_goods_attr,true));
        // 没有被 unset($old_goods_attr[$tmp_key]); 掉是 说明 数据库中存在 表单中没有提交过来则要删除操作

        foreach ($old_goods_attr as $k => $v) {
            $GoodsAttr->where('goods_attr_id', $v['goods_attr_id'])->delete(); //
        }

    }

    /**
     * 获取 规格的 笛卡尔积
     * @param $goods_id 商品 id
     * @param $spec_arr 笛卡尔积
     * @return string 返回表格字符串
     */
    public function getSpecInput($goods_id, $spec_arr, $store_id = 0)
    {
        // <input name="item[2_4_7][price]" value="100" /><input name="item[2_4_7][name]" value="蓝色_S_长袖" />        
        /*$spec_arr = array(         
            20 => array('7','8','9'),
            10=>array('1','2'),
            1 => array('3','4'),
            
        );  */
        // 排序
        $spec_arr_sort = $spec_arr2 = array();
        foreach ($spec_arr as $k => $v) {
            $spec_arr_sort[$k] = count($v);
        }
        asort($spec_arr_sort);
        foreach ($spec_arr_sort as $key => $val) {
            $spec_arr2[$key] = $spec_arr[$key];
        }


        $clo_name = array_keys($spec_arr2);
        $spec_arr2 = combineDika($spec_arr2); //  获取 规格的 笛卡尔积

        $spec = M('Spec')->getField('id,name'); // 规格表
        $specItem = M('SpecItem')->where("store_id", $store_id)->getField('id,item,spec_id');//规格项
        $keySpecGoodsPrice = M('SpecGoodsPrice')->where(['store_id' => $store_id, 'goods_id' => $goods_id])->getField('key,key_name,price,store_count,sku');//规格项

        $str = "<table class='table table-bordered' id='spec_input_tab'>";
        $str .= "<tr>";
        // 显示第一行的数据
        foreach ($clo_name as $k => $v) {
            $str .= " <td><b>{$spec[$v]}</b></td>";
        }
        $str .= "<td><b>价格</b></td>
               <td><b>库存</b></td>
               <td><b>SKU</b></td>
               <td><b>操作</b></td>
             </tr>";
        // 显示第二行开始
        foreach ($spec_arr2 as $k => $v) {
            $str .= "<tr>";
            $item_key_name = array();
            $spec_img_class = '';
            foreach ($v as $k2 => $v2) {
                $str .= "<td>{$specItem[$v2][item]}</td>";
                $item_key_name[$v2] = $spec[$specItem[$v2]['spec_id']] . ':' . $specItem[$v2]['item'];
                $spec_img_class .= 'spec_img_'.$v2.' ';
            }
            ksort($item_key_name);
            $item_key = implode('_', array_keys($item_key_name));
            $item_name = implode(' ', $item_key_name);

            $keySpecGoodsPrice[$item_key][price] ? false : $keySpecGoodsPrice[$item_key][price] = 0; // 价格默认为0
            $keySpecGoodsPrice[$item_key][store_count] ? false : $keySpecGoodsPrice[$item_key][store_count] = 0; //库存默认为0
            $str .= "<td><input class='batch-fill-text1' name='item[$item_key][price]' value='{$keySpecGoodsPrice[$item_key][price]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
            $str .= "<td><input class='batch-fill-text2' name='item[$item_key][store_count]' value='{$keySpecGoodsPrice[$item_key][store_count]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")'/></td>";
            $str .= "<td><input class='batch-fill-text3' name='item[$item_key][sku]' value='{$keySpecGoodsPrice[$item_key][sku]}' /><input type='hidden' name='item[$item_key][key_name]' value='$item_name' /></td>";
            $str .= "<td><button type='button' class='btn btn-default delete_item'>无效</button></td>";
            $str .= "</tr>";
        }
        $str .= "</table>";
        return $str;
    }
    /**
     *  获取排好序的品牌列表

     */
    function getSortBrands()
    {
        $brandList = S('getSortBrands');
        if(!empty($brandList))
            return $brandList;

        $brandList = M("Brand")->select();
//        $brandIdArr =  M("Brand")->where("name in (select `name` from `".C('database.prefix')."brand` group by name having COUNT(id) > 1)")->getField('id,cat_id2');
        $brandIdArr = Db::name('brand')->where('id', 'IN', function ($query) {
            $query->name('brand')->group('name')->having('COUNT(id) > 1')->field('name');
        })->getField('id,cat_id2');
        $goodsCategoryArr = M('goodsCategory')->where("level", 1)->getField('id,name');
        $nameList = array();
        foreach ($brandList as $k => $v) {

            $name = getFirstCharter($v['name']) . '  --   ' . $v['name']; // 前面加上拼音首字母

            if (array_key_exists($v[id], $brandIdArr) && $v[cat_id]) // 如果有双重品牌的 则加上分类名称
                $name .= ' ( ' . $goodsCategoryArr[$v[cat_id]] . ' ) ';

            $nameList[] = $v['name'] = $name;
            $brandList[$k] = $v;
        }
        array_multisort($nameList, SORT_STRING, SORT_ASC, $brandList);
        S('getSortBrands',$brandList); 
        return $brandList;
    }

    /**
     *  获取排好序的分类列表
     */
    function getSortCategory()
    {
        $categoryList = Db::name("GoodsCategory")->where(['level'=>3,])->getField('id,name,parent_id,level');
        $nameList = array();
        foreach ($categoryList as $k => $v) {
            $name = getFirstCharter($v['name']) . ' ' . $v['name']; // 前面加上拼音首字母
            $nameList[] = $v['name'] = $name;
            $categoryList[$k] = $v;
        }
        array_multisort($nameList, SORT_STRING, SORT_ASC, $categoryList);

        return $categoryList;
    }

    /**
     * 删除商品(可批量删除)
     * @param $goods_ids
     * @return array
     */
    function delStoreGoods($goods_ids=''){
        if(empty($goods_ids)) return ['status' => -1, 'msg' => "非法操作！"];
        // 判断此商品是否有订单
        $ordergoods_count = Db::name('order_goods')->whereIn('goods_id', $goods_ids, 'and')->group('goods_id')->getField('goods_id', true);
        if ($ordergoods_count) {
            $goods_count_ids = implode(',', $ordergoods_count);
            return ['status' => -1, 'msg' => "ID为【{$goods_count_ids}】的商品有订单,不得删除!"];
        }
        // 商品团购
        $groupBuy_goods = M('group_buy')->whereIn('goods_id', $goods_ids, 'and')->group('goods_id')->getField('goods_id', true);
        if ($groupBuy_goods) {
            $groupBuy_goods_ids = implode(',', $groupBuy_goods);
            return ['status' => -1, 'msg' => "ID为【{$groupBuy_goods_ids}】的商品有团购,不得删除!"];
        }
        // 商品退货记录
        $return_goods = Db::name('return_goods')->whereIn('goods_id', $goods_ids, 'and')->group('goods_id')->getField('goods_id', true);
        if ($return_goods) {
            $return_goods_ids = implode(',', $groupBuy_goods);
            return ['status' => -1, 'msg' => "ID为【{$return_goods_ids}】的有退货记录,不得删除!"];
        }
        // 删除此商品
        $result = Db::name('Goods')->where(['store_id' => STORE_ID])->whereIn('goods_id', $goods_ids, 'and')->delete();  //商品表
        if ($result !== false) {
            Db::name('cart')->whereIn('goods_id', $goods_ids, 'and')->delete();  // 购物车
            Db::name('comment')->whereIn('goods_id', $goods_ids, 'and')->delete();  //商品评论
            Db::name('goods_consult')->whereIn('goods_id', $goods_ids, 'and')->delete();  //商品咨询
            Db::name('goods_images')->whereIn('goods_id', $goods_ids, 'and')->delete();  //商品相册
            Db::name('spec_goods_price')->whereIn('goods_id', $goods_ids, 'and')->delete();  //商品规格
            Db::name('spec_image')->whereIn('goods_id', $goods_ids, 'and')->delete();  //商品规格图片
            Db::name('goods_attr')->whereIn('goods_id', $goods_ids, 'and')->delete();  //商品属性
            Db::name('goods_collect')->whereIn('goods_id', $goods_ids, 'and')->delete();  //商品收藏
            return ['status' => 1, 'msg' => '操作成功',];
        }else{
            return ['status' => -1, 'msg' => '操作失败',];
        }
    }
    /**
     * 后置操作方法
     * 自定义的一个函数 用于数据保存后做的相应处理操作, 使用时手动调用
     * @param int $goods_id 商品id
     * @param int $store_id 店铺id
     */
    public function afterSave($goods_id,$store_id)
    {
        // 商品货号
        $goods_sn = "TP".str_pad($goods_id,7,"0",STR_PAD_LEFT);
        Db::name('goods')->where("goods_id = $goods_id and goods_sn = ''")->save(array("goods_sn"=>$goods_sn)); // 根据条件更新记录
        $goods_images = I('goods_images/a');
        $img_sorts = I('img_sorts/a');
        $original_img = I('original_img');
        $item_img = I('item_img/a');

        // 商品图片相册  图册
        if(count($goods_images) > 1)
        {
            array_pop($goods_images); // 弹出最后一个
            $goodsImagesArr = M('GoodsImages')->where("goods_id = $goods_id")->getField('img_id,image_url'); // 查出所有已经存在的图片

            // 删除图片
            foreach($goodsImagesArr as $key => $val)
            {
                if(!in_array($val, $goods_images)){
                    M('GoodsImages')->where("img_id = {$key}")->delete();

                    //同时删除物理文件
                    $filename = $val;
                    $filename= str_replace('../','',$filename);
                    $filename= trim($filename,'.');
                    $filename= trim($filename,'/');
                    $is_exists = file_exists($filename);

                    //同时删除物理文件
                    if($is_exists){
                        unlink($filename);
                    }
                }
            }
            $goodsImagesArrRever = array_flip($goodsImagesArr);
            // 添加图片
            foreach($goods_images as $key => $val)
            {
                if ($original_img ==$val){  //商品主图默认排在最前
                    $sort = $img_sorts[$key]?$img_sorts[$key]:0;
                }else{
                    $sort = $img_sorts[$key]?$img_sorts[$key]:1;
                }
                if($val == null)  continue;
                if(!in_array($val, $goodsImagesArr))
                {
                    $data = array( 'goods_id' => $goods_id,'image_url' => $val , 'img_sort'=>$sort);
                    M("GoodsImages")->insert($data); // 实例化User对象
                }else{
                    $img_id = $goodsImagesArrRever[$val];
                    //修改图片顺序
                    M('GoodsImages')->where("img_id = {$img_id}")->save(array('img_sort' => $sort));
                }
            }
        }
        // 查看主图是否已经存在相册中
        $c = M('GoodsImages')->where("goods_id = $goods_id and image_url = '{$original_img}'")->count();

        //@modify by wangqh fix:修复删除商品详情的图片(相册图刚好是主图时)删除的图片仍然在相册中显示. 如果主图存物理图片存在才添加到相册 @{
        $deal_orignal_img = str_replace('../','',$original_img);
        $deal_orignal_img= trim($deal_orignal_img,'.');
        $deal_orignal_img= trim($deal_orignal_img,'/');
        if($c == 0 && $original_img && file_exists($deal_orignal_img))//@}
        {
            M("GoodsImages")->add(array('goods_id'=>$goods_id,'image_url'=>$original_img,'image_sort'=>0));
        }
        clearCache();
        // 商品规格价钱处理
        $goods_item = I('item/a');

        if ($goods_item) {
            $store_count = 0;
            $keyArr = '';//规格key数组
            foreach ($goods_item as $k => $v) {
                $keyArr .= $k.',';
                //批量添加数据
                $v['price'] = trim($v['price']);
                $store_count += $v['store_count']; // 记录商品总库存
                $v['sku'] = trim($v['sku']);
                $data = array('goods_id' => $goods_id, 'key' => $k, 'key_name' => $v['key_name'], 'price' => $v['price'], 'store_count' => $v['store_count'], 'sku' => $v['sku'], 'store_id' => $store_id);
                $specGoodsPrice = Db::name('spec_goods_price')->where(['goods_id' => $data['goods_id'], 'key' => $data['key']])->find();
                if ($item_img) {
                    $spec_key_arr = explode('_', $k);
                    foreach ($item_img as $key => $val) {
                        if (in_array($key, $spec_key_arr)) {
                            $data['spec_img'] = $val;
                            break;
                        }
                    }
                }
                if($specGoodsPrice){
                    Db::name('spec_goods_price')->where(['goods_id' => $goods_id, 'key' => $k])->update($data);
                    $stock = $specGoodsPrice['store_count'] - $data['store_count'];
                    if($stock != 0){
                        update_stock_log(session('seller_id'), $stock,array('goods_id'=>$goods_id,'key_name'=>$data['key_name'],'goods_name'=>I('goods_name'),'store_id'=>$store_id));
                    }
                }else{
                    Db::name('spec_goods_price')->insert($data);
                    update_stock_log(session('seller_id'), $data['store_count'],array('goods_id'=>$data['goods_id'],'key_name'=>$data['key_name'],'goods_name'=>I('goods_name'),'store_id'=>$store_id));
                }
                // 修改商品后购物车的商品价格也修改一下
                M('cart')->where("goods_id = $goods_id and spec_key = '$k'")->save(array(
                    'market_price' => $v['price'], //市场价
                    'goods_price' => $v['price'], // 本店价
                    'member_goods_price' => $v['price'], // 会员折扣价
                ));
            }
            if($keyArr){
                Db::name('spec_goods_price')->where('goods_id',$goods_id)->whereNotIn('key',substr($keyArr,0,-1))->delete();
            }
        }else{
            Db::name('spec_goods_price')->where(['goods_id' => $goods_id])->delete();
        }

        // 商品规格图片处理
        if($item_img)
        {
            M('SpecImage')->where("goods_id = $goods_id")->delete(); // 把原来是删除再重新插入
            foreach ($item_img as $key => $val)
            {
                M('SpecImage')->insert(array('goods_id'=>$goods_id ,'spec_image_id'=>$key,'src'=>$val,'store_id'=>$store_id));
            }
        }
        refresh_stock($goods_id); // 刷新商品库存
    }
}