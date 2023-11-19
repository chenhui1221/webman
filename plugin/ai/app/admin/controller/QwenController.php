<?php

namespace plugin\ai\app\admin\controller;

use plugin\ai\app\service\Qwen;

/**
 * 百度千帆模型配置
 */
class QwenController extends SettingBase
{

    /**
     * 服务名
     * @var string
     */
    protected $service = Qwen::class;

}
