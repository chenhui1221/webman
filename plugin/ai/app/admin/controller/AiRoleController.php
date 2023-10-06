<?php

namespace plugin\ai\app\admin\controller;

use support\Request;
use support\Response;
use plugin\ai\app\model\AiRole;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;
use Throwable;

/**
 * AI角色 
 */
class AiRoleController extends Crud
{

    /**
     * @var AiRole
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new AiRole;
    }

    /**
     * 浏览
     * @return Response
     * @throws Throwable
     */
    public function index(): Response
    {
        return raw_view('ai-role/index');
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     * @throws BusinessException
     * @throws Throwable
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return parent::insert($request);
        }
        return raw_view('ai-role/insert');
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     * @throws BusinessException|Throwable
     */
    public function update(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return parent::update($request);
        }
        return raw_view('ai-role/update');
    }

    /**
     * 查询
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function select(Request $request): Response
    {
        // 没有数据的时候从roles.json生成
        if (!AiRole::first()) {
            $roles = json_decode(file_get_contents(base_path('plugin/ai/roles.json')), true);
            foreach ($roles as $item) {
                $role = new AiRole();
                foreach (['roleId', 'model', 'name', 'desc', 'rolePrompt', 'avatar', 'maxTokens', 'contextNum', 'greeting', 'temperature', 'category'] as $name) {
                    if (isset($item[$name])) {
                        $role->$name = $item[$name];
                    }
                }
                $role->save();
            }
        }
        [$where, $format, $limit, $field, $order] = $this->selectInput($request);
        $query = $this->doSelect($where, $field, $order);
        return $this->doFormat($query, $format, $limit);
    }

}
