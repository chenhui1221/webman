<?php
/**
 * @author charles
 * @created 2023/10/28 16:23
 */

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class ApiFormat implements MiddlewareInterface
{

    public function process(Request $request, callable $handler): Response
    {
        /**
         * @var Response $response
         */
        $response = $handler($request);

        return $response;
    }
}