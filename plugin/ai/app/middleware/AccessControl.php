<?php
namespace plugin\ai\app\middleware;

use plugin\admin\app\model\User;
use plugin\ai\app\model\AiBan;
use ReflectionClass;
use ReflectionException;
use support\exception\BusinessException;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class AccessControl implements MiddlewareInterface
{

    /**
     * @throws ReflectionException|BusinessException
     */
    public function process(Request $request, callable $handler) : Response
    {
        // admin模块不走此中间件
        if ($request->app === 'admin') {
            return $handler($request);
        }

        if ($request->method() == 'OPTIONS') {
            return $this->cors($request, response(''));
        }

        $controller = $request->controller;
        $action = $request->action;
        $loginUserId = session('user.id') ?? session('user.uid');

        if ($loginUserId) {
            // 如果是登录用户，判断是否有会员信息
            \plugin\ai\app\service\User::checkNewUser($loginUserId);
        }

        // 禁用账户不允许post请求
        if ($request->post()) {
            if ($loginUserId) {
                $loginUser = User::find($loginUserId);
                if ($loginUser && $loginUser->status == 1) {
                    $request->session()->forget('user');
                    $msg = '当前账户已被禁用';
                    return $this->cors($request, json(['code' => -2, 'msg' => $msg, 'error' => ['message' => $msg], 'data' => []]));
                }
            }
            $ip = $request->getRealIp();
            if (AiBan::whereIn('type', ['user', 'ip'])->whereIn('value', [$loginUserId, $ip])
                ->where('expired_at', '>', date('Y-m-d H:i:s'))->first()) {
                $msg = '当前账户暂时不可用';
                return $this->cors($request, json(['code' => -2, 'msg' => $msg, 'error' => ['message' => $msg], 'data' => []]));
            }
        }

        // 路由是匿名函数或者用户已经登录的走正常流程
        if (!$controller || $loginUserId) {
            return $this->cors($request, $handler($request));
        }
        // 获取控制器鉴权信息
        $class = new ReflectionClass($controller);
        $properties = $class->getDefaultProperties();
        // 获取需要登陆的控制器action
        $noNeedLogin = $properties['noNeedLogin'] ?? [];
        // 需要登录
        if (!in_array($action, $noNeedLogin)) {
            $msg = '请登录';
            if ($request->expectsJson()) {
                return $this->cors($request, json(['code' => -1, 'msg' => $msg, 'data' => [], 'error' => ['message' => $msg]]));
            }
            return $this->cors($request, redirect('/app/ai/user/login?redirect=' . urlencode($request->uri())));
        }
        return $this->cors($request, $handler($request));
    }

    /**
     * 跨域headers
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    protected function cors(Request $request, Response $response)
    {
        if (method_exists($response, 'exception') && $exception = $response->exception()) {
            $code = $exception->getCode() ?: 1;
            $msg = $exception->getMessage();
            $response = $request->expectsJson() ? json([
                'code' => $code,
                'msg' => $msg,
                'error' => ['message' => $msg],
                'data' => [],
                'traces' => config('plugin.ai.app.debug') ? $exception->getTraceAsString() : ''
            ]) : $response;
        }
        return $response->withHeaders([
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Origin' => $request->header('origin', '*'),
            'Access-Control-Allow-Methods' => $request->header('access-control-request-method', '*'),
            'Access-Control-Allow-Headers' => $request->header('access-control-request-headers', '*'),
        ]);
    }
}