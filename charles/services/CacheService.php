<?php
/**
 * @author charles
 * @created 2023/11/16 18:17
 */

namespace charles\services;

use Closure;
use Illuminate\Contracts\Cache\Lock;
use Shopwwi\LaravelCache\Cache;

class CacheService
{
    /**
     * 过期时间
     * @var int
     */
    protected static $expire;

    /**
     * 写入缓存
     * @param string $name 缓存名称
     * @param mixed $value 缓存值
     * @param int|null $expire 缓存时间，为0读取系统缓存时间
     */
    public static function set(string $name, $value, int $expire = 600, string $tag = 'charles')
    {

        try {
            return Cache::tags($tag)->set($name, $value, $expire);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 如果不存在则写入缓存
     * @param string $name
     * @param mixed $default
     * @param int|null $expire
     * @param string $tag
     * @return mixed|string|null
     */
    public static function remember(string $name, $default = '', int $expire = 600, string $tag = 'charles')
    {
        try {
            return Cache::tags($tag)->remember($name, $expire, $default);
        } catch (\Throwable $e) {
            try {
                if (is_callable($default)) {
                    return $default();
                } else {
                    return $default;
                }
            } catch (\Throwable $e) {
                return null;
            }
        }
    }

    /**
     * 读取缓存
     * @param string $name
     * @param mixed $default
     * @return mixed|string
     */
    public static function get(string $name, $default = '', $tags = 'charles')
    {
        return Cache::tags($tags)->get($name) ?? $default;
    }

    /**
     * 删除缓存
     * @param string $name
     * @return bool
     */
    public static function delete(string $name): bool
    {
        return Cache::forget($name);
    }

    /**
     * 清空缓存池
     * @return bool
     */
    public static function clear(string $tags = 'charles'): bool
    {
        return Cache::tags($tags)->flush();
    }

    /**
     * 检查缓存是否存在
     * @param string $key
     * @return bool
     */
    public static function has(string $key, $tags = "charles"): bool
    {
        try {
            return Cache::tags($tags)->has($key);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 指定缓存类型
     * @param string $type
     * @param string $tag
     * @return
     */
    public static function store(string $type = 'redis', string $tag = 'charles'): \Illuminate\Cache\TaggedCache
    {
        return Cache::store($type)->tags($tag);
    }

    /**
     * 获取锁
     * @param string $name 锁的名称
     * @param int $seconds 锁持续时间
     * @return Lock | bool
     */
    public static function acquire(string $name, int $seconds = 10)
    {
        $lock = Cache::lock($name, $seconds);

        if ($lock->get()) {
            // 锁定成功
            return $lock;
        }

        // 获取锁失败
        return false;
    }

    /**
     * 释放锁
     * @param Lock $lock
     * @return void
     */
    public static function release(Lock $lock)
    {
        $lock->release();
    }

    /**
     * 尝试获取锁并执行闭包
     *
     * @param string $name 锁的名称
     * @param int $seconds 锁持续时间
     * @param Closure $callback 获取锁后执行的闭包
     * @param int $timeout 获取锁的超时时间
     * @return mixed
     */
    public static function run(string $name, int $seconds, Closure $callback, int $timeout = 0)
    {
        return Cache::lock($name, $seconds)->block($timeout, function () use ($callback) {
            return $callback();
        });

    }

}