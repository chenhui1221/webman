<?php

namespace plugin\ai\app\controller;

use plugin\user\api\User;
use support\Request;
use support\Response;

/**
 * 应用市场相关
 */

class MarketController
{
    /**
     * 不需要登录的方法
     *
     * @var string[]
     */
    protected $noNeedLogin = ['index'];

    /**
     * 应用市场
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        if (!class_exists(User::class)) {
            return \response('请在 <a target="_blank" href="https://www.workerman.net/app/view/admin">webman/admin后台</a> 重新安装webman/ai');
        }
        return view('market/index');
    }

}
