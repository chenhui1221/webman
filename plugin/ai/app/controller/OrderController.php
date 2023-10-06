<?php

namespace plugin\ai\app\controller;

use Exception;
use plugin\ai\app\model\AiOrder;
use plugin\ai\app\model\AiUser;
use plugin\ai\app\service\ChatGpt;
use plugin\ai\app\service\Midjourney;
use plugin\ai\app\service\Plan;
use support\exception\BusinessException;
use support\Log;
use support\Request;
use support\Response;
use Yansongda\Pay\Exception\ContainerDependencyException;
use Yansongda\Pay\Exception\ContainerException;
use Yansongda\Pay\Exception\InvalidParamsException;
use Yansongda\Pay\Exception\ServiceNotFoundException;
use Yansongda\Pay\Pay;

class OrderController extends Base
{

    /**
     * 不需要登录的方法
     *
     * @var string[]
     */
    protected $noNeedLogin = ['status', 'getStatus', 'alipayNotify', 'wechatNotify'];

    /**
     * 创建订单
     * @param Request $request
     * @return string
     * @throws Exception
     */
    public function create(Request $request)
    {
        $login_uid = session('user.id') ?? session('user.uid');
        if (!$login_uid) {
            return redirect('/app/user/login?redirect=' . urlencode('/app/ai/user/vip'));
        }

        $plan = (int)$request->post('plan');
        $paymentMethod = $request->post('paymentMethod');
        $plans = Plan::getSetting();
        if (!isset($plans[$plan])) {
            return \response('参数错误');
        }
        $totalAmount = $plans[$plan]['price'];
        $months = $plans[$plan]['months'];

        // 创建订单
        $order = new AiOrder();
        $orderId = date('YmdHis') . random_int(10000, 99999);
        $order->order_id = $orderId;
        $order->user_id = $login_uid;
        $order->total_amount = $totalAmount;
        $order->state = 'unpaid';
        $order->payment_method = $paymentMethod;
        $order->data = json_encode(['plan' => $plan, 'months' => $months]);
        $order->save();

        return $this->json(0, 'ok', ['orderId' => $orderId]);
    }

    /**
     * 创建订单
     * @param Request $request
     * @return string
     * @throws Exception
     */
    public function alipayQr(Request $request)
    {
        $login_uid = session('user.id') ?? session('user.uid');
        if (!$login_uid) {
            return response('<script>window.parent.location.reload();</script>');
        }

        $orderId = $request->input('orderId');
        $order = AiOrder::where('order_id', $orderId)->first();
        if (!$order) {
            return response('订单不存在');
        }
        $totalAmount = $order->total_amount;

        $scheme = $request->header('x-forwarded-proto');
        $host = $request->host();
        $hostWithOutPort = $request->host(true);
        $hostIsIp = filter_var($hostWithOutPort, FILTER_VALIDATE_IP);
        if (!$scheme) {
            if ($hostIsIp) {
                $scheme = 'http';
            } else {
                $scheme = strpos('https', $request->header('referer', '')) === 0 ? 'https' : 'http';
            }
        }
        $payment_config = config('plugin.ai.payment');
        $payment_config['alipay']['default']['return_url'] = ''; // 二维码模式留空，防止支付后跳转
        $payment_config['alipay']['default']['notify_url'] = "$scheme://$host/app/ai/order/alipay-notify";

        Pay::config($payment_config);

        $subject = "充值官方AI助手会员";
        return Pay::alipay()->web([
            'out_trade_no' => $orderId,
            'total_amount' => $totalAmount,
            'subject' => $subject,
            'qr_pay_mode' => 4,
            'qrcode_width' => 200,
        ])->getBody()->getContents();
    }

    /**
     * 订单状态
     *
     * @param Request $request
     * @return Response
     */
    public function status(Request $request): Response
    {
        $orderId = $request->get('orderId');
        $order = AiOrder::where('order_id', $orderId)->first();
        if (!$order) {
            return $this->json(404, '订单不存在');
        }
        return $this->json(0, 'ok',  ['status' => $order->state]);
    }

