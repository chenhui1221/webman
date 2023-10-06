<?php

namespace plugin\ai\app\controller;

use Exception;
use plugin\ai\app\model\AiMessage;
use plugin\ai\app\service\Midjourney;
use plugin\ai\app\service\SensitiveWord;
use support\Db;
use support\Log;
use support\Request;
use support\Response;
use Throwable;
use Webman\Push\Api;
use Webman\Push\PushException;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Protocols\Http\Chunk;

/**
 * midjourney画图模块
 */
class MidjourneyController extends Base
{

    /**
     * 不需要登录的方法
     *
     * @var string[]
     */
    protected $noNeedLogin = ['imagine', 'notify', 'status', 'change'];

    /**
     * 作图
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function imagine(Request $request): ?Response
    {
        if ($error = static::tryReduceBalance('midjourney')) {
            return json(['error' => ['message' => $error]]);
        }
        $messages = $request->post('messages');
        if (!$messages) {
            return json(['error' => ['message' => "请输入图片描述"]]);
        }
        $prompt = last($messages)['content'] ?? '';
        if (!$prompt) {
            return json(['error' => ['message' => '请输入图片描述']]);
        }
        if (!SensitiveWord::contentSafe($prompt)) {
            return json(['error' => ['message' => '出于政策隐私和安全的考虑，我们无法提供相关信息']]);
        }

        $userMessageId = $request->post('user_message_id');
        $assistantMessageId = $request->post('assistant_message_id');
        $rawPrompt = $request->post('raw_prompt', $prompt);
        $roleId = $request->post('role_id');
        $userId = session('user.id') || session('user.uid');
        $sessionId = $request->sessionId();
        $remoteIp = $request->getRealIp();
        $aiMessage = new AiMessage();
        $aiMessage->user_id = $userId;
        $aiMessage->session_id = $sessionId;
        $aiMessage->message_id = $userMessageId;
        $aiMessage->role_id = $roleId;
        $aiMessage->role = 'user';
        $aiMessage->content = $rawPrompt;
        $aiMessage->ip = $remoteIp;
        $aiMessage->model = 'midjourney';
        $aiMessage->save();

        $url = $request->post('url');
        if ($url) {
            $prompt = "$url $prompt";
        }

        $base64 = $this->getImageBase64($prompt, $request);

        // 设置API URL和参数
        $data = array(
            'base64' => $base64[0] ?? '',
            'notifyHook' => $request->header('X-Forwarded-Proto', 'http') . '://' . $request->host() . '/app/ai/midjourney/notify',
            'prompt' => $prompt,
            'state' => $request->session()->getId(),
        );

        $host = Midjourney::getSetting('api_host');
        // 向 chatgpt api 发送数据
        [$schema, $host] = explode('://', $host, 2);
        $con = new AsyncTcpConnection("tcp://$host", ['ssl' => [
            'verify_peer' => false,
        ]]);
        $con->transport = $schema === 'https' ? 'ssl' : 'tcp';
        $body = json_encode($data);
        $bodyLength = strlen($body);
        $con->send(
            "POST /mj/submit/imagine HTTP/1.1\r\n" .
            "Host: $host\r\n" .
            "Connection: close\r\n" .
            "Content-Type: application/json\r\n" .
            "Request-Origion: Knife4j\r\n" .
            "knife4j-gateway-code: mj\r\n" .
            "Content-Length: $bodyLength\r\n\r\n" .
            $body
        );
        // 获取浏览器链接
        $connection = $request->connection;
        // 失败时
        $con->onError = function ($con, $code, $msg) use ($connection) {
            $connection->send(new Chunk(json_encode(['error' => ['message' => $msg]])));
            $connection->send(new Chunk(''));
        };
        $con->buffer = '';
        // api接口返回数据时
        $con->onMessage = function ($con, $buffer) use ($connection, $request) {
            static $headerCompleted = false, $header = '';
            if (!$headerCompleted) {
                $header .= $buffer;
                if (!$position = strpos($header, "\r\n\r\n")) {
                    return;
                }
                $headerCompleted = true;
                if(!$buffer = substr($header, $position + 4)) {
                    return;
                }
            }
            $con->buffer .= $buffer;
            $connection->send($buffer, true);
        };
        $con->onClose = function ($con) use ($assistantMessageId, $userId, $sessionId, $roleId, $remoteIp) {
            if ($con->buffer) {
                $content = $this->formatContent($con->buffer);
                $json = json_decode($content, true);
                $aiMessage = new AiMessage;
                $aiMessage->user_id = $userId;
                $aiMessage->session_id = $sessionId;
                $aiMessage->message_id = $json['result'] ?? $assistantMessageId;
                $aiMessage->role_id = $roleId;
                $aiMessage->role = 'assistant';
                $aiMessage->content = $content;
                $aiMessage->ip = $remoteIp;
                $aiMessage->model = 'midjourney';
                $aiMessage->save();
            }
        };
        $con->connect();
        // 向浏览器发送头部响应
        return response("\n")->withHeaders([
            "Content-Type" => "application/octet-stream",
            "Transfer-Encoding" => "chunked",
        ]);
    }

    protected function formatContent($content)
    {
        if (preg_match('/\{.*\}/', $content, $match)) {
            return $match[0];
        }
        return $content;
    }

    /**
     * 选图或者便变换
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function change(Request $request)
    {
        $index = $request->post('index', 1);
        $taskId = $request->post('taskId');
        $action = $request->post('action');
        if (!$action || !$taskId) {
            return json(['error' => ['message' => "参数错误"]]);
        }

        // 选择图片UPSCALE不消耗余额，REROLL消耗余额
        if (strtoupper($action) === 'REROLL' && $error = static::tryReduceBalance('midjourney')) {
            return json(['error' => ['message' => $error]]);
        }

        // 设置API URL和参数
        $data = [
            'action' => $action,
            'index' => $index,
            'notifyHook' => $request->header('X-Forwarded-Proto', 'http') . '://' . $request->host() . '/app/ai/midjourney/notify',
            'taskId' => $taskId,
            'state' => $request->session()->getId(),
        ];

        $host = Midjourney::getSetting('api_host');
        // 向 chatgpt api 发送数据
        [$schema, $host] = explode('://', $host, 2);
        $con = new AsyncTcpConnection("tcp://$host", ['ssl' => [
            'verify_peer' => false,
        ]]);
        $con->transport = $schema === 'https' ? 'ssl' : 'tcp';
        $body = json_encode($data);
        $bodyLength = strlen($body);
        $con->send(
            "POST /mj/submit/change HTTP/1.1\r\n" .
            "Host: $host\r\n" .
            "Connection: close\r\n" .
            "Content-Type: application/json\r\n" .
            "Request-Origion: Knife4j\r\n" .
            "knife4j-gateway-code: mj\r\n" .
            "Content-Length: $bodyLength\r\n\r\n" .
            $body
        );
        // 获取浏览器链接
        $connection = $request->connection;
        // 失败时
        $con->onError = function ($con, $code, $msg) use ($connection) {
            $connection->send(new Chunk(json_encode(['error' => ['message' => $msg]])));
            $connection->send(new Chunk(''));
        };
        // api接口返回数据时
        $con->onMessage = function ($con, $buffer) use ($connection, $request) {
            static $headerCompleted = false, $header = '';
            if (!$headerCompleted) {
                $header .= $buffer;
                if (!$position = strpos($header, "\r\n\r\n")) {
                    return;
                }
                $headerCompleted = true;
                if(!$buffer = substr($header, $position + 4)) {
                    return;
                }
            }
            $connection->send($buffer, true);
        };
        $con->connect();
        // 向浏览器发送头部响应
        return response("\n")->withHeaders([
            "Content-Type" => "application/octet-stream",
            "Transfer-Encoding" => "chunked",
        ]);
    }

    /**
     * 通过url获取图片数据，用作垫图
     * @param $prompt
     * @param $request
     * @return array|Response
     */
    protected function getImageBase64(&$prompt, $request)
    {
        $host = $request->host();
        $base64 = [];
        $pattern = '/(http:\/\/|https:\/\/)' . preg_quote($host, '/') . '\S*/';
        preg_match_all($pattern, $prompt, $matches);
        if (empty($matches[0]) || count($matches[0]) > 1) {
            return [];
        }
        foreach ($matches[0] as $url) {
            // 获取图片位置
            $path = parse_url($url, PHP_URL_PATH);
            // 过滤掉 ../../password类似的请求
            if (strpos($path, '/..') !== false) {
                return json(['error' => ['message' => "url非法"]]);
            }
            if (strpos($path, '/app/ai/') !== false) {
                $path = '/plugin/ai/public/' . substr($path, 8);
            }
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $allowedExtensions = array("png", "jpg", "jpeg");
            if (!in_array($extension, $allowedExtensions)) {
                continue;
            }
            clearstatcache();
            $path = base_path($path);
            if (is_file($path)) {
                $base64[] = 'data:image/png;base64,' . base64_encode(file_get_contents($path));
                $prompt = str_replace($url, '', $prompt);
            }
        }
        return $base64;
    }

