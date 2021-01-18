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

/**
 * Description of Article
 *
 * @author Administrator
 */
class Topic extends Validate
{
    //验证规则
    protected $rule = [
        'topic_title'  => 'require|checkEmpty',
    ];
    
    //错误消息
    protected $message = [
        'topic_title'    => '标题不能为空',
    ];
    
    //验证场景
    protected $scene = [
        'add'  => ['topic_title'],
        'edit' => ['topic_title'],
        'del'  => ['']
    ];
    
    protected function checkEmpty($value)
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