    /**
     * 主动获取订单状态
     *
     * @param Request $request
     * @return Response
     * @throws BusinessException
     * @throws ContainerDependencyException
     * @throws ContainerException
     * @throws InvalidParamsException
     * @throws ServiceNotFoundException
     */
    public function getStatus(Request $request): Response
    {
        $orderId = $request->get('orderId');
        $order = AiOrder::where('order_id', $orderId)->first();
        if (!$order) {
            return $this->json(404, '订单不存在');
        }
        // 微信需要transaction_id查询订单，而transaction_id是异步通知的
        if ($order->state === 'paid' || $order->payment_method === 'wechat') {
            return $this->json(0, 'ok',  ['status' => $order->state]);
        }
        Pay::config(config('plugin.ai.payment'));
        $result = Pay::alipay()->find($orderId);
        if ($result['code'] != 10000 || !isset($result['trade_status']) || ($result['trade_status'] !== 'TRADE_SUCCESS' && $result['trade_status'] !== 'TRADE_FINISHED')) {
            return $this->json(0, 'ok',  ['status' => $order->state]);
        }
        $this->dealAlipayOrder($orderId);

        $order = AiOrder::where('order_id', $orderId)->first();
        return $this->json(0, 'ok',  ['status' => $order->state]);
    }

    /**
     * 微信二维码
     *
     * @param Request $request
     * @return Response
     * @throws ContainerDependencyException
     * @throws ContainerException
     * @throws ServiceNotFoundException
     */
    public function wechatQr(Request $request)
    {
        $login_uid = session('user.id') ?? session('user.uid');
        if (!$login_uid) {
            return response('<script>window.parent.location.reload();</script>');
        }

        $orderId = $request->input('orderId');
        $order = AiOrder::where('order_id', $orderId)->first();
        if (!$order) {
            return response('订单不存在');
        }
        $totalAmount = $order->total_amount;

        $scheme = $request->header('x-forwarded-proto');
        $host = $request->host();
        $hostWithOutPort = $request->host(true);
        $hostIsIp = filter_var($hostWithOutPort, FILTER_VALIDATE_IP);
        if (!$scheme) {
            if ($hostIsIp) {
                $scheme = 'http';
            } else {
                $scheme = strpos('https', $request->header('referer', '')) === 0 ? 'https' : 'http';
            }
        }
        $payment_config = config('plugin.ai.payment');
        $payment_config['wechat']['default']['notify_url'] = "$scheme://$host/app/ai/order/wechat-notify";
        Pay::config($payment_config);

        $subject = "充值官方AI助手会员";
        $order = [
            'out_trade_no' => $orderId,
            'description' => $subject,
            'amount' => [
                'total' => (float)$totalAmount * 100,
            ],
        ];
        $result = Pay::wechat()->scan($order);
        if (isset($result['message'])) {
            return \response($result['message']);
        }
        return view('user/wechat', ['codeUrl' => $result['code_url']]);
    }

