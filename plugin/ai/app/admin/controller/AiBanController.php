<?php

namespace plugin\ai\app\admin\controller;

use support\Request;
use support\Response;
use plugin\ai\app\model\AiBan;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * AI封禁列表 
 */
class AiBanController extends Crud
{
    
    /**
     * @var AiBan
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new AiBan;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('ai-ban/index');
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
            return parent::insert($request);
        }
        return view('ai-ban/insert');
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
        return view('ai-ban/update');
    }

}
