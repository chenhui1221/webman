<?php

namespace plugin\ai\app\admin\controller;

use plugin\admin\app\controller\Base;
use plugin\ai\app\service\Plan;
use plugin\ai\app\service\Setting;
use support\Request;
use support\Response;

/**
 * 配置设置基类
 */
class SettingBase extends Base
{

    protected $service = Setting::class;

    /**
     * 获取配置
     * @return Response
     */
    public function select(): Response
    {
        return json(['code' => 0, 'msg' => 'ok', 'data' => call_user_func([$this->service, 'get'])]);
    }

    /**
     * 更新配置
     * @param Request $request
     * @return Response
     */
    public function update(Request $request): Response
    {
        $data = $request->post();
        if (in_array(get_class($this), [CategoryController::class, PlanController::class, SensitiveWordController::class])) {
            $data = current($request->post());
        }
        call_user_func([$this->service, 'saveSetting'], $data);
        return $this->json(0);
    }

}
