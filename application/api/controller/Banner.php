<?php

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 首页接口
 */
class Banner extends Api
{
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();

        $this->model = new \app\admin\model\Banner;
    }

    /**
     * 获取产品列表
     */
    public function getBannerList()
    {
        $list = $this->model->where('status',1)->order('weight', 'desc')->select();
        foreach ($list as &$item) {
            $item ['image'] = cdnurl($item ['image'], true);
        }

        $this->success(__('api.common.success'), $list);
    }


}
