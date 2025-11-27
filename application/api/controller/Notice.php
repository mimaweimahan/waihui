<?php

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 首页接口
 */
class Notice extends Api
{
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();

        $this->model = new \app\admin\model\Notice;
    }

    /**
     * 获取资讯列表
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getNoticeList()
    {
        $page = $this->request->get('page', 1);

        $list = $this->model->where('status', 1)->order('createtime', 'desc')->page($page, 10)->select();

        foreach ($list as &$item) {
            $item ['createtime_text'] = date('Y-m-d H:i:s', $item ['createtime']);
        }

        $this->success(__('api.common.success'), $list);
    }

    /**
     * 获取资讯详情
     * @return void
     * @throws \think\exception\DbException
     */
    public function getNoticeInfo()
    {
        $id = $this->request->get('id');

        $info = $this->model->get($id);

        $info ['createtime_text'] = date('Y-m-d H:I:s', $info ['createtime']);

        $this->success(__('api.common.success'), $info);
    }

    public function saveNews()
    {
        $title = $this->request->post('title');
        $label = $this->request->post('label');
        $image = $this->request->post('image');
        $content = $this->request->post('content');

        $newsModel = new \app\admin\model\News();
        $newsModel->title = $title;
        $newsModel->label = $label;
        $newsModel->image = $image;
        $newsModel->content = $content;

        $result = $newsModel->save();
        if ($result) {
            $this->success(__('api.common.success'));
        }

        $this->error(__('api.common.error'));
    }
}
