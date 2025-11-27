<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Cache;
use think\Config;

/**
 * 首页接口
 */
class Product extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();

        $this->model = new \app\admin\model\Product;

        setProduct();
    }

    /**
     * 获取产品列表
     */
    public function getProductList()
    {
        $list = $this->model->where('status', 1)->order('weight', 'desc')->select();
        if ($list) {
            $list = collection($list)->toArray();
        }
        foreach ($list as &$item) {
            $item ['image'] = cdnurl($item ['image'], true);
            
            //获取K线数据
            $klineData = $this->getKlineData($item ['code'], '1min');

            $item['chartData'] = [];
            if ($klineData) {

                $klineData = array_slice($klineData, -100);
                $item['chartData'] = [
                    'series' => [['name' => 'area', 'data' => array_map(function ($item) {
                        return $item['close'];
                    }, $klineData)]],
                    'categories' =>
                        array_map(function ($item) {
                            return $item['time'];
                        }, $klineData)
                ];
            }
        }

        $this->success(__('api.common.success'), $list);
    }

    /**
     * 获取产品信息
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getProductInfo()
    {
        $symbol = $this->request->get('symbol');
        $product = $this->model->where('code', $symbol)->find();
        if (empty($product)) {
            $this->error(__('api.product.error'));
        }

        $play_rule = json_decode($product['play_rule'], true);
        $timeList = array();
        foreach ($play_rule as $key => $val) {
            $time_str = $val['time'] . 's';
            $timeList[] = array(
                'seconds' => $val['time'],
                'seconds_desc' => $time_str,
                'profit_ratio' => $val['win'],
                'lose_ratio' => $val['lose'],
                'min' => $val['min'],
                'max' => $val['max'],
            );
        }

        $data = [];
        $data['timeList'] = $timeList;
        $data['id'] = $product['id'];
        $data['symbol'] = $product['symbol'];
        $data['min'] = $product['min'];
        $data['max'] = $product['max'];
        $data['price'] = $product['price'];
        $data['open_price'] = $product['open_price'];
        $data['price_high'] = $product['price_high'];
        $data['price_low'] = $product['price_low'];
        $data['price_zf'] = $product['price_zf'];
        $data['vol'] = $product['vol'];

        $this->success(__('api.common.success'), $data);
    }

     /**
     * 获取产品k线
     * @return void
     */
    public function getKline()
    {
        //分钟数
        $period = $this->request->get('period');

        //币种
        $symbol = $this->request->get('symbol');
        if ($period == '1h' || $period == '60min') {
            $period = '1hour';
        }

        $data = $this->getKlineData($symbol, $period);

        $this->success(__('api.common.success'), $data);
    }


    /**
     * 获取产品k线数据
     * @return array
     */
    public function getKlineData($symbol, $period)
    {

        $key = $symbol . '_stock_' . $period;
        $data = Cache::store('redis')->get($key);
        if (empty($data)) {
            return [];
        }
        $data = array_values(json_decode($data, true));

        if (count($data) > 0) {
            $ind = count($data) - 1;
            $pro = $data[$ind];
            $close = rtrim(rtrim($pro['close'], '0'), '.');
            $randomDigit = rand(0, 9); // 随机生成一个 0 到 9 的数字
            $close = substr($close, 0, -1) . $randomDigit;
            $pro['close'] = $close;

            if ($close > floatval($pro['high'])) {
                $pro['high'] = $close;
            }
            if ($close < floatval($pro['low'])) {
                $pro['low'] = $close;
            }
            $data[$ind] = $pro;
        }

        return $data;
    }
}
