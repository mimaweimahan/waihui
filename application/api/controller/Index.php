<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     *
     */
    public function index()
    {
        $this->success('请求成功');
    }

    public function getConfig()
    {
        $keyword = $this->request->get('keyword');

        $keyword = explode(',', $keyword);

        $site = config('site');

        $data = [];
        foreach ($keyword as $item) {
            if (!isset($site [$item])) {
                $data [$item] = '';
                continue;
            }

            $data [$item] = $site [$item];

        }

        $this->success(__('api.common.success'), $data);
    }
}
