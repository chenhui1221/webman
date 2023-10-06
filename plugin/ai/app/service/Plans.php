<?php

namespace plugin\ai\app\service;

use plugin\admin\app\model\Option;

class Plans
{
    /**
     * options表对应的name字段
     */
    const OPTION_NAME = 'plugin_ai.plans';

    /**获取配置
     *
     * @return array|mixed
     */
    public static function get()
    {
        $plans = Option::where('name', static::OPTION_NAME)->value('value');
        $plans = $plans ? json_decode($plans, true) : [];
        if (!$plans) {
            $plans = [
                ['plan' => 1, 'price' => 19, 'months' => 1, 'name' => '月度会员'],
                ['plan' => 2, 'price' => 49, 'months' => 3, 'name' => '季度会员'],
                ['plan' => 3, 'price' => 168, 'months' => 12, 'name' => '年度会员'],
            ];
            static::save($plans);
        }
        return array_column($plans, null, 'plan');
    }

    /**
     * 保存配置
     *
     * @param $data
     * @return void
     */
    public static function save($data)
    {
        if (!$plans = Option::where('name', static::OPTION_NAME)->first()) {
            $plans = new Option;
        }
        $plans->name = static::OPTION_NAME;
        $plans->value = json_encode($data, JSON_UNESCAPED_UNICODE);
        $plans->save();
    }
}