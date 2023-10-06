<?php

namespace plugin\nat\app\controller;

use Exception;
use plugin\admin\app\model\Option;
use plugin\nat\app\model\NatApp;
use plugin\user\api\FormException;
use plugin\nat\app\model\NatUser;
use support\Request;
use support\Response;

class AppController
{

    /**
     * 列表
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function index(Request $request): Response
    {
        $loginUserId = session('user.id');
        $apps = NatApp::where('user_id', $loginUserId)->get();
        return view('app/index', [
            'apps' => $apps
        ]);
    }

    /**
     * 根据token获取应用信息
     * @param Request $request
     * @return void
     */
    public function get(Request $request): Response
    {
        $token = $request->get('token');
        $userId = NatUser::where('token', $token)->value('user_id');
        if (!$userId) {
            return json(['code' => 1, 'msg' => 'token错误']);
        }
        $items = NatApp::where('user_id', $userId)->get()->keyBy('domain');
        return json(['code' => 0, 'msg' => 'ok', 'data' => $items]);
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     * @throws FormException
     */
    public function update(Request $request): Response
    {
        $id = $request->post('id');
        [$name, $domain, $localIp, $localPort] = $this->validateApp($request->post());
        $loginUserId = session('user.id');
        $nat = NatApp::where(['user_id' => $loginUserId, 'id' => $id])->first();
        if (!$nat) {
            return json(['code' => 1, 'msg' => '记录不存在']);
        }
        if (NatApp::where('domain', $domain)->where('id', '<>', $nat->id)->first()) {
            return json(['code' => 1, 'msg' => "域名 {$domain} 已经占用", 'data' => ['field' => 'domain']]);
        }
        $nat->name = $name;
        $nat->domain = $domain;
        $nat->local_ip = $localIp;
        $nat->local_port = $localPort;
        $nat->user_id = $loginUserId;
        $nat->save();
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 保存
     * @param Request $request
     * @return Response
     * @throws FormException
     */
    public function insert(Request $request): Response
    {
        [$name, $domain, $localIp, $localPort] = $this->validateApp($request->post());
        $loginUserId = session('user.id');
        $maxCount = 2;
        if (NatApp::where('user_id', $loginUserId)->count() >= $maxCount) {
            return json(['code' => 1, 'msg' => "最多支持{$maxCount}个应用"]);
        }
        if (NatApp::where('domain', $domain)->first()) {
            return json(['code' => 1, 'msg' => "{$domain} 已被占用", 'data' => ['field' => 'domain']]);
        }
        $nat = new NatApp();
        $nat->name = $name;
        $nat->domain = $domain;
        $nat->local_ip = $localIp;
        $nat->local_port = $localPort;
        $nat->user_id = $loginUserId;
        $nat->save();
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * @param $post
     * @return array
     * @throws FormException
     */
    protected function validateApp($post): array
    {
        $name = $post['name'] ?? null;
        if (empty($name)) {
            throw new FormException('名字不能为空', 1, 'name');
        }
        $domain = $post['domain'] ?? null;
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
            throw new FormException('域名非法', 1, 'domain');
        }
        $localIp = $post['local_ip'] ?? null;
        if (!filter_var($localIp, FILTER_VALIDATE_IP)) {
            throw new FormException('ip非法', 1, 'local_ip');
        }
        $localPort = (int)$post['local_port'] ?? null;
        if ($localPort <= 0 || $localPort >= 65535) {
            throw new FormException('端口非法', 1, 'local_port');
        }
        return [$name, $domain, $localIp, $localPort];
    }

    /**
     * 删除
     * @param Request $request
     * @return Response
     */
    public function delete(Request $request): Response
    {
        $ids = (array)$request->post('id');
        if ($ids) {
            $loginUserId = session('user.id');
            NatApp::where('user_id' , $loginUserId)->whereIn('id', $ids)->delete();
        }
        return json(['code' => 0, 'msg' => 'ok']);
    }

}
