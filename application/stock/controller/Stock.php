<?php

namespace app\stock\controller;

use app\admin\model\Product;
use think\Cache;
use think\Controller;

class Stock extends Controller
{

    //从redis里取出交易对
    public function run()
    {

        if (Cache::store('redis')->has('updateProduct')) {
            $cache = Cache::store('redis')->get('updateProduct');

            $cache = json_decode($cache, true);

            //更新交易对信息
            foreach ($cache as $key => $item) {
                //判断交易对是否存在
                $check = Product::where('code', $key)->value('id');
                if (!$check) {
                    continue;
                }
                unset($item['price_update'], $item['time']);

                $zf = ($item ['price'] - $item ['open_price']) * 100 / $item ['open_price'];
                $zf = bcmul($zf, 1, 2);
                $item ['price_zf'] = $zf;
                Product::where('code', $key)->update($item);
            }
        }
    }

}