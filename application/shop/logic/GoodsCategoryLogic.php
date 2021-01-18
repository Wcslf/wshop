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

namespace app\shop\logic;

use think\Model;
use think\Db;

/**
 * 分类逻辑定义
 * Class CatsLogic
 * @package Home\Logic
 */
class GoodsCategoryLogic extends Model
{
    protected $store;

    public function setStore($store){
        $this->store = $store;
    }

    /**
     * 获取店铺的商品分类
     * @param int $parent_id
     * @return array|false|\PDOStatement|string|\think\Collection
     */
    public function getStoreGoodsCategory($parent_id = 0){
        $goods_category_list = Db::name('goods_category')->where(array('parent_id' => $parent_id))->order('sort_order desc')->select();
        if($this->store['bind_all_gc'] == 0){
            $bind_class_where = ['store_id' => $this->store['store_id'], 'state' => 1];
            if($goods_category_list[0]['level'] == 1){
                $class_id = Db::name('store_bind_class')->where($bind_class_where)->getField('class_1', true);
            }elseif($goods_category_list[0]['level'] == 2){
                $class_id = Db::name('store_bind_class')->where($bind_class_where)->getField('class_2', true);
            }else{
                $class_id = Db::name('store_bind_class')->where($bind_class_where)->getField('class_3', true);
            }
            if($class_id){
                $store_category_list = [];
                foreach ($goods_category_list as $categoryKey => $categoryItem) {
                    // 如果是某个店铺登录的, 那么这个店铺只能看到自己申请的分类,其余的看不到
                    if (in_array($categoryItem['id'], $class_id)){
                        $store_category_list[] = $goods_category_list[$categoryKey];
                    }
                }
                return $store_category_list;
            }
        }
        return $goods_category_list;
    }
}