<?php

namespace plugin\nat\app\controller;

use Exception;
use plugin\nat\app\model\NatApp;
use plugin\user\api\FormException;
use plugin\nat\app\model\NatUser;
use support\Request;
use support\Response;

class TokenController
{

    /**
     * token
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function index(Request $request): Response
    {
        $loginUserId = session('user.id');
        $token = NatUser::where('user_id', $loginUserId)->value('token');
        if (!$token) {
            $token = md5(session('user.username') . random_bytes(16));
            $natUser = new NatUser();
            $natUser->token = $token;
            $natUser->user_id = $loginUserId;
            $natUser->save();
        }
        return view('token/index', [
            'token' => $token
        ]);
    }


}
