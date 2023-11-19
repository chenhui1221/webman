<?php

namespace plugin\ai\app\admin\controller;

use plugin\ai\app\service\Chatglm;

/**
 * 清华智普模型配置
 */
class ChatglmController extends SettingBase
{

    /**
     * 服务名
     * @var string
     */
    protected $service = Chatglm::class;

}
