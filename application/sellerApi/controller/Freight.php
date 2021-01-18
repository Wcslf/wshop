<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * 运费模板管理
 * Date: 2017-11-14
 */

namespace app\sellerApi\controller;

use app\common\model\FreightTemplate;
use app\seller\logic\FreightLogic;
use think\Db;
use think\Page;

class Freight extends Base
{

    /**
     * 运费模板列表
     */
    public function index()
    {
        $FreightTemplate = new FreightTemplate();
        $count = $FreightTemplate->where('store_id', STORE_ID)->count();
        $Page = new Page($count, 10);
        $template_list = $FreightTemplate->append(['type_desc'])->with('freightConfig')
            ->where('store_id', STORE_ID)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        if($template_list ){
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' =>$template_list]);
        }else{
            $this->ajaxReturn(['status' => 0, 'msg' => '获取失败', 'result' =>[]]);
        }
    }

    /**
     * 模板详情
     */
    public function info()
    {
        $template_id = input('template_id');
        if ($template_id) {
            $FreightTemplate = new FreightTemplate();
            $freightTemplate = $FreightTemplate->with('freightConfig,freightConfig.freightRegion,freightConfig.freightRegion.region')->where(['template_id' => $template_id, 'store_id' => STORE_ID])->find();
            if (empty($freightTemplate)) {
                $this->ajaxReturn(['status' => 0, 'msg' => '非法操作']);
            }
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' =>$freightTemplate]);
        }else{
            $this->ajaxReturn(['status' => 0, 'msg' => '参数错误']);
        }
    }

    /**
     * 保存运费模板
     */
    public function save()
    {
        $FreightLogic = new FreightLogic();
        $res = $FreightLogic->addEditFreightTemplate();
        $this->ajaxReturn($res);
    }

    /**
     * 删除运费模板
     * @throws \think\Exception
     */
    public function delete()
    {
        $template_id = I('template_id');
        $action = I('action');
        if (empty($template_id)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '参数错误', 'result' => '']);
        }
        if ($action != 'confirm') {
            $goods_count = Db::name('goods')->where(['template_id' => $template_id, 'store_id' => STORE_ID])->count();
            if ($goods_count > 0) {
                $this->ajaxReturn(['status' => -1, 'msg' => '已有' . $goods_count . '种商品使用该运费模板，确定删除该模板吗？继续删除将把使用该运费模板的商品设置成包邮。', 'result' => '']);
            }
        }
        Db::name('goods')->where(['template_id' => $template_id, 'store_id' => STORE_ID])->update(['template_id' => 0, 'is_free_shipping' => 1]);
        Db::name('freight_region')->where(['template_id' => $template_id, 'store_id' => STORE_ID])->delete();
        Db::name('freight_config')->where(['template_id' => $template_id, 'store_id' => STORE_ID])->delete();
        $delete = Db::name('freight_template')->where(['template_id' => $template_id, 'store_id' => STORE_ID])->delete();
        if ($delete !== false) {
            $this->ajaxReturn(['status' => 1, 'msg' => '删除成功', 'result' => '']);
        } else {
            $this->ajaxReturn(['status' => 0, 'msg' => '删除失败', 'result' => '']);
        }
    }
}