<?php

namespace app\controller;

use support\Request;
use think\facade\Db;

class IndexController
{
    public function index(Request $request)
    {
        static $readme;
        if (!$readme) {
            $readme = file_get_contents(base_path('README.md'));
        }
        return $readme;
    }

    public function view(Request $request)
    {
        return view('index/view', ['name' => 'webman']);
    }

    public function json(Request $request)
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }
    public function get(){
        $hello = trans('hello'); // 你好 世界!
        return response($hello);
    }
    public function sql(){
       //return Db::name('ai_users')->select();
      var_dump(\support\Db::table('ai_users')->get()->toArray());
    }
}
