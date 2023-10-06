<?php

namespace plugin\ai\app\admin\controller;

use plugin\admin\app\model\User;
use support\Request;
use support\Response;
use plugin\ai\app\model\AiUser;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * AI会员 
 */
class AiUserController extends Crud
{
    
    /**
     * @var AiUser
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new AiUser;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return raw_view('ai-user/index');
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
        return raw_view('ai-user/insert');
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
        return raw_view('ai-user/update');
    }

    /**
     * 获取数据
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function select(Request $request): Response
    {
        [$where, $format, $limit, $field, $order] = $this->selectInput($request);
        $query = $this->doSelect($where, $field, $order);
        // 带上用户基本信息
        $query = $query->with('base');
        return $this->doFormat($query, $format, $limit);
    }


}
