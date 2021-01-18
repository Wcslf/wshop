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

namespace app\seller\controller;

use app\seller\logic\FreightLogic;
use app\common\model\FreightTemplate;
use think\Db;
use think\Page;

class Freight extends Base
{

    public function index()
    {
        $FreightTemplate = new FreightTemplate();
        $count = $FreightTemplate->where('store_id', STORE_ID)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $template_list = $FreightTemplate->append(['type_desc'])->with('freightConfig')->where('store_id', STORE_ID)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('page', $show);
        $this->assign('template_list', $template_list);
        return $this->fetch();
    }

    public function info()
    {
        $template_id = input('template_id');
        if ($template_id) {
            $FreightTemplate = new FreightTemplate();
            $freightTemplate = $FreightTemplate->with('freightConfig')->where(['template_id' => $template_id, 'store_id' => STORE_ID])->find();
            if (empty($freightTemplate)) {
                $this->error('非法操作');
            }
            $this->assign('freightTemplate', $freightTemplate);
        }
        return $this->fetch();
    }

    /**
     *  保存运费模板
     * @throws \think\Exception
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
        $template_id = input('template_id');
        $action = input('action');
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


    public function area()
    {
        $province_list = Db::name('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        $this->assign('province_list', $province_list);
        return $this->fetch();
    }


}