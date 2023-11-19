<?php

namespace plugin\ai\app\service;

use plugin\admin\app\model\Option;

/**
 * AI模型
 */
class Model extends Base
{
    /**
     * options表对应的name字段
     */
    const OPTION_NAME = 'plugin_ai.models';

    /**获取配置
     *
     * @return array|mixed
     */
    public static function getSetting()
    {
        $items = Option::where('name', static::OPTION_NAME)->value('value');
        $items = $items ? json_decode($items, true) : [];
        if (!$items) {
            $items = [
                'gpt-3.5-turbo' => 'gpt-3.5-turbo',
                'gpt-3.5-turbo-0613' => 'gpt-3.5-turbo-0613',
                'gpt-3.5-turbo-16k' => 'gpt-3.5-turbo-16k',
                'gpt-4' => 'gpt-4',
                'gpt-4-32k' => 'gpt-4-32k',
                "qwen-plus" => "通义千问",
                "ernie-bot-turbo" => "文心一言",
                "ernie-bot-4" => "文心一言4.0",
                "spark" => "讯飞星火",
                "chatglm" => "清华智普",
                "midjourney" => "Midjourney作图",
                "dall.e" => "DALL.E作图",
            ];
            static::saveSetting($items);
        }
        return $items;
    }
}