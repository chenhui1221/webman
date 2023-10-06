<?php

namespace plugin\ai\app\admin\controller;

use plugin\ai\app\service\Midjourney;

/**
 * Midjourney配置
 */
class MidjourneyController extends SettingBase
{

    /**
     * 服务名
     * @var string
     */
    protected $service = Midjourney::class;

}
