<?php


use plugin\ai\app\middleware\AccessControl;
use plugin\ai\app\middleware\AdminAccessControl;

return [
    '' => [
        AccessControl::class,
    ],
    'admin' => [
        AdminAccessControl::class
    ]
];
