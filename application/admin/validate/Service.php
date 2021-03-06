<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 */

namespace app\admin\validate;

use think\Validate;

class Service extends Validate
{
    //验证规则
    protected $rule = [
        'expose_type_name'   => 'require|notEmpty|unique:expose_type',
        'expose_type_desc'   => 'require|notEmpty',
    ];
    
    //错误消息
    protected $message = [
        'expose_type_name'  => '举报类型不能为空',
        'expose_type_name.unique' => '举报类型已存在',
        'expose_type_desc'  => '举报类型描述不能为空',
    ];
    
    //验证场景
    protected $scene = [
        'add'  => ['expose_type_name', 'expose_type_desc'],
    ];
    
    protected function notEmpty($value)
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        if (empty($value)) {
            return false;
        }
        return true;
    }
}
