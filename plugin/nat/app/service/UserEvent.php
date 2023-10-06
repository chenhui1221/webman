<?php

namespace plugin\nat\app\service;

use plugin\nat\app\model\NatApp;
use plugin\user\app\model\User as UserModel;
use stdClass;

/**
 * 用户相关事件
 */
class UserEvent
{
    /**
     * 用户注册时
     * @return void
     */
    public function onUserRegister(UserModel $user)
    {
        $request = request();
        $host = $request->host(false);
        // 创建一个默认内网穿透应用
        if (!filter_var($host, FILTER_VALIDATE_IP)) {
            $username = $user->username;
            $domain = "$username.$host";
            if (!NatApp::where('domain', $domain)->first()) {
                $app = new NatApp;
                $app->user_id = $user->id;
                $app->name = '我的应用';
                $app->domain = $domain;
                $app->local_ip = '127.0.0.1';
                $app->local_port = 8787;
                $app->save();
            }
        }
    }

    /**
     * 当渲染用户端顶部导航菜单时
     * @param stdClass $object
     * @return void
     */
    public function onUserNavRender(stdClass $object)
    {
        $request = request();
        $path = $request ? $request->path() : '';
        // 添加内网穿透自己的导航栏菜单
        $object->navs[] = [
            'name' => '内网穿透',
            'items' => [
                ['name' => '我的应用', 'url' => '/app/nat/apps', 'class' => $path === '/app/nat/apps' ? 'active' : ''],
                ['name' => '我的token', 'url' => '/app/nat/token', 'class' => $path === '/app/nat/token' ? 'active' : ''],
            ]
        ];
    }

    /**
     * 当渲染用户中心左侧菜单时
     * @param stdClass $object
     * @return void
     */
    public function onUserSidebarRender(stdClass $object)
    {
        $request = request();
        $path = $request ? $request->path() : '';
        // 添加内网穿透自己的左侧用户中心菜单
        $object->sidebars[] = [
            'name' => '内网穿透',
            'items' => [
                ['name' => '我的应用', 'url' => '/app/nat/apps', 'class' => $path === '/app/nat/apps' ? 'active' : ''],
                ['name' => '我的token', 'url' => '/app/nat/token', 'class' => $path === '/app/nat/token' ? 'active' : ''],
            ]
        ];
    }
}