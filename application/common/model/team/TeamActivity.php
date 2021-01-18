<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 *
 * Date: 2019-03-1
 */
namespace app\common\model\team;

use think\Model;
use think\Request;

class TeamActivity extends Model
{
    public function teamGoodsItem()
    {
        return $this->hasMany('teamGoodsItem','team_id','team_id');
    }

    public function goods(){
        return $this->hasOne('app\common\model\Goods','goods_id','goods_id')->bind(['shop_price'=>'shop_price']);
    }
    public function store(){
        return $this->hasOne('app\common\model\Store','store_id','store_id');
    }
    public function teamFound(){
        return $this->hasMany('teamFound','team_id','team_id');
    }

    public function getTeamTypeDescAttr($value, $data){
        $status = config('TEAM_TYPE');
        return $status[$data['team_type']];
    }
    public function getTimeLimitHoursAttr($value, $data){
        return $data['time_limit'] / 3600;
    }
    //分享链接
    public function getBdUrlAttr($value, $data){
        return U('Mobile/Team/info',['goods_id'=>$data['goods_id'],'team_id'=>$data['team_id']],'',true);
    }
    public function getBdPicAttr($value, $data){
        $request = Request::instance();
        return $request->domain().$data['share_img'];
    }
    public function getLotteryUrlAttr($value, $data){
        return U('Mobile/Team/lottery',['team_id'=>$data['team_id']],'',true);
    }
    public function getStatusDescAttr($value, $data){
        $status = array('审核中', '进行中', '审核失败', '管理员关闭');
        if ($data['is_lottery'] == 1) {
            return '已开奖';
        }
        return $status[$data['status']];
    }
    public function getVirtualSaleNumAttr($value, $data){
        return $data['virtual_num'] + $data['sales_sum'];
    }

    /**
     * 前台显示拼团详情
     */
    public function getFrontStatusDescAttr($value, $data){
        if($data['status'] != 1){
            return '活动未上架';
        }
        if($data['team_type'] == 2){
            if($data['is_lottery'] == 1){
                return '已开奖';
            }else{
                return '拼团中';
            }
        }else{
            return '拼团中';
        }
    }


    public function setTimeLimitAttr($value, $data){
        return $value * 3600;
    }
    public function setBonusAttr($value,$data)
    {
        return ($data['team_type'] != 1) ? 0 : $value;
    }
    public function setBuyLimitAttr($value,$data){
        return ($data['team_type'] == 2) ? 1 : $value;
    }
}
