<?php

namespace plugin\ai\app\controller;

use plugin\ai\api\Install;
use plugin\ai\app\model\AiUser;
use plugin\ai\app\service\ChatGpt;
use plugin\ai\app\service\Common;
use plugin\ai\app\service\Setting;
use plugin\ai\app\service\User;
use plugin\user\api\Limit;
use support\Db;
use support\exception\BusinessException;
use support\Response;
use Throwable;

class Base
{

    /**
     * 尝试减少余额
     * @param $modelType
     * @param $isVip
     * @return string|null
     * @throws BusinessException
     */
    public static function tryReduceBalance($modelType, &$isVip)
    {
        if (!Common::modelEnabled($modelType)) {
            return "系统未开启 $modelType 功能";
        }
        $request = request();
        $session = $request->session();
        $loginUser = $session->get('user');
        $loginUserId = $loginUser['uid'] ?? $loginUser['id'] ?? null;
        $expired = false;
        $isVip = $loginUserId && User::isVip($loginUserId, $expired);
        if ($expired) {
            return "会员已经过期，[请续费](/app/ai/user/vip)";
        }

        if (!$loginUserId && Setting::getSetting('need_login')) {
            return "[请登录](/app/ai/user/login)";
        }
        // 尝试从余额中扣除
        if ($loginUserId && User::reduceBalance($loginUserId, $modelType)) {
            return null;
        }

        if ($isVip && $modelType === 'gpt4' && ($request->host() === 'bla.cn' || $request->host() === 'www.workerman.net')) {
            return null;
        }

        // 余额不足则使用每日赠送余额
        $freeCountPerDay = Common::getDayFreeCount($modelType);
        try {
            if (!$freeCountPerDay) {
                throw new BusinessException('余额不足');
            }
            Limit::perDay($session->getId() . "-ai-$modelType-", $freeCountPerDay);
            $ip = request()->getRealIp();
            // 非内网ip时
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                Limit::perDay("$ip-ai-$modelType-", $freeCountPerDay);
            }
            // 消息加1
            if ($loginUserId) {
                AiUser::where('user_id', $loginUserId)->update([
                    'message_count' => Db::raw('message_count + 1'),
                ]);
            }
            // 赠送余额不足则给出提示
        } catch (Throwable $e) {
            if ($isVip) {
                return  "您的账户{$modelType}额度不足，如需继续使用请 [续费](/app/ai/user/vip?redirect=".urlencode('/app/ai').")";
            }
            return "您今天{$modelType}消息已经达到上限，如需继续使用请 [升级会员](/app/ai/user/vip?redirect=".urlencode('/app/ai').")";
        }

        return null;
    }

    /**
     * AI是否开启了数据库
     *
     * @return bool
     */
    public static function dbEnabled(): bool
    {
        static $enabled;
        if ($enabled === null) {
            $enabled = false;
            try {
                if (config('plugin.admin.database')) {
                    if (!Db::schema('plugin.admin.mysql')->hasTable('ai_users')) {
                        Install::importDb();
                    }
                    $enabled = Db::schema('plugin.admin.mysql')->hasTable('ai_users');
                }
            } catch (Throwable $exception) {}
        }
        return $enabled;
    }

    /**
     * AI是否开启了支付宝支付
     *
     * @return bool
     */
    public static function alipayEnabled(): bool
    {
        static $enabled;
        if ($enabled === null) {
            $enabled = static::dbEnabled() && config('plugin.ai.payment.alipay.default.alipay_root_cert_path');
        }
        return $enabled;
    }

    /**
     * AI是否开启了微信支付
     *
     * @return bool
     */
    public static function wechatEnabled(): bool
    {
        static $enabled;
        if ($enabled === null) {
            $enabled = static::dbEnabled() && config('plugin.ai.payment.wechat.default.mch_public_cert_path');
        }
        return $enabled;
    }


    /**
     * AI是否开启了支付
     *
     * @return bool
     */
    public static function payEnabled(): bool
    {
        static $enabled;
        if ($enabled === null) {
            $enabled = static::wechatEnabled() || static::alipayEnabled();
        }
        return $enabled;
    }

    /**
     * @param $code
     * @param string $msg
     * @param mixed $data
     * @return Response
     */
    protected function json($code, string $msg = 'ok', $data = []): Response
    {
        return json(['code' => $code, 'msg' => $msg, 'data' => $data]);
    }

}
