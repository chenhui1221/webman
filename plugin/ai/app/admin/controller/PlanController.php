<?php

namespace plugin\ai\app\admin\controller;

use plugin\ai\app\service\Plan;
use support\Request;
use support\Response;

/**
 * 套餐配置
 */
class PlanController extends SettingBase
{
    /**
     * 服务名
     * @var string
     */
    protected $service = Plan::class;

    /**
     * 更新设置
     * @param Request $request
     * @return Response
     */
    public function update(Request $request): Response
    {
        $plans = current($request->post());
        $json = json_decode($plans, true);
        if (!$json) {
            return $this->json(1, 'json格式错误');
        }
        Plan::saveSetting($json);
        return $this->json(0);
    }

}