    /**
     * 支付宝支付通知
     *
     * @param Request $request
     * @return Response
     * @throws BusinessException
     * @throws ContainerDependencyException
     * @throws ContainerException
     * @throws InvalidParamsException
     * @throws ServiceNotFoundException
     */
    public function alipayNotify(Request $request): Response
    {
        Pay::config(config('plugin.ai.payment'));
        $result = Pay::alipay()->callback($request->post());
        if (!empty($result['sub_code'])) {
            Log::error("alipayNotify Error " . json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        $this->dealAlipayOrder($result['out_trade_no']);
        return response('success');
    }

    /**
     * 微信支付通知
     *
     * @param Request $request
     * @return Response
     * @throws BusinessException
     * @throws ContainerDependencyException
     * @throws ContainerException
     * @throws InvalidParamsException
     * @throws ServiceNotFoundException
     */
    public function wechatNotify(Request $request): Response
    {
        Pay::config(config('plugin.ai.payment'));
        $result = Pay::wechat()->callback($request->post());
        $this->dealWechatOrder($result);
        return response(Pay::wechat()->success()->getBody());
    }

    /**
     * 处理订单
     *
     * @param $orderId
     * @return void
     * @throws BusinessException
     * @throws ContainerDependencyException
     * @throws ContainerException
     * @throws InvalidParamsException
     * @throws ServiceNotFoundException
     */
    protected function dealAlipayOrder($orderId)
    {
        $result = Pay::alipay()->find($orderId);
        if ($result['code'] != 10000 || !isset($result['trade_status']) || ($result['trade_status'] !== 'TRADE_SUCCESS' && $result['trade_status'] !== 'TRADE_FINISHED')) {
            throw new BusinessException("订单{$orderId}状态错误 " . json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        $totalAmount = $result['total_amount'];
        $order = AiOrder::where('order_id', $orderId)->first();
        if (!$order) {
            throw new BusinessException("订单{$orderId}不存在: $orderId");
        }
        if ($totalAmount < $order->total_amount) {
            throw new BusinessException("订单金额错误 待支付金额{$order->total_amount} 实际金额$totalAmount");
        }
        $data = json_decode($order->data, true);

        $date = date('Y-m-d H:i:s');
        if (!$order->paid_amount) {
            // 更新订单
            $order->state = 'paid';
            $order->paid_amount = $totalAmount;
            $order->paid_at = $result['send_pay_date'] ?? $date;
            $order->save();

            // 保存余额
            $this->addBalance($order->user_id, $data);
        }

    }

    /**
     * @param $notify
     * @return void
     * @throws BusinessException
     */
    protected function dealWechatOrder($notify)
    {
        $ciphertext = $notify['resource']['ciphertext'];
        $orderId = $ciphertext['out_trade_no'];
        $order = AiOrder::where('order_id', $orderId)->first();
        if (!$order) {
            throw new BusinessException("订单{$orderId}不存在: $orderId");
        }

        if ($ciphertext['trade_state'] !== 'SUCCESS') {
            throw new BusinessException("订单状态错误 订单状态{$ciphertext['trade_state']}");
        }
        $totalAmount = $ciphertext['amount']['payer_total'];
        if ($totalAmount < $order->total_amount * 100) {
            throw new BusinessException("订单金额错误 待支付金额{$order->total_amount}分 实际金额{$totalAmount}分");
        }
        $data = json_decode($order->data, true);
        $data['transaction_id'] = $ciphertext['transaction_id'];
        $order->data = $data;
        $paidAt = date('Y-m-d H:i:s', strtotime($ciphertext['success_time']));

        if (!$order->paid_amount) {
            // 更新订单
            $order->state = 'paid';
            $order->paid_amount = $totalAmount/100;
            $order->paid_at = $paidAt;
            $order->save();

            // 保存余额
            $this->addBalance($order->user_id, $data);
        }

    }
    
    protected function addBalance($userId, $data)
    {
        $plans = Plan::getSetting();
        $user = AiUser::where('user_id', $userId)->first();
        if (!$user) {
            $user = new AiUser();
            $user->user_id = $userId;
            $user->expired_at = null;
        }
        $months = $data['months'];
        $hasExpired = !$user->expired_at || strtotime($user->expired_at) < time();
        if (!$hasExpired) {
            $startDate = $user->expired_at;
        } else {
            $startDate = date('Y-m-d H:i:s');
        }
        $startTimestamp = strtotime($startDate);
        $expirationTimestamp = strtotime("+" . $months . " months", $startTimestamp);

        $plan = $data['plan'];
        $user->expired_at = date("Y-m-d H:i:s", $expirationTimestamp);
        $user->available_gpt3 =  ($hasExpired ? 0 : $user->available_gpt3) + ($plans[$plan]['gpt3'] ?? 0);
        $user->available_gpt4 = ($hasExpired ? 0: $user->available_gpt4) + ($plans[$plan]['gpt4'] ?? 0);
        $user->available_midjourney = ($hasExpired ? 0 : $user->available_midjourney) + ($plans[$plan]['midjourney'] ?? 0);
        $user->save();
    }

}
