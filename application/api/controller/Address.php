<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use think\Exception;

/**
 * 用户地址接口
 */
class Address extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();

        $this->model = new \app\admin\model\Recharge;
    }

    /**
     * 提交订单
     * @return void
     */
    public function submit()
    {

        if ($this->request->isPost()) {

            $user = $this->auth->getUserinfo();

            //充值金额
            $amount = $this->request->post('amount');
            if (empty($amount)) {
                $this->error(__('Please enter the recharge amount'));
            }

            //充值方式
            $type = $this->request->post('type', 2);

            $recharge = new \app\admin\model\Recharge();
            $result = false;
            Db::startTrans();
            try {
                $recharge->user_id = $user['id'];
                $recharge->amount = $amount;
                $recharge->type = $type;

                $result = $recharge->save();
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

            if ($result) {
                $this->success('success');
            }
            $this->error();

        }

        $this->error('error');
    }

    /**
     *获取充值地址列表
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRechargeList()
    {
        $user = $this->auth->getUserinfo();
        $page = $this->request->get('page');

        $where = ['user_id' => $user ['id']];

        $list = $this->model
            ->where($where)
            ->page($page, 10)
            ->order('id', 'desc')
            ->select();

        foreach ($list as &$item) {
            $item ['createtime_text'] = date('Y-m-d H:i:s', $item ['createtime']);
        }

        $this->success('success', $list);
    }
}
