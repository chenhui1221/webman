<?php

namespace plugin\wallet\app\admin\controller;

use support\Request;
use support\Response;
use plugin\wallet\app\model\BlockCentralWallet;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 中心钱包 
 */
class BlockCentralWalletController extends Crud
{
    
    /**
     * @var BlockCentralWallet
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new BlockCentralWallet;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('block-central-wallet/index');
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
        return view('block-central-wallet/insert');
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
        return view('block-central-wallet/update');
    }

}
