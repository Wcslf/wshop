<?php
/**
 * tpshop
 * ============================================================================

 * 技术交流QQ3327926505
 * ----------------------------------------------------------------------------

 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * 评论咨询投诉管理
 * @author soubao 当燃
 * @Date: 2016-06-20
 */

namespace app\sellerApi\controller;

use think\AjaxPage;
use think\Db;

class Comment extends Base
{
    /**
     * 评论列表
     */
    public function ajaxindex()
    {
        $CommentModel = new \app\common\model\Comment();
        $where['c.parent_id'] = 0;
        $where['c.store_id'] = STORE_ID;
        $key_word = I('key_word', '', 'trim');
        $rank = I('rank/d','');  //评分等级 1差，2中，3好评
        switch ($rank){
            case 1:$where['c.goods_rank'] = ['elt',2];break;
            case 2:$where['c.goods_rank'] = 3;break;
            case 3:$where['c.goods_rank'] = ['egt',4];break;
        }
        if(!empty($key_word)) {
            $where['c.content|c.order_sn'] = array('like', '%' . $key_word . '%');
        }
        $count = $CommentModel->alias('c')->where($where)->count();
        $Page = new AjaxPage($count, 16);
        //是否从缓存中读取Page
        if (session('is_back') == 1) {
            $Page = getPageFromCache();
            delIsBack();
        }
        $comment_list = $CommentModel
            ->alias('c')
            ->field('c.*,og.goods_name,og.final_price')
            ->join('order_goods og', 'og.rec_id = c.rec_id', 'LEFT')
            ->where($where)->order('add_time DESC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        cachePage($Page);
        $this->ajaxReturn(['status' => 1, 'msg' => $key_word, 'result' => $comment_list]);
    }

    /**
     * 评论详情
     */
    public function detail()
    {
        $id = I('comment_id/d');
        $CommentModel = new \app\common\model\Comment();
        $UsersModel = new \app\common\model\Users();
        $data = $comment_detail = $CommentModel::get(['comment_id' => $id, 'store_id' => STORE_ID]);
        empty($comment_detail) && $this->ajaxReturn(['status' => -1, 'msg' => '该评论不存在！！']);
        $User = $UsersModel::get(['user_id'=>$comment_detail['user_id']]);  //评论者
        $user_info  = $User->visible(['nickname','email'])->toArray();  //只显示部分字段
        $data['nickname'] = $user_info['nickname'];
        $data['email']    = $user_info['email'];
        $data['reply']    = $CommentModel::all(['parent_id' => $id]); // 卖家评论回复列表
       // $data['reply_list'] = Db::name('reply')->where(['comment_id' => $id, 'deleted' => 0])->select(); // 会员评论回复列表
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $data]);
    }

    /**
     * 回复评论
     */
    public function  replyComment(){
        if (IS_POST) {
            $add['parent_id'] = I('parent_id/d',0);
            $add['content'] = trim(I('content'));
            $add['goods_id'] = I('goods_id/d',0);
            $add['add_time'] = time();
            $add['username'] = '卖家';
            $add['is_show'] = 1;
            $add['store_id'] = STORE_ID;
            empty($add['parent_id']) && $this->ajaxReturn(['status' => -1, 'msg' => '非法操作！！']);
            empty($add['content']) && $this->ajaxReturn(['status' => -1, 'msg' => '请填写回复内容']);
            $row = M('comment')->add($add);
            $row === false && $this->ajaxReturn(['status' => -1, 'msg' =>'添加失败']);
            $this->ajaxReturn(['status' => 1, 'msg' =>'添加成功']);
        }else{
            $this->ajaxReturn(['status' => 0, 'msg' => '请求方式错误！！', 'result' => '']);
        }
    }
}