<?php
namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class Lang implements MiddlewareInterface
{
    public function process(Request $request, callable $next) : Response
    {
        locale(session('lang', 'zh_CN'));
        return $next($request);
    }
    
}
