<?php

namespace plugin\ai\app\service;

use plugin\admin\app\model\Option;

class ChatGpt extends Base
{
    /**
     * options表对应的name字段
     */
    const OPTION_NAME = 'plugin_ai.chatgpt-setting';

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
                'api_host' => 'https://ai.fakeopen.com', // api地址
                'enable_gpt3' => true, // 开启gpt3
                'enable_gpt4' => true, // 开启gpt4
                'enable_dalle' => true, // 开启dall.e作图
                'gpt3_reg_free_count' => 20, // 注册赠送gpt3.5消息数
                'gpt4_reg_free_count' => 5,  // 注册赠送gpt3.5消息数
                'dalle_reg_free_count' => 2,  // 注册赠送dall.e作图数
                'gpt3_day_free_count' => 10,  // 每日免费gpt3.5消息数
                'gpt4_day_free_count' => 0,  // 每日免费gpt4消息数
                'dalle_day_free_count' => 0, // 每日免费dall.e作图数
            ];
            static::saveSetting($setting);
        }
        return $name ? $setting[$name] ?? $default : $setting;
    }
}
