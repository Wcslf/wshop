<?php

/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * Author: 二月鸟飞
 * Date: 2019-03-1
 */

namespace app\admin\logic;
use think\Model;
use think\Db;

class ServiceLogic extends Model
{
    /**
     * 获取筛选框搜索条件对应的ID
     * 如store_name就去store表获取store_id
     * @param $type
     * @param $qv
     * @return mixed
     */
    public function getConditionId($type,$qv){
            $where["$type"] = array('like','%'.$qv.'%');
            $model = explode('_',$type);
            $column = $model[0].'_id';
            if($type !='order_sn'){
                $id_arr=Db::name("$model[0]")->where($where)->getField("$column",true);
                $data["$column"]=['in',$id_arr];
            }else{
                $data["$type"] = array('like','%'.$qv.'%');
            }
        return $data;
    }

}