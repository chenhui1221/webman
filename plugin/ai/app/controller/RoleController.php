<?php

namespace plugin\ai\app\controller;

use plugin\ai\app\model\AiRole;
use support\Request;
use support\Response;

class RoleController extends Base
{
    /**
     * 不需要登录的方法
     *
     * @var string[]
     */
    protected $noNeedLogin = ['index', 'installed'];

    /**
     * 获取角色
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        // 获取所有角色
        if ($request->get('type') == 'all') {
            return $this->json(0, 'ok', AiRole::get());
        }
        // 获取预安装角色
        return $this->json(0, 'ok', AiRole::where('preinstalled', 1)->get());
    }

    /**
     * 安装角色计数
     *
     * @param Request $request
     * @return Response
     */
    public function installed(Request $request): Response
    {
        $roleId = $request->post('roleId');
        $session = $request->session();
        $key = "ai_installed_$roleId";
        if (!$session->get($key)) {
            if ($role = AiRole::find($roleId)) {
                $role->installed++;
                $role->save();
                $session->set($key, 1);
            }
        }
        return $this->json(0);
    }

}
