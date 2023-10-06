<?php

namespace plugin\ai\app\admin\controller;

use plugin\ai\app\service\ChatGpt;

/**
 * ChatGpt配置
 */
class ChatGptController extends SettingBase
{

    /**
     * 服务名
     * @var string
     */
    protected $service = ChatGpt::class;

}
