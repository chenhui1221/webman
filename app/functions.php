<?php
/**
 * Here is your custom functions.
 */
if (!function_exists('app')) {
    /**
     * 快速获取容器中的实例 支持依赖注入
     * @template T
     * @param string|class-string<T> $name        类名或标识 默认获取当前应用实例
     */
    function app(string $name = '')
    {
        return \support\Container::get($name);
    }
}