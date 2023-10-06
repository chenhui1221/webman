<?php

namespace plugin\ai\app\service;

use plugin\admin\app\model\Option;

class Base
{
    /**
     * options表对应的name字段
     */
    const OPTION_NAME = 'plugin_ai.base';

    /**
     * 获取配置
     *
     * @return array|mixed
     */
    public static function getSetting()
    {
        $item = Option::where('name', static::OPTION_NAME)->value('value');
        return $item ? json_decode($item, true) : null;
    }

    /**
     * 保存配置
     *
     * @param $data
     * @return void
     */
    public static function saveSetting($data)
    {
        if (!$item = Option::where('name', static::OPTION_NAME)->first()) {
            $item = new Option;
        }
        $item->name = static::OPTION_NAME;
        $item->value = json_encode($data, JSON_UNESCAPED_UNICODE);
        $item->save();
    }
}