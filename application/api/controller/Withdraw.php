<?php

namespace app\api\controller;

use app\admin\model\user\Address;
use app\admin\model\user\Bank;
use app\common\controller\Api;
use think\Config;
use think\Db;
use think\Exception;

/**
 * 提现接口
 */
class Withdraw extends Api
{
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();

        $this->model = new \app\admin\model\Withdraw;
    }

    /**
     * 提交订单
     * @return void
     */
    public function submit()
    {

        if ($this->request->isPost()) {

            $user = $this->auth->getUser();

            if ($user ['safe_status'] == 0) {
                $this->error(__('api.user.safe0'), [], 402);
            }

            if ($user ['safe_status'] == 1) {
                $this->error(__('api.user.safe1'));
            }
            
            //检查资金状态
            if ($user ['fund_status'] == 0) {
                $this->error(__('api.order.fund.freeze'), null, 2);
            }
            
            //获取提现次数
            $withdraw_number = Config::get('site.withdraw_number');
            
            //查询今日提现次数
            $check = \app\admin\model\Withdraw::where('user_id', $this->auth->id)->whereTime('createtime', 'today')->count();
            if ($check >= $withdraw_number) {
                 $this->error(__('api.withdraw.number', $withdraw_number));
            }
            
            $time = time();
            
            // 获取提现时间
            $withdraw_time = Config::get('site.withdraw_time');
            if ($withdraw_time) {
                $withdraw_time = explode('-', $withdraw_time);
                // 判断是否跨天
                if (substr($withdraw_time[0], 0, 2) > substr($withdraw_time[1], 0, 2)) {
                    // 提现时间跨越到第二天
    
                    // 计算当天提现开始时间戳
                    $start = strtotime(date('Y-m-d ') . $withdraw_time[0]);
                    // 计算第二天提现结束时间戳
                    $end = strtotime(date('Y-m-d ', strtotime('+1 day')) . $withdraw_time[1]);
    
                    // 如果当前时间提现区间内
                    if ($time >= $start || $time >= $end) {
                       
                    } else{
                         $this->error(__('api.withdraw.time', $withdraw_time[0] . '-' . $withdraw_time[1]));
                    }
                } else {
                    // 非跨天提现
                    $start = strtotime(date('Y-m-d ') . $withdraw_time[0]);
                    $end = strtotime(date('Y-m-d ') . $withdraw_time[1]);
                    // 如果当前时间在提现区间内
                    if ($time >= $start && $time <= $end) {
                       
                    } else{
                         $this->error(__('api.withdraw.time', $withdraw_time[0] . '-' . $withdraw_time[1]));
                    }
                }
            }
            
            

            //校验支付密码
            $pay_password = $this->request->post('pay_password');
            if (empty($pay_password)) {
                $this->error(__('api.withdraw.pay_password'));
            }

            if ($this->auth->getEncryptPassword($pay_password, $user ['pay_salt']) != $user ['pay_password']) {
                $this->error(__('api.user.password.error'));
            }

            //提现金额
            $amount = $this->request->post('amount');
            if (empty($amount)) {
                $this->error(__('api.withdraw.amount'));
            }
            
            if ($user ['money'] <= 0) {
                $this->error(__('api.user.balance'));
            }

            if ($user ['money'] < $amount) {
                $this->error(__('api.user.balance'));
            }

            //提现方式
            $type = $this->request->post('type', 1);

            //地址id
            $address_id = $this->request->post('address_id', 0);

            //银行卡地址
            $bank_id = $this->request->post('bank_id', 0);

            $pay_info = [];

            if ($type == 1) {
                if (empty($bank_id)) {
                    $this->error(__('api.withdraw.select.bank'));
                }
                $pay_info = Bank::where('id', $bank_id)->field('username,bank_name,account')->find();
            } else if ($type == 2) {
                if (empty($address_id)) {
                    $this->error(__('api.withdraw.select.address'));
                }
                $pay_info = Address::where('id', $address_id)->field('name,address')->find();
            }

            $fee = Config::get('site.withdrawal_fee');

            $withdraw = new \app\admin\model\Withdraw();
            $withdrawal_fee = bcdiv($fee * $amount, 100, 8);
            $result = false;
            Db::startTrans();
            try {
                $withdraw->user_id = $user['id'];
                $withdraw->bank_id = $bank_id;
                $withdraw->address_id = $address_id;
                $withdraw->amount = $amount;
                $withdraw->amount_received = bcsub($amount, $withdrawal_fee, 8);
                $withdraw->type = $type;
                $withdraw->fee = $fee;
                $withdraw->withdrawal_fee = $withdrawal_fee;
                $withdraw->pay_info = json_encode($pay_info, JSON_UNESCAPED_UNICODE);
                $result = $withdraw->save();
                $money = $amount * -1;
                \app\common\model\User::money($money, $user['id'], 2, '提现扣除余额' . $money,null,$withdraw->id);

                
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

            if ($result) {
                $this->success(__('api.withdraw.success'));
            }
            $this->error(__('api.withdraw.error'));

        }

        $this->error(__('api.common.error'));
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

        $this->success(__('api.common.success'), $list);
    }
}
