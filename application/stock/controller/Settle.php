<?php

namespace app\stock\controller;

use app\admin\model\Order;
use app\admin\model\Product;
use app\common\model\User;
use think\Controller;
use think\Db;
use think\Config;

class Settle extends Controller
{

    public function run()
    {
        $product = Product::where(array('status' => 1))->column('price', 'id');
        $orders = Order::where(array('status' => 1, 'handletime' => ['<', (time() + 5)]))->select();

        foreach ($orders as $order) {

            $nowPrice = $product[$order['product_id']];
            $uorder = array('id' => $order['id'], 'end_price' => $nowPrice, 'open_price' => $order ['open_price'], 'status' => 3, 'user_id' => $order['user_id']);

            if ($nowPrice > 0) {

                if (($order['direction'] == 1 && $uorder ['end_price'] > $order['open_price']) || ($order['direction'] == 2 && $nowPrice < $order['open_price'])) {
                    $win = true;
                } else {
                    $win = false;
                }

                //是否平手
                $tie = false;

                //用户
                $uinfo = User::where('id', $order['user_id'])->field('risk,want_risk')->find();
                $ukong = $uinfo ['risk'];
                $uwkong = $uinfo ['want_risk'];
                //平台
                $all_kong = Config::get('site.risk');
                if ($order['pre_profit_result'] > 0 || $ukong > 0 || $uwkong > 0 || $all_kong > 0) {
                    $kong = $order['pre_profit_result'] > 0 ? $order['pre_profit_result'] : $ukong;
                    if ($order ['type'] == 1) {
                        $kong = $uwkong;
                    }
                    if ($kong == 1 || $all_kong == 1) {//赢
                        $win = true;
                    } elseif ($kong == 2 || $all_kong == 2) {//亏
                        $win = false;
                    } elseif ($kong == 3 || $all_kong == 3) {//平手
                        $win = false;
                        $tie = true;
                    }
                }

                $type = $tie == true ? 3 : ($win == true ? 1 : 2);
                $uorder['end_price'] = $this->getPrice($order['open_price'], $nowPrice, $order['direction'], $type);

                if ($tie) {
                    // 平手
                    $uorder['fact_profits'] = $order['amount'];
                    $uorder['profit_loss'] = $order['amount'];
                    $uorder['profit_result'] = 3;
                } else {
                    if ($win) {
                        $uorder['profit_loss'] = round($order['amount'] * (100 + $order['profit_ratio']) / 100, 2);
                        $uorder['fact_profits'] = round($order['amount'] * $order['profit_ratio'] / 100, 2);
                        $uorder['profit_result'] = 1;
                    } else {
                        $uorder['profit_loss'] = $order['amount'] - round($order['amount'] * $order['lose_ratio'] / 100, 2);
                        $uorder['fact_profits'] = 0 - round($order['amount'] * ($order['lose_ratio']) / 100, 2);
                        $uorder['profit_result'] = 2;
                    }
                }


                $uorder ['completetime'] = time();
                
                $log = (new \app\common\model\MoneyLog)->where('o_id', $order ['id'])->find();
                if (!$log) {
                    User::money($uorder['profit_loss'], $order ['user_id'], 4, '平仓获得收益', '', $order ['id']);
                }

                
                Order::where('id', $order ['id'])->update($uorder);
                echo $order['id'] . PHP_EOL;
            }


        }
    }


    private function getPrice($buy, $now, $style, $type)
    {
        $cha = abs($now - $buy);
        $FloatLength = $this->getFloatLength((float)$now);
        $_s_rand = rand(1, 10) / pow(10, $FloatLength - 1);
        //赢
        if ($type == 1) {
            //买涨
            if ($style == 1) {
                $price = $buy + $_s_rand;
            } elseif ($style == 2) {
                $price = $buy - $_s_rand;
            }
        } elseif ($type == 2) {
            //买涨
            if ($style == 1) {
                $price = $buy - $_s_rand;
            } elseif ($style == 2) {
                $price = $buy + $_s_rand;
            }
        } elseif ($type == 3) {
            //平手
            $price = $buy;
        }
        return $price;
    }

    public function getFloatLength($num)
    {
        $count = 0;

        $temp = explode('.', $num);

        if (sizeof($temp) > 1) {
            $decimal = end($temp);
            $count = strlen($decimal);
        }

        return $count;
    }

}