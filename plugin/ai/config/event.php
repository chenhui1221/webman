<?php

return [
    // 当渲染用户端导航菜单时
    'user.nav.render' => [
        function(stdClass $object) {
            $object->navs[] = [
                'name' => 'AI',
                'items' => [
                    ['name' => 'AI', 'url' => '/app/ai'],
                ]
            ];
        }
    ]
];
