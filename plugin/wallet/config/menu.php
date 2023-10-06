<?php

return [
    [
        'title' => '钱包管理',
        'key' => 'crontab',
        'icon' => 'layui-icon-align-left',
        'weight' => 0,
        'type' => 0,
        'children' => [
            [
                'title' => '币种列表',
                'key' => 'crontabList',
                'href' => '/app/wallet/sy/index',
                'type' => 1,
                'weight' => 0,
            ],
            [
                'title' => '用户钱包',
                'key' => 'showLog',
                'href' => '/app/cronweb/index/showLog',
                'type' => 1,
                'weight' => 0,
            ]
        ]
    ]
];