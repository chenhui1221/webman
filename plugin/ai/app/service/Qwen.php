<?php

namespace plugin\ai\app\service;

use plugin\admin\app\model\Option;

/**
 * 阿里灵积相关模型
 */
class Qwen extends Base
{
    /**
     * options表对应的name字段
     */
    const OPTION_NAME = 'plugin_ai.qwen-setting';

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
                'enable' => true,
                'apikey' => '',
                'reg_free_count' => 20,
                'day_free_count' => 0,
            ];
            static::saveSetting($setting);
        }
        return $name ? $setting[$name] ?? $default : $setting;
    }

}