    /**
     * 接收midjourney_porxy代理发来的通知
     * @param Request $request
     * @return Response
     * @throws PushException
     */
    public function notify(Request $request): Response
    {
        if (isset($post['progress']) && $post['progress'] === '100%' &&
            $aiMessage = AiMessage::where('message_id', $post['id'])->first()) {
            $aiMessage->content = $post['imageUrl'];
            $aiMessage->save();
        }
        try {
            $api = new Api(
                'http://127.0.0.1:3232',
                config('plugin.webman.push.app.app_key'),
                config('plugin.webman.push.app.app_secret')
            );
            $post = $request->post();
            unset($post['properties']);
            $api->trigger($post['state'], 'mj-state-change', $post);
        } catch (Throwable $e) {}
        return \response('ok');
    }

    /**
     * 获取作图任务状态
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function status(Request $request)
    {
        $taskId = $request->get('taskId');
        if (!$taskId) {
            return $this->json(1, "缺少taskId");
        }

        $host = Midjourney::getSetting('api_host');
        // 向 chatgpt api 发送数据
        [$schema, $host] = explode('://', $host, 2);
        $con = new AsyncTcpConnection("tcp://$host", ['ssl' => [
            'verify_peer' => false,
        ]]);
        $con->transport = $schema === 'https' ? 'ssl' : 'tcp';
        $con->send(
            "GET /mj/task/$taskId/fetch HTTP/1.1\r\n" .
            "Host: $host\r\n" .
            "Connection: close\r\n" .
            "Content-Type: application/json\r\n" .
            "Request-Origion: Knife4j\r\n" .
            "knife4j-gateway-code: mj\r\n" .
            "Content-Length: 0\r\n\r\n"
        );
        // 获取浏览器链接
        $connection = $request->connection;
        // 失败时
        $con->onError = function ($con, $code, $msg) use ($connection) {
            $connection->send(new Chunk(json_encode(['error' => ['message' => $msg]])));
            $connection->send(new Chunk(''));
        };
        $con->buffer = '';
        // api接口返回数据时
        $con->onMessage = function ($con, $buffer) use ($connection, $request) {
            static $headerCompleted = false, $header = '';
            if (!$headerCompleted) {
                $header .= $buffer;
                if (!$position = strpos($header, "\r\n\r\n")) {
                    return;
                }
                $headerCompleted = true;
                if(!$buffer = substr($header, $position + 4)) {
                    return;
                }
            }
            $con->buffer .= $buffer;
            $connection->send($buffer, true);
        };
        $con->onClose = function ($con) {
            if ($con->buffer) {
                $content = $this->formatContent($con->buffer);
                $json = json_decode($content, true);
                if (isset($json['progress']) && $json['progress'] === '100%' &&
                    $aiMessage = AiMessage::where('message_id', $json['id'])->first()) {
                    $aiMessage->content = $json['imageUrl'];
                    $aiMessage->save();
                }
            }
        };

        $con->connect();
        // 向浏览器发送头部响应
        return response("\n")->withHeaders([
            "Content-Type" => "application/octet-stream",
            "Transfer-Encoding" => "chunked",
        ]);
    }

}