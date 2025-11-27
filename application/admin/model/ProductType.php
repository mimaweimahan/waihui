<?php

namespace app\admin\model;

use think\Model;


class ProductType extends Model
{

    // 表名
    protected $name = 'product_type';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'cratetime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'createtime_text',
        'updatetime_text'
    ];

    public function getCreatetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['createtime']) ? $data['createtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getUpdatetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['updatetime']) ? $data['updatetime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreatetimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setUpdatetimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }
}
