<?php

namespace plugin\ai\app\service;

use plugin\admin\app\model\Option;

class Setting extends Base
{
    /**
     * options表对应的name字段
     */
    const OPTION_NAME = 'plugin_ai.setting';

    /**获取配置
     *
     * @return array|mixed
     */
    public static function getSetting($name = '', $default = null)
    {
        $setting = Option::where('name', static::OPTION_NAME)->value('value');
        $setting = $setting ? json_decode($setting, true) : [];
        if (!isset($setting['enable_payment'])) {
            $setting = [
                'enable_payment' => true,
                'need_login' => false,
            ];
            static::saveSetting($setting);
        }
        return $name ? $setting[$name] ?? $default : $setting;
    }

    /**
     * 保存配置
     *
     * @param $data
     * @return void
     */
    public static function saveSetting($data)
    {
        $data['enable_payment'] = $data['enable_payment'] ?? false;
        if (!$item = Option::where('name', static::OPTION_NAME)->first()) {
            $item = new Option;
        }
        $item->name = static::OPTION_NAME;
        $item->value = json_encode($data, JSON_UNESCAPED_UNICODE);
        $item->save();
    }
}