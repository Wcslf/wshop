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
namespace app\admin\validate;
use think\Validate;
class GoodsCategory extends Validate {
    //验证规则
    protected $rule = [
        'name'  => 'require',
        'mobile_name'  => 'require',
        'sort_order'   => 'number'
    ];

    //错误消息
    protected $message = [
        'name.require'                  => '分类名称必须填写', 
        'mobile_name.require'           => '手机分类名称必须填写',
        'sort_order.number'             => '排序必须为数字',
    ];

    /**
     * 验证分类名
     * @param $value
     * @param $rule
     * @param $data
     * @return bool
     */
    protected function checkName($value,$rule,$data){
        if(empty($data['id'])){
            if(M('goods_category')->where(['name'=>$value])->count()){
                return false;
            }
        }else{
            if(M('goods_category')->whereNotIn('id',$data['id'],'AND')->where(['name'=>$value])->count()){
                return false;
            }
        }
        return true;
    }
    /**
     * 验证手机分类名
     * @param $value
     * @param $rule
     * @param $data
     * @return bool
     */
    protected function checkMoblieName($value,$rule,$data){
        if(empty($data['id'])){
            if(M('goods_category')->where(['mobile_name'=>$value])->count()){
                return false;
            }
        }else{
            if(M('goods_category')->whereNotIn('id',$data['id'],'AND')->where(['mobile_name'=>$value])->count()){
                return false;
            }
        }
        return true;
    }
}
