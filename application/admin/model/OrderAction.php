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

namespace app\admin\model;

use think\Model;
use think\Db;

/**
 * Class UserModel
 * @package Admin\Model
 */
class OrderAction extends Model
{
    protected $table='';

    //自定义初始化
    protected function initialize()
    {
        parent::initialize();
        $select_year = select_year(); // 查询 三个月,今年内,2016年等....订单
        $prefix = C('database.prefix');  //获取表前缀
        $this->table = $prefix.'order_action'.$select_year;
    }

    public function users(){
        $this->hasOne('users','action_user','user_id')->field('nickname,user_id');
    }
}