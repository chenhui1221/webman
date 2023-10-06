<?php

use plugin\nat\app\service\UserEvent;

return [
    // 当有用户注册时
    'user.register' => [
        [UserEvent::class, 'onUserRegister']
    ],
    // 当渲染用户端导航菜单时
    'user.nav.render' => [
        [UserEvent::class, 'onUserNavRender']
    ],
    // 当渲染用户中心左侧边栏时
    'user.sidebar.render' => [
        [UserEvent::class, 'onUserSidebarRender']
    ],
];
