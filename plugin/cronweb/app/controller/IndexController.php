<?php

namespace plugin\cronweb\app\controller;

use plugin\cronweb\app\model\SystemCrontab;
use support\Response;
use yzh52521\Task\Client;

/**
 * 定时任务控制器
 */
class IndexController
{

    /**
     * @var string[] 任务类型
     */
    private $type = [
        'command',
        'class',
        'url',
        'eval',
        'shell',
    ];

    /**
     * 任务列表
     * @return Response
     */
    public function index(): Response
    {
        return view('index/index');
    }

    /**
     * 分页查询任务列表数据
     * @return Response
     */
    public function list(): Response
    {
        $param = [
            'method' => 'crontabIndex',//计划任务列表
            'args'   => [
                'limit' => \request()->get("limit", 10),
                'page' => \request()->get("page", 1)
            ]
        ];
        $result= Client::instance()->request($param);
        $data = [
            'code' => 1,
            'count' => 0,
            'data' => [],
            'msg' => 'ok'
        ];
        if ($result && isset($result->data) && isset($result->data->total) && isset($result->data->data)) {
            foreach ($result->data->data as $key => $value) {
                $result->data->data[$key]->singleton = $value->singleton == 1 ? 0 : 1;
                $result->data->data[$key]->last_running_time = date("Y-m-d H:i:s", $value->last_running_time);
                $result->data->data[$key]->create_time = date("Y-m-d H:i:s", $value->create_time);
                $result->data->data[$key]->update_time = date("Y-m-d H:i:s", $value->update_time);
            }
            $data = [
                'code' => 0,
                'count' => $result->data->total,
                'data' => $result->data->data,
                'msg' => 'ok'
            ];
        }

        return json($data);
    }

    /**
     * 添加任务页面
     * @return Response
     */
    public function insert(): Response
    {
        return view("index/insert");
    }

    /**
     * 添加任务
     * @return Response
     */
    public function create(): Response
    {
        $singleton = \request()->post("singleton");
        $param = [
            'method' => 'crontabCreate',
            'args'   => [
                'title'     => \request()->post("title"),
                'type'      => \request()->post("type"),
                'rule'      => \request()->post("rule"),
                'target'    => \request()->post("target"),
                'status'    => \request()->post("status"),
                'remark'    => \request()->post("remark"),
                'parameter'    => \request()->post("parameter"),
                'singleton'    => $singleton == 1 ? 0 : 1,
            ]
        ];

        $count = SystemCrontab::where('title', \request()->post("title"))->count();
        if ($count > 0) {
            return json([
                'code' => 400,
                'msg' => '任务已存在'
            ]);
        }

         $result  = Client::instance()->request($param);

        return json($result);
    }

    /**
     * 任务详情
     * @return Response
     */
    public function detail(): Response
    {
        $id = \request()->get("id");
        if (!$id) {
            return json([
                'code' => 400,
                'msg' => '任务不存在'
            ]);
        }
        $crontab = SystemCrontab::where('id', $id)->first();
        $crontab['singleton'] = $crontab['singleton'] == 1 ? 0 : 1;
        return json([
            'code' => 200,
            'msg' => 'ok',
            'data' => $crontab
        ]);
    }

    /**
     * 更新任务页面
     * @return Response
     */
    public function edit(): Response
    {
        return view("index/update");
    }

    /**
     * 更新任务
     * @return Response
     */
    public function update(): Response
    {
        $crontab = SystemCrontab::where('id', \request()->post("id"))->first();
        if (!$crontab) {
            return json([
                'code' => 400,
                'msg' => '任务不存在'
            ]);
        }
        $singleton = \request()->post("singleton");
        $param = [
            'method' => 'crontabUpdate',
            'args'   => [
                'id'     => \request()->post("id"),
                'title'     => \request()->post("title") ?? $crontab->title,
                'type'      => \request()->post("type") ?? $crontab->type,
                'rule'      => \request()->post("rule") ?? $crontab->rule,
                'target'    => \request()->post("target") ?? $crontab->target,
                'status'    => \request()->post("status") ?? $crontab->status,
                'remark'    => \request()->post("remark") ?? $crontab->remark,
                'parameter'    => \request()->post("parameter") ?? $crontab->parameter,
                'singleton'    => $singleton == 1 ? 0 : 1,
            ]
        ];

        $result  = Client::instance()->request($param);
        return json($result);
    }

    /**
     * 删除任务
     * @return Response
     */
    public function delete(): Response
    {
        $param = [
            'method' => 'crontabDelete',
            'args'   => [
                'id' => \request()->post("id"),
            ]
        ];

        $result  = Client::instance()->request($param);
        return json($result);
    }

    /**
     * 任务日志列表页
     * @return Response
     */
    public function showLog() {
        return view('index/logging');
    }

    /**
     * 任务日志列表
     * @return Response
     */
    public function log(): Response
    {
        $param = [
            'method' => 'crontabLog',
            'args'   => [
                'page' => \request()->get("page", 1),
                'limit' => \request()->get("limit", 10),
                'crontab_id' => \request()->get("id"),
            ]
        ];

        $result  = Client::instance()->request($param);
        $data = [
            'code' => 1,
            'count' => 0,
            'data' => [],
            'msg' => 'ok'
        ];
        if (isset($result->data) && isset($result->data->data)) {
            foreach ($result->data->data as $key => $value) {
                $result->data->data[$key]->create_time = date("Y-m-d H:i:s", $value->create_time);
                $result->data->data[$key]->update_time = date("Y-m-d H:i:s", $value->update_time);
            }
            $data = [
                'code' => 0,
                'count' => $result->data->total,
                'data' => $result->data->data,
                'msg' => 'ok'
            ];
        }

        return json($data);
    }

    /**
     * 重启任务
     * @return Response
     */
    public function reload(): Response
    {
        $param = [
            'method' => 'crontabReload',
            'args'   => [
                'id'     => \request()->post("id"),
            ]
        ];

        $result  = Client::instance()->request($param);
        return json($result);
    }
}
