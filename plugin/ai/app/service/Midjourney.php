<?php

namespace plugin\ai\app\service;

use plugin\admin\app\model\Option;

class Midjourney extends Base
{
    /**
     * options表对应的name字段
     */
    const OPTION_NAME = 'plugin_ai.midjourney-setting';

    /**获取配置
     *
     * @return array|mixed
     */
    public static function getSetting($name = '', $default = null)
    {
        $setting = Option::where('name', static::OPTION_NAME)->value('value');
        $setting = $setting ? json_decode($setting, true) : [];
        if (!$setting) {
            $setting = [
                'enable' => true, // 是否开启
                'api_host' => 'http://127.0.0.1:8080', // api地址
                'reg_free_count' => 2,  // 注册赠送midjourney作图数
                'day_free_count' => 0,  // 注册赠送midjourney作图数
            ];
            static::saveSetting($setting);
        }
        return $name ? $setting[$name] ?? $default : $setting;
    }

}