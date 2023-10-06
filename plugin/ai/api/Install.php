<?php

namespace plugin\ai\api;

use plugin\admin\api\Menu;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use support\Db;
use Throwable;

class Install
{
    /**
     * 安装
     *
     * @param $version
     * @return void
     */
    public static function install($version)
    {
        // 安装数据库
        static::importDb();
        // 导入菜单
        if($menus = static::getMenus()) {
            Menu::import($menus);
        }
    }

    /**
     * 卸载
     *
     * @param $version
     * @return void
     */
    public static function uninstall($version)
    {
        // 删除菜单
        foreach (static::getMenus() as $menu) {
            Menu::delete($menu['key']);
        }
        // 删除表格
        static::dropTables();
    }

    /**
     * 更新
     *
     * @param $from_version
     * @param $to_version
     * @param $context
     * @return void
     */
    public static function update($from_version, $to_version, $context = null)
    {
        // 删除不用的菜单
        if (isset($context['previous_menus'])) {
            static::removeUnnecessaryMenus($context['previous_menus']);
        }

        // 安装数据库
        static::importDb();

        // 导入新菜单
        if ($menus = static::getMenus()) {
            Menu::import($menus);
        }

        // 保存chatgpt api_key
        $config_file = base_path('/plugin/ai/config/chatgpt.php');
        if (version_compare($from_version, '3.0', '<')) {
            $api_key = $context['chatgpt']['api_key'];
            $config_content = file_get_contents($config_file);
            $config_content = str_replace("'api_key' => ''", "'api_key' => '$api_key'", $config_content);
            file_put_contents($config_file, $config_content);
        } else {
            // 3.x 版本直接替换版本号
            $config_content = str_replace($from_version, $to_version, $context['config_files']['app']);
            file_put_contents(base_path('/plugin/ai/config/app.php'), $config_content);
        }

    }

    /**
     * 更新前数据收集等
     *
     * @param $from_version
     * @param $to_version
     * @return array|array[]
     */
    public static function beforeUpdate($from_version, $to_version)
    {
        // 在更新之前获得老菜单，通过context传递给 update
        $directory = base_path('/plugin/ai/config'); // 替换为目标目录的路径
        $files = [];
        if (is_dir($directory)) {
            $dir = new RecursiveDirectoryIterator($directory);
            $iterator = new RecursiveIteratorIterator($dir);
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $filename = $file->getBasename('.' . $file->getExtension());
                    $content = file_get_contents($file->getPathname());
                    $files[$filename] = $content;
                }
            }
        }
        return [
            'previous_menus' => static::getMenus(),
            'config' => config('plugin.ai'),
            'config_files' => $files,
        ];
    }

    /**
     * 导入数据库
     *
     * @return void
     */
    public static function importDb()
    {
        // 安装文件默认放置于应用插件根目录下
        $mysqlDumpFile = __DIR__ . '/../install.sql';
        if (!is_file($mysqlDumpFile)) {
            return;
        }
        foreach (explode(';', file_get_contents($mysqlDumpFile)) as $sql) {
            if ($sql = trim($sql)) {
                try {
                    Db::connection('plugin.admin.mysql')->statement($sql);
                } catch (Throwable $e) {}
            }
        }
    }

    /**
     * 导入数据库
     *
     * @return void
     */
    protected static function dropTables()
    {
        $tables = ['ai_users', 'ai_orders', 'ai_roles', 'ai_apikeys'];
        foreach ($tables as $table) {
            try {
                Db::schema('plugin.admin.mysql')->drop($table);
            } catch (Throwable $e) {}
        }
    }

    /**
     * 获取菜单
     *
     * @return array|mixed
     */
    public static function getMenus()
    {
        clearstatcache();
        if (is_file($menu_file = __DIR__ . '/../config/menu.php')) {
            $menus = include $menu_file;
            return $menus ?: [];
        }
        return [];
    }

    /**
     * 删除不需要的菜单
     *
     * @param $previous_menus
     * @return void
     */
    public static function removeUnnecessaryMenus($previous_menus)
    {
        $menus_to_remove = array_diff(Menu::column($previous_menus, 'name'), Menu::column(static::getMenus(), 'name'));
        foreach ($menus_to_remove as $name) {
            Menu::delete($name);
        }
    }

}