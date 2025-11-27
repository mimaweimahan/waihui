<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;

/**
 * 首页接口
 */
class Order extends Api
{
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();

        $this->model = new \app\admin\model\Order;
    }

    /**
     * 提交订单
     * @return void
     */
    public function submit()
    {

        if ($this->request->isPost()) {

            //获取用户信息
            $user = $this->auth->getUserInfo();

            //检查资金状态
            if ($user ['fund_status'] == 0) {
                $this->error(__('api.order.fund.freeze'), null, 2);
            }

            //检查是否有未完成订单
            $check = \app\admin\model\Order::where(['user_id' => $user ['id'], 'status' => 1])->find();
            if ($check) {
                $this->error(__('api.order.unsettled'));
            }

            //产品id
            $product_id = $this->request->post('product_id');

            //方向:1=买涨,2=买跌
            $direction = $this->request->post('direction');

            //秒数
            $seconds = $this->request->post('seconds');

            //购买金额
            $amount = $this->request->post('amount');

            //开仓价
            $open_price = $this->request->post('open_price');

            //收益率
            $profit_ratio = $this->request->post('profit_ratio');
            if (stripos($profit_ratio, '-')) {
                $profit_ratioArr = explode('-', $profit_ratio);
                $profit_ratio = rand($profit_ratioArr[0], $profit_ratioArr[1]);
            }

            $type = $this->request->post('type', 0);
            if ($type > 0) {
                // 我想买
                $direction = rand(1, 2);
            }

            //查询产品玩法
            $product = \app\admin\model\Product::where('id', $product_id)->find();
            $play_rule = json_decode($product ['play_rule'], true);
            $min = $max = $lose_ratio = '0.00';
            if ($play_rule) {
                foreach ($play_rule as $item) {
                    if ($item ['time'] == $seconds) {
                        $lose_ratio = $item ['lose'];
                        $min = $item ['min'];
                        $max = $item ['max'];
                        break;
                    }
                }
            }

            if (stripos($lose_ratio, '-')) {
                $lose_ratioArr = explode('-', $lose_ratio);
                $lose_ratio = rand($lose_ratioArr[0], $lose_ratioArr[1]);
            }

            if ($min > $amount) {
                $this->error(__('api.order.min') . ': ' . $min);
            }

            if ($max < $amount) {
                $this->error(__('api.order.max') . ': ' . $max);
            }

            //判断用户与是否充足
            if ($user ['money'] < $amount) {
                $this->error(__('api.user.balance'));
            }

            $order = new \app\admin\model\Order();

            $result = false;
            Db::startTrans();
            try {
                $order->order_sn = 'O' . date('YmdHis') . rand(10000, 99999);
                $order->user_id = $user ['id'];
                $order->product_id = $product_id;
                $order->type = $type;
                $order->direction = $direction;
                $order->seconds = $seconds;
                $order->amount = $amount;
                $order->open_price = $open_price;
                $order->profit_ratio = $profit_ratio;
                $order->lose_ratio = $lose_ratio;
                $order->handletime = time() + $seconds;

                $money = $amount * -1;

                \app\common\model\User::money($money, $user ['id'], 3, '下单扣除余额');

                $result = $order->save();

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

            if ($result) {
                $this->success(__('api.order.success'), ['order_id' => $order->id]);
            }
            $this->error(__('api.order.error'));

        }

        $this->error(__('api.common.error'));
    }

    /**
     *获取订单列表
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderList()
    {
        $user = $this->auth->getUserinfo();
        $page = $this->request->get('page');
        $status = $this->request->get('status');

        $where ['user_id'] = $user ['id'];

        if ($status) {
            $where ['status'] = $status;
        }

        $list = $this->model
            ->where($where)
            ->page($page, 10)
            ->field('id,order_sn,product_id,direction,amount,open_price,end_price,profit_result,seconds,fact_profits,status,createtime,handletime')
            ->order('id', 'desc')
            ->select();

        foreach ($list as &$item) {
            $item ['product_title'] = \app\admin\model\Product::where('id', $item ['product_id'])->value('title');
            $item ['createtime_text'] = date('Y-m-d H:i:s', $item ['createtime']);
            $item ['handletime_text'] = date('Y-m-d H:i:s', $item ['handletime']);
            $item ['countdown'] = 0;
            if ($item ['status'] == 1) {
                $item ['countdown'] = $item ['handletime'] - time();
            }
        }

        $this->success(__('api.common.success'), $list);
    }

    /**
     * 获取订单信息
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderInfo()
    {
        $user = $this->auth->getUserinfo();
        $order_id = $this->request->get('order_id');

        $orderInfo = $this->model
            ->where(['id' => $order_id, 'user_id' => $user ['id']])
            ->field('id,product_id,order_sn,direction,amount,open_price,end_price,profit_ratio,profit_result,fact_profits,status,createtime,handletime')
            ->find();

        $orderInfo ['product_title'] = \app\admin\model\Product::where('id', $orderInfo ['product_id'])->value('title');
        $orderInfo ['createtime_text'] = date('Y-m-d H:i:s', $orderInfo ['createtime']);
        $orderInfo ['handletime_text'] = date('Y-m-d H:i:s', $orderInfo ['handletime']);
        $orderInfo ['profit_loss'] = ($orderInfo ['amount'] * $orderInfo ['profit_ratio']) / 100;
        $orderInfo ['countdown'] = $orderInfo ['handletime'] - time();

        $this->success(__('api.common.success'), $orderInfo);
    }
}
