<?php

namespace plugin\ai\app\controller;

use plugin\ai\app\service\Category;
use plugin\ai\app\service\ChatGpt;
use plugin\ai\app\service\Midjourney;
use plugin\ai\app\service\Model;
use plugin\ai\app\service\Plan;
use plugin\ai\app\service\Setting;
use support\Request;
use support\Response;

class SettingController extends Base
{
    /**
     * 不需要登录的方法
     *
     * @var string[]
     */
    protected $noNeedLogin = ['index', 'models', 'categories'];

    /**
     * 获取配置
     *
     * @return Response
     */
    public function index(): Response
    {
        $models = Model::getSetting();
        $midjourneySetting = Midjourney::getSetting();
        $gptSetting = ChatGpt::getSetting();
        if (empty($midjourneySetting['enable'])) {
            unset($models['midjourney']);
        }
        $enabledModelTypes = [];
        if ($gptSetting['enable_gpt3']) {
            $enabledModelTypes['gpt3'] = 'gpt3.5对话';
        }
        if ($gptSetting['enable_gpt4']??false) {
            $enabledModelTypes['gpt4'] = 'gpt4对话';
        }
        if ($gptSetting['enable_dalle']??false) {
            $enabledModelTypes['dalle'] = 'Dall.E作图';
        }
        if ($midjourneySetting['enable']??false) {
            $enabledModelTypes['midjourney'] = 'Midjourney作图';
        }

        return $this->json(0, 'ok', [
            'defaultModels' => $models,
            'dbEnabled' => static::dbEnabled(),
            'enabledAlipay' => static::alipayEnabled(),
            'enabledWechat' => static::wechatEnabled(),
            'enablePayment' => Setting::getSetting('enable_payment'),
            'enabledModelTypes' => $enabledModelTypes,
            'plans' => static::dbEnabled() ? Plan::getSetting() : []
        ]);
    }

    /**
     * 可用模型
     *
     * @param Request $request
     * @return Response
     */
    public function models(Request $request): Response
    {
        $models = [];
        foreach (Model::getSetting() as $model => $name) {
            $models[] = [
                'name' => $name,
                'value' => $model
            ];
        }
        return $this->json(0, 'ok', $models);
    }


    /**
     * 获取所有分类
     *
     * @param Request $request
     * @return Response
     */
    public function categories(Request $request): Response
    {
        $categories = explode("\n", Category::getSetting());
        $categories = array_filter($categories, 'strlen');
        $data = [];
        foreach ($categories as $category) {
            $category = trim($category);
            if ($category === '') continue;
            $data[] = [
                'name' => $category,
                'value' => $category
            ];
        }
        return json(['code' => 0, 'msg' => 'ok', 'data' => $data]);
    }

}
