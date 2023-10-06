<?php

namespace plugin\nat\api;

use plugin\admin\app\common\Util;
use Throwable;

class Install
{
    /**
     * 安装
     * @return void
     */
    public static function install()
    {
        $sqls = file_get_contents(base_path('plugin/nat/install.sql'));
        $sqls = explode(';', $sqls);
        foreach ($sqls as $sql) {
            $sql = trim($sql);
            if ($sql) {
                try {
                    Util::db()->select($sql);
                } catch (Throwable $e) {}
            }
        }
    }

    /**
     * 卸载
     * @return void
     */
    public static function uninstall()
    {
        try {
            Util::schema()->drop('nat_users');
        } catch (Throwable $e) {}
        try {
            Util::schema()->drop('nat_apps');
        } catch (Throwable $e) {}
    }

}