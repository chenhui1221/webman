<?php

namespace plugin\ai\app\controller;

use plugin\ai\app\model\AiUser;
use plugin\ai\app\service\ChatGpt;
use plugin\ai\app\service\Midjourney;
use plugin\user\api\User;
use plugin\user\app\service\Register;
use support\Request;
use support\Response;
use Yansongda\Pay\Pay;

class UserController extends Base
{

    /**
     * 不需要登录的方法
     *
     * @var string[]
     */
    protected $noNeedLogin = ['info', 'register', 'login'];


    /**
     * 用户首页
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $userId = session('user.id') ?? session('user.uid');
        $aiUser = [
            'user_id' => $userId,
            'expired_at' => '',
            'vip' => 0,
            'vipStr' => '',
        ];
        $dbEnabled = static::dbEnabled();
        if ($dbEnabled) {
            $aiUser = AiUser::where('user_id', $userId)->first();
            if (!$aiUser) {
                $aiUser = new AiUser();
                $aiUser->user_id = $userId;
                $aiUser->expired_at = null;
            }
            $aiUser->vip = 0;
            $aiUser->vipStr = '<span class="text-danger">未开通</span>';
            if ($aiUser->expired_at) {
                $expired = time() > strtotime($aiUser->expired_at);
                $aiUser->vipStr = $expired ? '<sapn class="text-danger">已过期</sapn>' : '<sapn class="text-success">已开通</sapn>';
                $aiUser->vip = !$expired;
            }
        }
        $user = session('user');
        $enabledAlipay = static::alipayEnabled();
        $enabledWechat = static::wechatEnabled();
        $gptSetting = ChatGpt::getSetting();
        $midjourneySetting = Midjourney::getSetting();

        return view('user/index', [
            'aiUser' => $aiUser,
            'gptSetting' => $gptSetting,
            'midjourneySetting' => $midjourneySetting,
            'user' => $user,
            'dbEnabled' => $dbEnabled,
            'vipEnabled' => $enabledAlipay || $enabledWechat
        ]);
    }

    /**
     * 会员支付
     *
     * @param Request $request
     * @return Response
     */
    public function vip(Request $request): Response
    {
        if (!session('user')) {
            return redirect('/app/ai/user/login?redirect=' . urlencode($request->uri()));
        }
        if (!class_exists(Pay::class)) {
            return \response('支付功能需要执行命令 composer require -W yansongda/pay:~3.1.0 并重启');
        }
        return view('user/vip');
    }

    /**
     * 获取用户信息
     * @param Request $request
     * @return Response
     */
    public function info(Request $request): Response
    {
        $data = [
            'username' => '',
            'nickname' => '',
            'avatar' => '/app/ai/avatar/user.png',
            'vip' => false,
            'vip_expired_at' => '',
            'sid' => $request->session()->getId(),
            'apikey' => config('plugin.webman.push.app.app_key')
        ];
        $userId = session('user.id') ?? session('user.uid');
        if ($userId) {
            if (static::dbEnabled()) {
                $aiUser = AiUser::where('user_id', $userId)->first();
                if ($aiUser) {
                    $data['vip_expired_at'] = $aiUser->expired_at;
                    $data['vip'] = $aiUser->expired_at && time() < strtotime($aiUser->expired_at);
                }
            }
            $user = session('user');
            $data['username'] = $user['username'];
            $data['nickname'] = $user['nickname'];
            $data['avatar'] = $user['avatar'];
        }
        return $this->json(0, 'ok', $data);
    }

    /**
     * 注册
     *
     * @return Response
     */
    public function register(): Response
    {
        $settings = Register::getSetting();
        return view('user/register', [
            'settings' => $settings,
        ]);
    }

    /**
     * 登录
     *
     * @return Response
     */
    public function login(): Response
    {
        if (!class_exists(User::class)) {
            return \response('用户功能需要在 <a target="_blank" href="https://www.workerman.net/app/view/admin">webman/admin后台</a> 安装 <a target="_blank" href="https://www.workerman.net/app/view/user">用户模块</a>');
        }
        return view('user/login', ['name' => 'user']);
    }

}
