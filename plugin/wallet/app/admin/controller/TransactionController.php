<?php

namespace plugin\wallet\app\admin\controller;

use support\Request;
use support\Response;
use plugin\wallet\app\model\Transaction;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 账单记录 
 */
class TransactionController extends Crud
{
    
    /**
     * @var Transaction
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Transaction;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('transaction/index');
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
        return view('transaction/insert');
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
        return view('transaction/update');
    }

}
