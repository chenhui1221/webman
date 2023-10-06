<?php

return [
    [
        'title' => 'AI助手',
        'key' => 'plugin_ai',
        'icon' => 'layui-icon-android',
        'weight' => 490,
        'type' => 0,
        'children' => [
            [
                'title' => 'AI通用设置',
                'key' => 'plugin\\ai\\app\\admin\\controller\\SettingController',
                'href' => '/app/ai/admin/setting',
                'type' => 1,
                'weight' => 800,
            ], [
                'title' => 'ApiKey设置',
                'key' => 'plugin\\ai\\app\\admin\\controller\\ApiKeyController',
                'href' => '/app/ai/admin/apikey',
                'type' => 1,
                'weight' => 700,
            ], [
                'title' => 'AI角色',
                'key' => 'plugin\\ai\\app\\admin\\controller\\AiRoleController',
                'href' => '/app/ai/admin/ai-role',
                'type' => 1,
                'weight' => 600,
            ], [
                'title' => 'AI会员',
                'key' => 'plugin\\ai\\app\\admin\\controller\\AiUserController',
                'href' => '/app/ai/admin/ai-user',
                'type' => 1,
                'weight' => 500,
            ], [
                'title' => 'AI订单',
                'key' => 'plugin\\ai\\app\\admin\\controller\\AiOrderController',
                'href' => '/app/ai/admin/ai-order',
                'type' => 1,
                'weight' => 400,
            ], [
                'title' => 'AI消息',
                'key' => 'plugin\\ai\\app\\admin\\controller\\AiMessageController',
                'href' => '/app/ai/admin/ai-message',
                'type' => 1,
                'weight' => 350,
            ], [
                'title' => 'AI禁用列表',
                'key' => 'plugin\\ai\\app\\admin\\controller\\AiBanController',
                'href' => '/app/ai/admin/ai-ban',
                'type' => 1,
                'weight' => 330,
            ]
        ]
    ],
];
