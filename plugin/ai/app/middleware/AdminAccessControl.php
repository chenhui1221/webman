<?php
namespace plugin\ai\app\middleware;

use plugin\admin\api\Auth;
use plugin\ai\api\Install;
use ReflectionException;
use support\Db;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use support\exception\BusinessException;

/**
 * admin管理后台中间件
 */
class AdminAccessControl implements MiddlewareInterface
{
    /**
     * 鉴权
     * @param Request $request
     * @param callable $handler
     * @return Response
     * @throws ReflectionException
     * @throws BusinessException
     */
    public function process(Request $request, callable $handler): Response
    {
        $this->tryImportDb();

        $controller = $request->controller;
        $action = $request->action;

        $code = 0;
        $msg = '';
        if (!Auth::canAccess($controller, $action, $code, $msg)) {
            if ($request->expectsJson()) {
                $response = json(['code' => $code, 'msg' => $msg, 'type' => 'error']);
            } else {
                if ($code === 401) {
                    $response = response(<<<EOF
<script>
    if (self !== top) {
        parent.location.reload();
    }
</script>
EOF
                    );
                } else {
                    $request->app = '';
                    $request->plugin = 'admin';
                    $response = view('common/error/403')->withStatus(403);
                }
            }
        } else {
            $response = $request->method() == 'OPTIONS' ? response('') : $handler($request);
        }
        return $response;
    }

    /**
     * 尝试导入数据库
     *
     * @return void
     */
    protected function tryImportDb()
    {
        static $dbImported = false;
        if (!$dbImported) {
            if (!Db::schema('plugin.admin.mysql')->hasTable('ai_users')) {
                Install::importDb();
            }
            $dbImported = true;
        }
    }

}
