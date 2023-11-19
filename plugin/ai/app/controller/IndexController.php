<?php

namespace plugin\ai\app\controller;

use plugin\ai\app\service\Plan;
use support\Request;
use support\Response;

class IndexController
{
    /**
     * 不需要登录的方法
     *
     * @var string[]
     */
    protected $noNeedLogin = ['index', 'info'];

    /**
     * 首页
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        if ($request->host() === 'www.workerman.net' && !session('user')) {
            return redirect('/user/login?redirect=/ai');
        }
        return view('index/index', [
            'js_version' => filemtime(base_path('plugin/ai/public/js/app.js')),
            'css_version' => filemtime(base_path('plugin/ai/public/css/app.css'))
        ]);
    }

    /**
     * 信息页
     *
     * @return Response
     */
    public function info(): Response
    {
        return view('index/info');
    }

}
