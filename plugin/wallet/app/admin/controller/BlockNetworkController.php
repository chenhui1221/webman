<?php

namespace plugin\wallet\app\admin\controller;

use support\Request;
use support\Response;
use plugin\wallet\app\model\BlockNetwork;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 网络配置 
 */
class BlockNetworkController extends Crud
{
    
    /**
     * @var BlockNetwork
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new BlockNetwork;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('block-network/index');
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
        return view('block-network/insert');
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
        return view('block-network/update');
    }

}
