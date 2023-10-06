<?php

namespace plugin\ai\app\service;

use plugin\admin\app\model\Option;

class Plan extends Base
{
    /**
     * options表对应的name字段
     */
    const OPTION_NAME = 'plugin_ai.plans';

    /**获取配置
     *
     * @return array|mixed
     */
    public static function getSetting()
    {
        $plans = Option::where('name', static::OPTION_NAME)->value('value');
        $plans = $plans ? json_decode($plans, true) : [];
        if (!$plans || !is_array($plans)) {
            $plans = [
                ['plan' => 1, 'price' => 19, 'months' => 1, 'name' => '月度会员', 'gpt3' => 1000, 'gpt4' => 20, 'dalle' => 20, 'midjourney' => 20],
                ['plan' => 2, 'price' => 49, 'months' => 3, 'name' => '季度会员', 'gpt3' => 3000, 'gpt4' => 60, 'dalle' => 60, 'midjourney' => 60],
                ['plan' => 3, 'price' => 168, 'months' => 12, 'name' => '年度会员', 'gpt3' => 12000, 'gpt4' => 240, 'dalle' => 240, 'midjourney' => 240],
            ];
            static::saveSetting($plans);
        }
        // 兼容老版本
        if (!isset(current($plans)['gpt3'])) {
            foreach ($plans as $key => $plan) {
                if (!isset($plan['gpt3']) && isset($plan['months'])) {
                    $plans[$key]['gpt3'] = $plan['months'] * 1000;
                    $plans[$key]['gpt4'] = $plan['months'] * 20;
                    $plans[$key]['dalle'] = $plan['months'] * 20;
                    $plans[$key]['midjourney'] = $plan['months'] * 20;
                }
            }
            static::saveSetting($plans);
        }
        return array_column($plans, null, 'plan');
    }
}