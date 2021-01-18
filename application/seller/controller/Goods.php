<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * ============================================================================
 *
 * Date: 2019-03-1
 */
namespace app\seller\controller;

use app\common\model\SpecGoodsPrice;
use app\common\model\Goods as GoodsModel;
use app\seller\logic\GoodsCategoryLogic;
use app\seller\logic\GoodsLogic;
use app\seller\logic\AdminLogic;
use think\Db;
use think\Page;
use think\AjaxPage;
use think\Loader;

class Goods extends Base
{

    /**
     * 删除分类
     */
    public function delGoodsCategory()
    {
        // 判断子分类
        $GoodsCategory = M("GoodsCategory");
        $count = $GoodsCategory->where("parent_id", $_GET['id'])->count("id");
        $count > 0 && $this->error('该分类下还有分类不得删除!', U('Admin/Goods/categoryList'));
        // 判断是否存在商品
        $goods_count = M('Goods')->where("cat_id", $_GET['id'])->count('1');
        $goods_count > 0 && $this->error('该分类下有商品不得删除!', U('Admin/Goods/categoryList'));
        // 删除分类
        $GoodsCategory->where("id", $_GET['id'])->delete();
        $this->success("操作成功!!!", U('Admin/Goods/categoryList'));
    }

    /**
     *  商品列表
     */
    public function goodsList()
    {
        checkIsBack();
        $nowPage = 1;
        $store_goods_class_list = M('store_goods_class')->where(['parent_id' => 0, 'store_id' => STORE_ID])->select();
        $this->assign('store_goods_class_list', $store_goods_class_list);
        $suppliers_list = M('suppliers')->where(array('store_id'=>STORE_ID))->select();
        $this->assign('suppliers_list', $suppliers_list);
        $this->assign("now_page" , $nowPage);
        return $this->fetch('goodsList');
    }

    /**
     *  商品列表
     */
    public function ajaxGoodsList()
    {
        $where['store_id'] = STORE_ID;
        $intro = I('intro', 0);
        $store_cat_id1 = I('store_cat_id1', '');
        $key_word = trim(I('key_word', ''));
        $orderby1 = I('post.orderby1', '');
        $orderby2 = I('post.orderby2', '');
        $suppliers_id = input('suppliers_id','');
        if($suppliers_id !== ''){
            $where['suppliers_id'] = $suppliers_id;
        }
        if (!empty($intro)) {
            $where[$intro] = 1;
        }
        if ($store_cat_id1 !== '') {
            $where['store_cat_id1'] = $store_cat_id1;
        }
        $where['is_on_sale'] = 1;
        $where['goods_state'] = 1;
        if ($key_word !== '') {
            $where['goods_name|goods_sn'] = array('like', '%' . $key_word . '%');
        }
        $order_str = array();
        if ($orderby1 !== '') {
            $order_str[$orderby1] = $orderby2;
        }
        $model = M('Goods');
        $count = $model->where($where)->count();
        $Page = new AjaxPage($count, 10);

        //是否从缓存中获取Page
        if (session('is_back') == 1) {
            $Page = getPageFromCache();
            //重置获取条件
            delIsBack();
        }
        $goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        cachePage($Page);
        $show = $Page->show();
        
        $catList =  M('goods_category')->cache(true)->select();
        $catList = convert_arr_key($catList, 'id');
        $store_warning_storage = M('store')->where('store_id', STORE_ID)->getField('store_warning_storage');
        $this->assign('store_warning_storage', $store_warning_storage);
        $this->assign('catList', $catList);
        $this->assign('goodsList', $goodsList);
        $this->assign('page', $show);// 赋值分页输出
        return $this->fetch();
    }
    
