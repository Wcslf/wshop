<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * Date: 2015-09-14
 */

namespace app\seller\logic;

use app\common\model\FreightTemplate;
use think\Loader;
use think\Model;
use think\Db;

class FreightLogic extends Model
{

    /**
     * 添加编辑模板
     */
    public function addEditFreightTemplate(){
        $template_id = input('template_id/d');
        $template_name = input('template_name/s');
        $type = input('type/d');
        $is_enable_default = input('is_enable_default/d');
        $config_list = input('config_list/a', []);
        $data = input('post.');
        $data['store_id'] = STORE_ID;
        $freightTemplateValidate = Loader::validate('FreightTemplate');
        if (!$freightTemplateValidate->batch()->check($data)) {  //验证数据
            return ['status' => 0, 'msg' => '操作失败', 'result' => $freightTemplateValidate->getError()];
        }
        if (empty($template_id)) {
            //添加模板
            $freightTemplate = new FreightTemplate();
        } else {
            //更新模板
            $freightTemplate = FreightTemplate::get(['template_id' => $template_id, 'store_id' => STORE_ID]);
        }
        $freightTemplate['template_name'] = $template_name;
        $freightTemplate['type'] = $type;
        $freightTemplate['store_id'] = STORE_ID;
        $freightTemplate['is_enable_default'] = $is_enable_default;
        $freightTemplate->save();
        $config_list_count = count($config_list);
        $config_id_arr = Db::name('freight_config')->where(['template_id' => $template_id, 'store_id' => STORE_ID])->getField('config_id', true);
        $update_config_id_arr = [];
        if ($config_list_count > 0) {
            for ($i = 0; $i < $config_list_count; $i++) {
                $freight_config_data = [
                    'first_unit' => $config_list[$i]['first_unit'],
                    'first_money' => $config_list[$i]['first_money'],
                    'continue_unit' => $config_list[$i]['continue_unit'],
                    'continue_money' => $config_list[$i]['continue_money'],
                    'template_id' => $freightTemplate['template_id'],
                    'is_default' => $config_list[$i]['is_default'],
                    'store_id' => STORE_ID,
                ];
                if (empty($config_list[$i]['config_id'])) {
                    //新增配送区域
                    $config_id = Db::name('freight_config')->insertGetId($freight_config_data);
                    if(!empty($config_list[$i]['area_ids'])){
                        $area_id_arr = explode(',', $config_list[$i]['area_ids']);
                        if ($config_id !== false) {
                            foreach ($area_id_arr as $areaKey => $areaVal) {
                                Db::name('freight_region')->add(['template_id'=>$freightTemplate['template_id'],'config_id' => $config_id, 'region_id' => $areaVal, 'store_id' => STORE_ID]);
                            }
                        }
                    }
                } else {
                    //更新配送区域
                    array_push($update_config_id_arr, $config_list[$i]['config_id']);
                    $config_result = Db::name('freight_config')->where(['config_id' => $config_list[$i]['config_id'], 'store_id' => STORE_ID])->save($freight_config_data);
                    if ($config_result !== false) {
                        Db::name('freight_region')->where(['config_id' => $config_list[$i]['config_id'], 'store_id' => STORE_ID])->delete();
                        if(!empty($config_list[$i]['area_ids'])){
                            $area_id_arr = explode(',', $config_list[$i]['area_ids']);
                            foreach ($area_id_arr as $areaKey => $areaVal) {
                                Db::name('freight_region')->add(['template_id'=>$freightTemplate['template_id'],'config_id' => $config_list[$i]['config_id'], 'region_id' => $areaVal, 'store_id' => STORE_ID]);
                            }
                        }
                    }
                }
            }
        }
        $delete_config_id_arr = array_diff($config_id_arr, $update_config_id_arr);
        if (count($delete_config_id_arr) > 0) {
            Db::name('freight_region')->where(['config_id' => ['IN', $delete_config_id_arr], 'store_id' => STORE_ID])->delete();
            Db::name('freight_config')->where(['config_id' => ['IN', $delete_config_id_arr], 'store_id' => STORE_ID])->delete();
        }
        $this->checkFreightTemplate($freightTemplate->template_id);
        return ['status' => 1, 'msg' => '保存成功', 'result' => ''];
    }

    /**
     * 检查模板，如果模板下没有配送区域配置，就删除该模板
     * @param $template_id
     */
    private function checkFreightTemplate($template_id)
    {
        $freight_config = Db::name('freight_config')->where(['store_id' => STORE_ID, 'template_id' => $template_id])->find();
        if (empty($freight_config)) {
            Db::name('freight_template')->where('template_id', $template_id)->delete();
        }
    }
}