<?php

namespace app\admin\model;

use think\Model;


class Order extends Model
{

    // 表名
    protected $name = 'order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'direction_text',
        'handletime_text',
        'completetime_text'
    ];


    public function getDirectionList()
    {
        return ['1' => __('Direction 1'), '2' => __('Direction 2')];
    }


    public function getDirectionTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['direction']) ? $data['direction'] : '');
        $list = $this->getDirectionList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getHandletimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['handletime']) ? $data['handletime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getCompletetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['completetime']) ? $data['completetime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setHandletimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setCompletetimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    public function product()
    {
        return $this->belongsTo('Product', 'product_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
