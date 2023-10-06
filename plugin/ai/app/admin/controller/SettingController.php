<?php

namespace plugin\ai\app\admin\controller;

use plugin\admin\app\controller\Base;
use plugin\admin\app\model\Option;
use plugin\ai\app\service\Category;
use plugin\ai\app\service\ChatGpt;
use plugin\ai\app\service\Model;
use plugin\ai\app\service\Plan;
use plugin\ai\app\service\SensitiveWord;
use plugin\ai\app\service\Setting;
use plugin\ai\app\service\Midjourney;
use support\Request;
use support\Response;
use Throwable;

/**
 * 配置管理
 */
class SettingController extends Base
{

    /**
     * 浏览配置
     *
     * @return Response
     * @throws Throwable
     */
    public function index(): Response
    {
        return raw_view('setting/index');
    }

    /**
     * 获取设置
     *
     * @return Response
     */
    public function select(): Response
    {
        return json(['code' => 0, 'msg' => 'ok', 'data' => [
            'setting' => Setting::getSetting(),
            'chatgpt' => ChatGpt::getSetting(),
            'midjourney' => Midjourney::getSetting(),
            'category' => Category::getSetting(),
            'plan' => json_encode(Plan::getSetting(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'model' => json_encode(Model::getSetting(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'sensitive-word' => SensitiveWord::getSetting(),
        ]]);
    }

    /**
     * 更新设置
     *
     * @param Request $request
     * @return Response
     */
    public function update(Request $request): Response
    {
        Setting::saveSetting($request->post());
        return $this->json(0);
    }

}
