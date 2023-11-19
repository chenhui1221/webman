<?php
/**
 * @author charles
 * @created 2023/9/26 19:00
 */

namespace app\common\crontab;

error_log('TestCron is being loaded'); // 添加这一行进行日志记录
class TestCron
{

    public function run($params){

        sleep(1);
        return $params;
    }
    public function test(){
        sleep(2);
        return 'test';
    }
}