    public function goods_offline(){
    	$where['store_id'] = STORE_ID;
    	$model = M('Goods');
        $suppliers_id = input('suppliers_id');
        if($suppliers_id){
            $where['suppliers_id'] = $suppliers_id;
        }
    	if(I('is_on_sale') == 2){
    		$where['is_on_sale'] = 2;
    	}else{
  			$where['is_on_sale'] = 0;
    	}
    	$goods_state = I('goods_state', '', 'string'); // 商品状态  0待审核 1审核通过 2审核失败
    	if($goods_state != ''){
    		$where['goods_state'] = intval($goods_state);
    	}
    	$store_cat_id1 = I('store_cat_id1', '');
    	if ($store_cat_id1 !== '') {
    		$where['store_cat_id1'] = $store_cat_id1;
    	}
    	$key_word = trim(I('key_word', ''));
    	if ($key_word !== '') {
    		$where['goods_name|goods_sn'] = array('like', '%' . $key_word . '%');
    	}
    	$count = $model->where($where)->count();
    	$Page = new Page($count, 10);
    	$goodsList = $model->where($where)->order('goods_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
    	$show = $Page->show();
    	$store_goods_class_list = M('store_goods_class')->where(['parent_id' => 0, 'store_id' => STORE_ID])->select();
    	$this->assign('store_goods_class_list', $store_goods_class_list);
    	$suppliers_list = M('suppliers')->where(array('store_id'=>STORE_ID))->select();
    	$this->assign('suppliers_list', $suppliers_list);
		$this->assign('state',C('goods_state'));
    	$this->assign('goodsList', $goodsList);
    	$this->assign('page', $show);// 赋值分页输出
    	return $this->fetch();
    } 

    public function stock_log()
    {
        $map['store_id'] = STORE_ID;
        $mtype = I('mtype');
        if ($mtype == 1) {
            $map['stock'] = array('gt', 0);
        }
        if ($mtype == -1) {
            $map['stock'] = array('lt', 0);
        }
        $goods_name = I('goods_name');
        if ($goods_name) {
            $map['goods_name'] = array('like', "%$goods_name%");
        }
        $ctime = urldecode(I('post.ctime'));
        if ($ctime) {
            $gap = explode(' - ', $ctime);
            $this->assign('ctime', $gap[0] . ' - ' . $gap[1]);
            $this->assign('start_time', $gap[0]);
            $this->assign('end_time', $gap[1]);
            $map['ctime'] = array(array('gt', strtotime($gap[0])), array('lt', strtotime($gap[1])));
        }
        $count = Db::name('stock_log')->where($map)->count();
        $Page = new Page($count, 20);
        $show = $Page->show();
        $this->assign('page', $show);// 赋值分页输出
        $stock_list = Db::name('stock_log')->where($map)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('stock_list', $stock_list);
        return $this->fetch();
    }
    
    public function stock_list(){
    	$map['store_id'] = STORE_ID;
    	$goods_name = I('goods_name');
    	$spec_name = I('spec_name');
    	if ($goods_name) {
    		$map['goods_name'] = array('like', "%$goods_name%");
    		$gids = M('goods')->where($map)->getField('goods_id',true);
            unset($map['goods_name']);
    		if($gids){
    			$map['goods_id'] = array('in',$gids);
    		}
    	}
    	if($spec_name){
    		$map['key_name'] = array('like', "%$spec_name%");
    	}
    	$count = Db::name('spec_goods_price')->where($map)->count();
    	$Page = new Page($count, 20);
    	$show = $Page->show();
    	$this->assign('page', $show);// 赋值分页输出
        $SpecGoodsPrice = new SpecGoodsPrice();
    	$stock_list = $SpecGoodsPrice->where($map)->order('item_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
    	$this->assign('stock_list', $stock_list);
    	if($stock_list){
            $stock_list = collection($stock_list)->toArray();
    		$goodsIds = get_arr_column($stock_list, 'goods_id');
    		$goods = M('goods')->where(array('goods_id'=>array('in',$goodsIds)))->getField('goods_id,goods_name');
    		$this->assign('goods',$goods);
    	}
    	return $this->fetch();
    }
    
    public function updateGoodsStock(){
    	$item_id = I('item_id/d');
    	$store_count = I('store_count/d');
    	$old_stock = I('old_stock');
    	$spec_goods = Db::name('spec_goods_price')->alias('s')->field('s.*,g.goods_name')->join('__GOODS__ g', 'g.goods_id = s.goods_id', 'LEFT')->where(['s.item_id'=>$item_id])->find();
    	$r = M('spec_goods_price')->where(array('item_id'=>$item_id))->save(array('store_count'=>$store_count));
    	if($r){
    		$stock = $store_count - $old_stock;
    		$goods = array('goods_id'=>$spec_goods['goods_id'],'goods_name'=>$spec_goods['goods_name'],'key_name'=>$spec_goods['key_name'],'store_id'=>STORE_ID);
    		update_stock_log(STORE_ID, $stock,$goods);
    		exit(json_encode(array('status'=>1,'msg'=>'修改成功')));
    	}else{
    		exit(json_encode(array('status'=>0,'msg'=>'修改失败')));
    	}
    }

    /**
     *
     */
    public function addStepOne(){
        //限制发布商品数量，0为不限制
        $alreadyPushNum = Db::name('goods')->where(['store_id' => STORE_ID])->count();
        $sgGoodsLimit = Db::name('store_grade')->where(['sg_id' => $this->storeInfo['grade_id']])->getField('sg_goods_limit');
        if($alreadyPushNum >= $sgGoodsLimit && $sgGoodsLimit > 0 && $this->storeInfo['is_own_shop'] !=1){
            $this->error("可发布商品数量已达到上限", U('Goods/goodsList'));
        }
        $goods_id = input('goods_id');
        if($goods_id){
            $goods = Db::name('goods')->where('goods_id',$goods_id)->find();
            $this->assign('goods',$goods);
        }
        $GoodsCategoryLogic = new GoodsCategoryLogic();
        $GoodsCategoryLogic->setStore($this->storeInfo);
        $goodsCategoryLevelOne = $GoodsCategoryLogic->getStoreGoodsCategory();
        $this->assign('goodsCategoryLevelOne',$goodsCategoryLevelOne);
        return $this->fetch();
    }

    /**
     * 相册图片
     */
    public function album_image(){
        
        $album_id = I('album_id/d' , 0);
        /* 列出图片 */
        $allowFiles = 'png|jpg|jpeg|gif|bmp';
        $path = UPLOAD_PATH.'store/'.session('store_id').'/goods_other_album/album_'.$album_id;
        $key = empty($_GET['key']) ? '' : $_GET['key'];
        $listSize = 20;

        /* 获取参数 */
        $size = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $listSize;
    	$page = isset($_GET['p']) ? htmlspecialchars($_GET['p']) : 1;
    	$start = ($page-1)*$size;
    	$end = $start + $size;

        /* 获取文件列表 */
        $adminLogc = new AdminLogic();
        $file = $adminLogc->getfiles($path, $allowFiles, $key ,true);
        $urls = array();
        if (!count($file)) {
            $this->assign('imageList',array());
        }else{
            /* 获取指定范围的列表 */
            $len = count($file);
            for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--){
                $list[] = $file[$i];
                array_push($urls , $file[$i]['url']);
            }
            $where = implode($urls, ",");
            $extends = M('Image_extend')->where('img_url' , 'in' , $where)->getField("img_url , cn_name , en_name" , true);
            /* 返回数据 */
            $Page = new Page($len, $size);
            $show = $Page->show();
            $this->assign('show_page', $show);
            $this->assign('imageList',$list);
            $this->assign('extends',$extends);
        }
          
        $this->assign("album_id" , $album_id);
         
        return $this->fetch();
        
    }
    
    
    /**
     * 添加修改商品
     */
    public function set_mobile_url(){
        
        $album_id = I('album_id/d' , 0);
        $album_img = I('filename/s' , '');
        
        if(empty($album_id) || empty($album_img))$this->ajaxReturn(['status'=>-1 , 'msg'=>'相册ID或图片路径错误']);
        
        $row = M('StoreAlbum')->where(['id'=>$album_id])->save(['album_img'=>$album_img]);
        
        if($row>0)$this->ajaxReturn(['status'=>1 , 'msg'=>'设置成功']);
    }
    
    
    
    /**
     * 添加修改商品
     */
    public function addEditGoods()
    {
        $goods_id = I('goods_id/d', 0);
        $goods_cat_id3 = I('cat_id3/d', 0);
        if(empty($goods_id)){
            if(empty($goods_cat_id3)){
                $this->error("您选择的分类不存在，或没有选择到最后一级，请重新选择分类。", U('Goods/addStepOne'));
            }
            $goods_cat[0] = Db::name('goods_category')->where('id', $goods_cat_id3)->find();
            $goods_cat[1] = Db::name('goods_category')->where('id', $goods_cat[0]['parent_id'])->find();
            $goods_cat[2] = Db::name('goods_category')->where('id', $goods_cat[1]['parent_id'])->find();
        }else{
            $Goods = new GoodsModel();
            $goods_info = $Goods->where(['goods_id' => $goods_id, 'store_id' => STORE_ID])->find();
            if(empty($goods_info)){
                $this->error("非法操作", U('Goods/goodsList'));
            }else{
                $this->assign('goodsInfo', $goods_info);  // 商品详情
            }
            $goods_cat = Db::name('goods_category')->where('id','IN',[$goods_info['cat_id1'],$goods_info['cat_id2'],$goods_info['cat_id3']])->order('level desc')->select();
        }

        //版式
        $plate_1=M('store_plate')->where('plate_position=1 and store_id='.STORE_ID)->field('plate_id,plate_name')->select();
        $plate_0=M('store_plate')->where('plate_position=0 and store_id='.STORE_ID)->field('plate_id,plate_name')->select();
        $this->assign('plate_1',$plate_1);
        $this->assign('plate_0',$plate_0);
        
        $GoodsLogic = new GoodsLogic();
        $store_goods_class_list = Db::name('store_goods_class')->where(['parent_id' => 0, 'store_id' => STORE_ID])->select(); //店铺内部分类
        if (!input('cat_id3')) {
            $goodscategory = model('goods_category')->where('id',$goods_cat[0]['id'])->field('type_id')->find();
        }else{
            $goodscategory = model('goods_category')->where('id',input('cat_id3'))->field('type_id')->find();
        }
        $brandList = db('brand_type')->join('tp_brand','tp_brand_type.brand_id=tp_brand.id','LEFT')->where('tp_brand_type.type_id',$goodscategory['type_id'])->select();
//        $brandList = $GoodsLogic->getSortBrands();
        $goodsType = Db::name("GoodsType")->select();
        $suppliersList = Db::name("suppliers")->where(['is_check'=>1,'store_id'=>STORE_ID])->select();
        $goodsImages = Db::name("GoodsImages")->where('goods_id', $goods_id)->order('img_sort asc')->select();
        $freight_template = Db::name('freight_template')->where(['store_id' => STORE_ID])->select();
        $this->assign('freight_template',$freight_template);
        $this->assign('goods_cat', $goods_cat);
        $this->assign('store_id', STORE_ID);
        $this->assign('store_goods_class_list', $store_goods_class_list);
        $this->assign('brandList', $brandList);
        $this->assign('goodsType', $goodsType);
        $this->assign('suppliersList', $suppliersList);
        $this->assign('goodsImages', $goodsImages);  // 商品相册
        return $this->fetch('_goods');
    }

    /**
     * 商品保存
     */
    public function save(){


        // 数据验证
        $data =input('post.');
        $goods_id = input('post.goods_id');
        $goods_cat_id3 = input('post.cat_id3');
        $spec_goods_item = input('post.item/a',[]);
        $store_count = input('post.store_count');
        $is_virtual = input('post.is_virtual');
        $virtual_indate = I('post.virtual_indate');//虚拟商品有效期
        $exchange_integral = I('post.exchange_integral');//虚拟商品有效期
        $validate = Loader::validate('Goods');
        $data['store_id'] = STORE_ID;
        if (!$validate->batch()->check($data)) {
            $error = $validate->getError();
            $error_msg = array_values($error);
            $return_arr = array('status' => -1,'msg' => $error_msg[0],'data' => $error);
            $this->ajaxReturn($return_arr);
        }
        $data['on_time'] = time(); // 上架时间
        $type_id = M('goods_category')->where("id", $goods_cat_id3)->getField('type_id'); // 找到这个分类对应的type_id
        $stores = M('store')->where(array('store_id' => STORE_ID))->getField('store_id , goods_examine,is_own_shop' , 1);
        $store_goods_examine = $stores[STORE_ID]['goods_examine'];
        if ($store_goods_examine) {
            $data['goods_state'] = 0; // 待审核
            $data['is_on_sale'] = 0; // 下架
        } else {
            $data['goods_state'] = 1; // 出售中
        }
        //总平台自营标识为2 , 第三方自营店标识为1
        $is_own_shop = (STORE_ID == 1) ? 2 : ($stores[STORE_ID]['is_own_shop']);
        $data['is_own_shop'] = $is_own_shop;
        $data['goods_type'] = $type_id ? $type_id : 0;
        $data['virtual_indate'] = strtotime($virtual_indate)>0 ? strtotime($virtual_indate) : 0;
        $data['exchange_integral'] = ($is_virtual == 1) ? 0 : $exchange_integral;
        //序列化保存手机端商品描述数据
        if ($_POST['m_body'] != '') {
        	$_POST['m_body'] = str_replace('&quot;', '"', $_POST['m_body']);
        	$_POST['m_body'] = json_decode($_POST['m_body'], true);
        	if (!empty($_POST['m_body'])) {
        		$_POST['m_body'] = serialize($_POST['m_body']);
        	} else {
        		$_POST['m_body'] = '';
        	}
        }
        $data['mobile_content'] = $_POST['m_body'];
        
        if ($goods_id > 0) {
            $Goods = GoodsModel::get(['goods_id' => $goods_id, 'store_id' => STORE_ID]);
            if(empty($Goods)){
                $this->ajaxReturn(array('status' => 0, 'msg' => '非法操作','result'=>''));
            }
            if (empty($spec_goods_item) && $store_count != $Goods['store_count']) {
                $real_store_count = $store_count - $Goods['store_count'];
                update_stock_log(session('admin_id'), $real_store_count, array('goods_id' => $goods_id, 'goods_name' => $Goods['goods_name'], 'store_id' => STORE_ID));
            } else {
                unset($data['store_count']);
            }
            $Goods->data($data, true); // 收集数据
            $update = $Goods->save(); // 写入数据到数据库
            // 更新成功后删除缩略图
            if($update !== false){
                // 修改商品后购物车的商品价格也修改一下
                Db::name('cart')->where("goods_id", $goods_id)->where("spec_key = ''")->save(array(
                    'market_price' => $Goods['market_price'], //市场价
                    'goods_price' => $Goods['shop_price'], // 本店价
                    'member_goods_price' => $Goods['shop_price'], // 会员折扣价
                ));
                delFile("./public/upload/goods/thumb/$goods_id", true);
            }
        } else {
            $Goods = new GoodsModel();
            $Goods->data($data, true); // 收集数据
            $Goods->save(); // 新增数据到数据库
            $goods_id = $Goods->getLastInsID();
            //商品进出库记录日志
            if (empty($spec_goods_item)) {
                update_stock_log(session('admin_id'), $store_count, array('goods_id' => $goods_id, 'goods_name' => $Goods['goods_name'], 'store_id' => STORE_ID));
            }
        }
        $GoodsLogic = new GoodsLogic();
        $GoodsLogic->afterSave($goods_id, STORE_ID);
        $GoodsLogic->saveGoodsAttr($goods_id, $type_id); // 处理商品 属性
        $this->ajaxReturn([ 'status' => 1, 'msg' => '操作成功', 'result' => ['goods_id'=>$Goods->goods_id]]);
    }

    /**
     * 更改指定表的指定字段
     */
    public function updateField()
    {
        $primary = array(
            'goods' => 'goods_id',
            'goods_attribute' => 'attr_id',
            'ad' => 'ad_id',
        );
        $id = I('id/d', 0);
        $field = I('field');
        $value = I('value');
        Db::name($_POST['table'])->where($primary[$_POST['table']], $id)->where('store_id', STORE_ID)->save(array($field => $value));
        $return_arr = array(
            'status' => 1,
            'msg' => '操作成功',
            'data' => array('url' => U('Goods/goodsAttributeList')),
        );
        $this->ajaxReturn($return_arr);
    }

    /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     */
    public function ajaxGetAttrInput()
    {
        $cat_id3 = I('cat_id3/d', 0);
        $goods_id = I('goods_id/d', 0);
        empty($cat_id3) && exit('');
        $type_id = M('goods_category')->where("id", $cat_id3)->getField('type_id'); // 找到这个分类对应的type_id
        empty($type_id) && exit('');
        $GoodsLogic = new GoodsLogic();
        $str = $GoodsLogic->getAttrInput($goods_id, $type_id);
        exit($str);
    }

    /**
     * 删除商品
     */
    public function delGoods()
    {
        $ids= I('ids');
        $GoodsLogic = new GoodsLogic();
        $res = $GoodsLogic->delStoreGoods($ids);
        $this->ajaxReturn($res);
    }

    /**
     * ajax 获取 品牌列表
     */
    public function getBrandByCat()
    {
        $db_prefix = C('database.prefix');
        $type_id = I('type_id/d');
        if ($type_id) {
//            $list = M('brand')->join("left join {$db_prefix}brand_type on {$db_prefix}brand.id = {$db_prefix}brand_type.brand_id and  type_id = $type_id")->order('id')->select();
            $list = Db::name('brand')->alias('b')->join('__BRAND_TYPE__ t', 't.brand_id = b.id', 'LEFT')->where(['t.type_id' => $type_id])->order('b.id')->select();
        } else {
            $list = M('brand')->order('id')->select();
        }
//        $goods_category_list = M('goods_category')->where("id in(select cat_id1 from {$db_prefix}brand) ")->getField("id,name,parent_id");
        $goods_category_list = Db::name('goods_category')
            ->where('id', 'IN', function ($query) {
                $query->name('brand')->where('')->field('cat_id1');
            })
            ->getField("id,name,parent_id");
        $goods_category_list[0] = array('id' => 0, 'name' => '默认');
        asort($goods_category_list);
        $this->assign('goods_category_list', $goods_category_list);
        $this->assign('type_id', $type_id);
        $this->assign('list', $list);
        return $this->fetch();
    }


    /**
     * ajax 获取 规格列表
     */
    public function getSpecByCat()
    {

        $db_prefix = C('database.prefix');
        $type_id = I('type_id/d');
        if ($type_id) {
//            $list = M('spec')->join("left join {$db_prefix}spec_type on {$db_prefix}spec.id = {$db_prefix}spec_type.spec_id  and  type_id = $type_id")->order('id')->select();
            $list = Db::name('spec')->alias('s')->join('__SPEC_TYPE__ t', 't.spec_id = s.id', 'LEFT')->where(['t.type_id' => $type_id])->order('s.id')->select();
        } else {
            $list = M('spec')->order('id')->select();
        }
//        $goods_category_list = M('goods_category')->where("id in(select cat_id1 from {$db_prefix}spec) ")->getField("id,name,parent_id");
        $goods_category_list = Db::name('goods_category')
            ->where('id', 'IN', function ($query) {
                $query->name('spec')->where('')->field('cat_id1');
            })
            ->getField("id,name,parent_id");
        $goods_category_list[0] = array('id' => 0, 'name' => '默认');
        asort($goods_category_list);
        $this->assign('goods_category_list', $goods_category_list);
        $this->assign('type_id', $type_id);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect()
    {
        $goods_id = I('goods_id/d', 0);
        $cat_id3 = I('cat_id3/d', 0);
        empty($cat_id3) && exit('');
        $goods_id = $goods_id ? $goods_id : 0;

        $type_id = M('goods_category')->where("id", $cat_id3)->getField('type_id'); // 找到这个分类对应的type_id
        empty($type_id) && exit('');
        $spec_id_arr = M('spec_type')->where("type_id", $type_id)->getField('spec_id', true); // 找出这个类型的 所有 规格id
        empty($spec_id_arr) && exit('');

        $specList = D('Spec')->where("id", "in", implode(',', $spec_id_arr))->order('`order` desc')->select(); // 找出这个类型的所有规格

        if ($specList) {
            foreach ($specList as $k => $v) {
                $specList[$k]['spec_item'] = D('SpecItem')->where(['store_id' => STORE_ID, 'spec_id' => $v['id']])->getField('id,item'); // 获取规格项
            }
        }

        $items_id = M('SpecGoodsPrice')->where("goods_id", $goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
        $items_ids = explode('_', $items_id);

        // 获取商品规格图片                
        if ($goods_id) {
            $specImageList = M('SpecImage')->where("goods_id", $goods_id)->getField('spec_image_id,src');
        }
        $this->assign('specImageList', $specImageList);

        $this->assign('items_ids', $items_ids);
        $this->assign('specList', $specList);
        return $this->fetch('ajax_spec_select');
    }

    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetSpecInput()
    {
        $GoodsLogic = new GoodsLogic();
        $goods_id = I('get.goods_id/d', 0);
        $spec_arr = I('spec_arr/a', []);
        $str = $GoodsLogic->getSpecInput($goods_id, $spec_arr, STORE_ID);
        $this->ajaxReturn(['status'=>1,'msg'=>'','result'=>$str]);
    }

    /**
     * 商家发布商品时添加的规格
     */
    public function addSpecItem()
    {
        $spec_id = I('spec_id/d', 0); // 规格id
        $spec_item = I('spec_item', '', 'trim');// 规格项

        $c = M('spec_item')->where(['store_id' => STORE_ID, 'item' => $spec_item, 'spec_id' => $spec_id])->count();
        $count = db('spec_item')->where(['spec_id'=>$spec_id,'store_id'=>STORE_ID])->count();
        if ($count >= 15) {
            $return_arr = array(
                'status' => -1,
                'msg' => '规格值最多添加15个',
                'data' => '',
            );
            exit(json_encode($return_arr));
        }
        if ($c > 0) {
            $return_arr = array(
                'status' => -1,
                'msg' => '规格已经存在',
                'data' => '',
            );
            exit(json_encode($return_arr));
        }
        $data = array(
            'spec_id' => $spec_id,
            'item' => $spec_item,
            'store_id' => STORE_ID,
        );
        M('spec_item')->add($data);

        $return_arr = array(
            'status' => 1,
            'msg' => '添加成功!',
            'data' => '',
        );
        exit(json_encode($return_arr));
    }

    /**
     * 商家发布商品时删除的规格
     */
    public function delSpecItem()
    {
        $spec_id = I('spec_id/d', 0); // 规格id
        $spec_item = I('spec_item', '', 'trim');// 规格项
        $spec_item_id = I('spec_item_id/d', 0); //规格项 id

        if (!empty($spec_item_id)) {
            $id = $spec_item_id;
        } else {
            $id = M('spec_item')->where(['store_id' => STORE_ID, 'item' => $spec_item, 'spec_id' => $spec_id])->getField('id');
        }

        if (empty($id)) {
            $return_arr = array('status' => -1, 'msg' => '规格不存在');
            exit(json_encode($return_arr));
        }
        $c = M("SpecGoodsPrice")->where("store_id", STORE_ID)->where(" `key` REGEXP :id1 OR `key` REGEXP :id2 OR `key` REGEXP :id3 or `key` = :id4")->bind(['id1' => '^' . $id . '_', 'id2' => '_' . $id . '_', 'id3' => '_' . $id . '$', 'id4' => $id])->select(); // 其他商品用到这个规格不得删除
        if ($c) {
            $return_arr = array('status' => -1, 'msg' => '当前有商品在使用该规格值，不能删除');
            exit(json_encode($return_arr));
        }
        M('spec_item')->where(['id' => $id, 'store_id' => STORE_ID])->delete(); // 删除规格项
        M('spec_image')->where(['spec_image_id' => $id, 'store_id' => STORE_ID])->delete(); // 删除规格图片选项
        $return_arr = array('status' => 1, 'msg' => '删除成功!');
        exit(json_encode($return_arr));
    }

    /**
     * 商品规格列表
     */
    public function specList()
    {
        $GoodsCategoryLogic = new GoodsCategoryLogic();
        $GoodsCategoryLogic->setStore($this->storeInfo);
        $goodsCategoryLevelOne = $GoodsCategoryLogic->getStoreGoodsCategory();
        $this->assign('cat_list', $goodsCategoryLevelOne);
        return $this->fetch();
    }

    /**
     *  商品规格列表
     */
    public function ajaxSpecList()
    {
        //ob_start('ob_gzhandler'); // 页面压缩输出
        $cat_id3 = I('cat_id3/d', 0);
        $spec_id = I('spec_id/d', 0);
        $type_id = M('goods_category')->where("id", $cat_id3)->getField('type_id'); // 获取这个分类对应的类型
        if (empty($cat_id3) || empty($type_id)) exit('');

        $spec_id_arr = M('spec_type')->where("type_id", $type_id)->getField('spec_id', true); // 获取这个类型所拥有的规格
        if (empty($spec_id_arr)) exit('');

        $spec_id = $spec_id ? $spec_id : $spec_id_arr[0]; //没有传值则使用第一个

        $specList = M('spec')->where("id", "in", implode(',', $spec_id_arr))->getField('id,name,cat_id1,cat_id2,cat_id3');
        $specItemList = M('spec_item')->where(['store_id' => STORE_ID, 'spec_id' => $spec_id])->order('id')->select(); // 获取这个类型所拥有的规格
        //I('cat_id1')   && $where = "$where and cat_id1 = ".I('cat_id1') ;                       
        $this->assign('spec_id', $spec_id);
        $this->assign('specList', $specList);
        $this->assign('specItemList', $specItemList);
        return $this->fetch();
    }
    
    public function ajaxAlbumList(){
        $list = Db::name('StoreAlbum')->where(array('store_id' => STORE_ID))->order('sort asc')->select();
        $html = '';
        foreach ($list as $k => $v)
            $html .= "<option value='{$v['id']}'>{$v['album_name']}</option>";
        exit($html);
    }

    /**
     *  批量添加修改规格
     */
    public function batchAddSpecItem()
    {
        $spec_id = I('spec_id/d', 0);
        $item = I('item/a');
        if (count($item)>15) {
            $this->ajaxReturn(['status'=>0,'msg'=>'规格值不能超过15','result'=>'']);
        }
        if (in_array('',$item)) {
            $this->ajaxReturn(['status'=>0,'msg'=>'规格值不能为空','result'=>'']);
        }
        $spec_item = M('spec_item')->where(['store_id' => STORE_ID, 'spec_id' => $spec_id])->getField('id,item');
        $spec_item_id_array = [];
        foreach ($item as $k => $v) {
            $spec_item_id = 0;
            $v = trim($v);
            if (empty($v)) continue; // 值不存在 则跳过不处理
            // 如果spec_id 存在 并且 值不相等 说明值被改动过
            if (array_key_exists($k, $spec_item) && $v != $spec_item[$k]) {
                M('spec_item')->where(['id' => $k, 'store_id' => STORE_ID])->save(array('item' => $v));
                // 如果这个key不存在 并且规格项也不存在 说明 需要插入
            } elseif (!array_key_exists($k, $spec_item) && !in_array($v, $spec_item)) {
                $spec_item_id = M('spec_item')->add(array('spec_id' => $spec_id, 'item' => $v, 'store_id' => STORE_ID));
            }
            if ($spec_item_id) {
                $spec_item_id_array[] = $spec_item_id;
            }
        }
        $this->ajaxReturn(['status'=>1,'msg'=>'保存成功','result'=>$spec_item_id_array]);
    }

   
    
    /**
     * 品牌列表
     */
    public function brandList()
    {
        $keyword = I('keyword');
        $brand_where['store_id'] = STORE_ID;
        if ($keyword) {
            $brand_where['name'] = ['like', '%' . $keyword . '%'];
        }
        $count = Db::name('brand')->where($brand_where)->count();
        $Page = new Page($count, 16);
        $brandList = Db::name('brand')->where($brand_where)->order("`sort` asc")->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $show = $Page->show();
        $cat_list = M('goods_category')->where("parent_id = 0")->getField('id,name'); // 已经改成联动菜单
        $this->assign('cat_list', $cat_list);
        $this->assign('show', $show);
        $this->assign('brandList', $brandList);
        return $this->fetch('brandList');
    }

    /**
     * 添加修改编辑  商品品牌
     */
    public function addEditBrand()
    {
        $id = I('id/d', 0);
        if (IS_POST) {
            $data = input('post.');
            $brandVilidate = Loader::validate('Brand');
            if (!$brandVilidate->batch()->check($data)){
                $error_msg = '';
                foreach ($brandVilidate->getError() as $key =>$value){
                    $error_msg .= $value.'，';
                }
                $this->ajaxReturn(['status'=>-1,'msg'=>$error_msg]);
            }
			
            if ($id) {
				$data['status'] = 1;
                Db::name('brand')->update($data);
            } else {
                $data['store_id'] = STORE_ID;
                M("Brand")->insert($data);
            }
            $this->ajaxReturn(['status'=>1,'msg'=>'操作成功！！','url'=>U('Goods/brandList')]);
        }
        $GoodsCategoryLogic = new GoodsCategoryLogic();
        $GoodsCategoryLogic->setStore($this->storeInfo);
        $goodsCategoryLevelOne = $GoodsCategoryLogic->getStoreGoodsCategory();
        $this->assign('cat_list', $goodsCategoryLevelOne);
        $brand = Db::name('brand')->where(array('id' => $id, 'store_id' => STORE_ID))->find();
        $this->assign('brand', $brand);
        return $this->fetch('_brand');
    }

    /**
     * 添加/编辑相册
     */
    public function addEditAlbum()
    {
        $id = I('id/d', 0);
        if (IS_POST) {
            $data = input('post.');
            $store_id = session('store_id');
            $savePath = 'store/'.$store_id.'/goods/';
            
            if(empty($id)){
                $storeAlbum = M('StoreAlbum')->where(['album_name'=>$data['album_name']])->find();
                $storeAlbum && $this->ajaxReturn(['status'=>-1,'msg'=>'已经存在相同相册','url'=>U('Goods/albumList')]);
            }
            
            // 获取表单上传文件 例如上传了001.jpg
            $album_file = request()->file('album_img');
            // 移动到框架应用根目录/public/uploads/ 目录下
            if($album_file){
                $info = $album_file->move('public/upload/'.$savePath);
                if($info){
                    // 成功上传后 获取上传信息
                    $return_url = '/public/upload/'.$savePath.$info->getSaveName();
                    $data['album_img'] = $return_url;
                }else{
                    // 上传失败获取错误信息
                    $upload_error = $album_file->getError();
                    $this->ajaxReturn(['status'=>-1,'msg'=>'相册图上传失败:'.$upload_error,'url'=>U('Goods/albumList')]);
                }
            } 
            
            if($data['is_default'] == 1){
                M('StoreAlbum')->where(['sort'=>0])->update(['sort'=>1]);
                $data['sort'] = 0; 
            }else{
                $data['sort'] = 1;
            }
                 
            if ($id) {
                Db::name('StoreAlbum')->update($data);
            } else {
                $data['store_id'] = STORE_ID;
                M("StoreAlbum")->insert($data);
            }
            $this->ajaxReturn(['status'=>1,'msg'=>'操作成功！！','url'=>U('Goods/albumList')]);
        }
    
        $album = Db::name('StoreAlbum')->where(array('id' => $id, 'store_id' => STORE_ID))->find();
        $this->assign('album', $album);
        return $this->fetch('_album');
    }
     
    /**
     * 相册列表列表
     */
    public function albumList()
    {
        $keyword = I('keyword');
        $album_model = Db::name('StoreAlbum');
        $album_where['store_id'] = STORE_ID;
        if ($keyword) {
            $album_where['album_name'] = ['like', '%' . $keyword . '%'];
        }
        
        $allowFiles = 'png|jpg|jpeg|gif|bmp';
        $listSize = 100000;
         
         
        $count = $album_model->where($album_where)->count();
        $Page = new Page($count, 16);
        $albumList = $album_model->where($album_where)->order("id desc")->limit($Page->firstRow . ',' . $Page->listRows)->select();
       
        $show = $Page->show();
        
        /* 获取文件列表 */
        $adminLogc = new AdminLogic();
        foreach ($albumList as $k => $v){
            $path = UPLOAD_PATH.'store/'.session('store_id').'/goods_other_album/album_'.$v['id'];
            $imageList = $adminLogc->getfiles($path, $allowFiles, '' ,true);
            $albumList[$k]['count'] = count($imageList);
        }
       
        $this->assign('show', $show);
        $this->assign('albumList', $albumList);
    
        return $this->fetch('albumList');
    }
    
    /**
     * 删除品牌
     */
    public function delBrand()
    {
        $model = M("Brand");
        $id = I('id/d');
        $model->where(['id' => $id, 'store_id' => STORE_ID])->delete();
        $return_arr = array('status' => 1, 'msg' => '操作成功', 'data' => '',);
        $this->ajaxReturn($return_arr);
    }
    
    
    /**
     * 删除品牌
     */
    public function delAlbum()
    {
        $model = M("Brand");
        $id = I('id/d');
        //删除此相册下的所有图片
        $albumPath = UPLOAD_PATH.'store/'.session('store_id').'/goods/album_'.$id;
        $isSuccessful = delFile($albumPath , true);
        //删除此相册数据库记录
        $row = M("StoreAlbum")->where(['id'=>$id])->delete();
        if($row > 0){
            $this->ajaxReturn(['status'=>1 , 'msg'=>'删除成功']);
        }else{
            $this->ajaxReturn(['status'=>-1 , 'msg'=>'删除失败']);
        }
        
    }

    public function brand_save()
    {
        $data = I('post.');
        if ($data['act'] == 'del') {
            $goods_count = M('Goods')->where("brand_id", $data['id'])->count('1');
            if ($goods_count) respose(array('status' => -1, 'msg' => '此品牌有商品在用不得删除!'));
            $r = M('brand')->where('id', $data['id'])->delete();
            if ($r) {
                respose(array('status' => 1));
            } else {
                respose(array('status' => -1, 'msg' => '操作失败'));
            }
        } else {
            if (empty($data['id'])) {
                $data['store_id'] = STORE_ID;
                $r = M('brand')->add($data);
            } else {
                $r = M('brand')->where('id', $data['id'])->save($data);
            }
        }
        if ($r) {
            $this->success("操作成功", U('Store/brand_list'));
        } else {
            $this->error("操作失败", U('Store/brand_list'));
        }
    }

    /**
     * 删除商品相册图
     */
    public function del_goods_images()
    {
        $path = I('filename', '');
        $goods_images = M('goods_images')->where(array('image_url' => $path))->select();
        foreach ($goods_images as $key => $val) {
            $goods = M('goods')->where(array('goods_id' => $goods_images[$key]['goods_id']))->find();
            if ($goods['store_id'] == STORE_ID) {
                M('goods_images')->where(array('img_id' => $goods_images[$key]['img_id']))->delete();
            }
        }
    }

    /**
     * 重新申请商品审核
     */
    public function goodsUpLine()
    {
        $goods_ids = input('goods_ids');
        $res = Db::name('goods')->where('goods_id', 'in', $goods_ids)->where('store_id', STORE_ID)->update(['is_on_sale' => 0, 'goods_state' => 0]);
        if($res !== false){
            $this->success('操作成功');
        }else{
            $this->success('操作失败');
        }

    }
    
    public function pic_list(){
        
        $albumList = M('StoreAlbum')->where(['store_id' => STORE_ID])->order("sort asc")->select();
        
    	$path = UPLOAD_PATH.'store/'.session('store_id').'/goods_other_album';
    	$listSize = 14;
    	
    	$album_id = I('album_id/d' , 0);
    	
    	if($album_id < 1){//如果没指定相册, 默认显示第一个
    	    $album_id = M('StoreAlbum')->order('sort','asc')->getField('id');
    	}
    	
    	if($album_id > 0){
    	    $path .= "/album_$album_id";
    	}
    	
    	$key = empty($_GET['key']) ? '' : $_GET['key'];
    	/* 获取参数 */
    	$size = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $listSize;
    	$page = isset($_GET['p']) ? htmlspecialchars($_GET['p']) : 1;
    	$start = ($page-1)*$size;
    	$end = $start + $size;
     
    	/* 获取文件列表 */
    	$allowFiles = 'png|jpg|jpeg|gif|bmp';
    	$adminLogc = new AdminLogic();
    	$files = $adminLogc->getfiles($path, $allowFiles, $key);
    	if (!count($files)) {
    		$this->assign('result',array());
    	}else{
    		/* 获取指定范围的列表 */
    		$len = count($files);
    		$urls = array();
    		for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--){
    			$list[] = $files[$i];
    			array_push($urls, $files[$i]['url']);
    		}
    		
    		$where = implode($urls, ",");
    		$extends = M('Image_extend')->where('img_url' , 'in' , $where)->getField("img_url , cn_name , en_name" , true);
    		$returnList = array();
    		foreach ($list as $k =>$v){
    		    $extends[$v['url']]['cn_name'] && $list[$k]['name'] = $extends[$v['url']]['cn_name'];
    		}
    	 
    		/* 返回数据 */
    		$Page = new Page($len, $size);
    		$show = $Page->show();
    		$this->assign('show_page', $show);
    		$this->assign('result',$list);
    	}
    	
    	if(IS_POST){
    	    $this->ajaxReturn($albumList);
    	}
    	
    	$this->assign('album_id',$album_id);
    	$this->assign('albumList',$albumList);
    	return $this->fetch();
    }
    
     
    /**
     * 获取商品分类
     */
    public function getSellerBindCategory()
    {
        $parent_id = I('parent_id/d', '0'); // 商品分类 父id
        $rank = I('rank'); // 商品分类 父id
        $next_class = I('next_class'); // 商品分类 父id
        empty($parent_id) && $this->ajaxReturn(['status'=>1,'msg'=>'','data'=>[]]);
        if($this->storeInfo['bind_all_gc']==1){
            $list = Db::name('goods_category')->where(['parent_id'=>$parent_id])->select();
        }else{
            $where=['store_id' => STORE_ID, "$rank" => $parent_id, 'state' => 1];
            $bind_class_arr = Db::name('store_bind_class')->where($where)->group("$next_class")->getField("$next_class",true);
            $bind_class_ids = implode(',',$bind_class_arr);
            $list = Db::name('goods_category')->whereIn('id',$bind_class_ids)->select();
        }
        if ($list){
            $this->ajaxReturn(['status'=>1,'msg'=>'获取成功','data'=>$list]);
        }
        $this->ajaxReturn(['status'=>-1,'msg'=>'请先添加绑定分类','data'=>'']);
    }
}