<?php

namespace plugin\ai\app\admin\controller;

use support\Request;
use support\Response;
use plugin\ai\app\model\Apikey;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;
use Throwable;

/**
 * ApiKey设置 
 */
class ApikeyController extends Crud
{
    
    /**
     * @var Apikey
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Apikey;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return raw_view('apikey/index');
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            if (Apikey::where('apikey', $request->post('apikey'))->first()) {
                return $this->json(1, 'apikey已经存在，不能重复添加');
            }
            return parent::insert($request);
        }
        return raw_view('apikey/insert');
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     * @throws BusinessException
    */
    public function update(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return parent::update($request);
        }
        return raw_view('apikey/update');
    }

    /**
     * 批量插入
     * @param Request $request
     * @return Response
     * @throws Throwable
     */
    public function batchInsert(Request $request): Response
    {
        if ($request->method() !== 'POST') {
            return raw_view('apikey/batch-insert');
        }
        $apiKeys = $request->post('apikeys', []);
        $gpt4 = $request->post('gpt4');
        foreach ($apiKeys as $key) {
            if (!Apikey::where('apikey', $key)->first()) {
                $apikey = new Apikey();
                $apikey->apikey = $key;
                $apikey->gpt4 = $gpt4 ? 1 : 0;
                $apikey->save();
            }
        }
        return $this->json(0);
    }

}
