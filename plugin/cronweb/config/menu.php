<?php

return [
    [
        'title' => '定时任务',
        'key' => 'crontab',
        'icon' => 'layui-icon-align-left',
        'weight' => 0,
        'type' => 0,
        'children' => [
            [
                'title' => '任务列表',
                'key' => 'crontabList',
                'href' => '/app/cronweb/index/index',
                'type' => 1,
                'weight' => 0,
            ],
            [
                'title' => '任务日志',
                'key' => 'showLog',
                'href' => '/app/cronweb/index/showLog',
                'type' => 1,
                'weight' => 0,
            ]
        ]
    ]
];
