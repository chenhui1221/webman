<?php

namespace plugin\ai\app\controller;

use Exception;
use plugin\ai\app\model\AiMessage;
use plugin\ai\app\service\SensitiveWord;
use plugin\ai\app\service\Common;
use plugin\ai\app\service\Setting;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Protocols\Http\Chunk;
use Workerman\Protocols\Ws;

/**
 * ChaGPT消息模块
 */
class MessageController extends Base
{

    /**
     * 不需要登录的方法
     *
     * @var string[]
     */
    protected $noNeedLogin = ['send', 'translate'];

    /**
     * 发消息
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function send(Request $request): ?Response
    {
        $messages = $request->post('messages');
        if (!$messages) {
            return json(['error' => ['message' => 'AI已经升级，请刷新页面继续使用']]);
        }
        $firstMessage = current($messages);
        $checkMessages = [];
        if ($firstMessage['role'] === 'system') {
            $checkMessages = [$firstMessage];
        }
        if (!$checkMessages || count($messages) > 1) {
            $checkMessages[] = array_slice($request->post('messages'), -1);
        }
        $json = json_encode($checkMessages, JSON_UNESCAPED_UNICODE);
        if (!SensitiveWord::contentSafe($json)) {
            return json(['error' => ['message' => '出于政策隐私和安全的考虑，我们无法提供相关信息']]);
        }
        $model = $request->post('model');
        $modelType = Common::getModelType($model);
        // 传了Authorization代表用户使用了自己的apikey则不做余额扣除
        if (!in_array($modelType, ['gpt3', 'gpt4', 'dalle']) || !$request->header('Authorization')) {
            if ($error = static::tryReduceBalance($modelType, $isVip)) {
                return json(['error' => ['message' => $error]]);
            }
        }

        $content = last($messages)['content'] ?? '';
        $userMessageId = $request->post('user_message_id');
        $assistantMessageId = $request->post('assistant_message_id');
        $roleId = $request->post('role_id');
        $chatId = $request->post('chat_id');
        $userId = session('user.id') ?? session('user.uid');
        $sessionId = $request->sessionId();
        $realIp = $request->getRealIp();
        $this->saveMessage($userId, $sessionId, $userMessageId, $roleId, 'user', $content, $realIp, $model, $chatId);
        $stream = $request->post('stream');
        $connection = $request->connection;
        $temperature = $request->post('temperature');

        return $this->chat([
            'model' => $model,
            'temperature' => $temperature,
            'messages' => $messages,
            'stream' => $stream,
            'request' => $request,
            'connection' => $connection
        ], function ($responseText) use ($userId, $sessionId, $assistantMessageId, $roleId, $realIp, $model, $chatId) {
            $this->saveMessage($userId, $sessionId, $assistantMessageId, $roleId, 'assistant',
                $responseText, $realIp, $model, $chatId);
        });
    }

    /**
     * 翻译
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function translate(Request $request): ?Response
    {
        $content = last($request->post('messages'))['content'] ?? '';
        if (!$content) {
            return json(['error' => ['message' => '内容为空']]);
        }
        if (!SensitiveWord::contentSafe($content)) {
            return json(['error' => ['message' => '出于政策隐私和安全的考虑，我们无法提供相关信息']]);
        }
        if ($request->post('model')) {
            return json(['error' => ['message' => '系统已经升级，请刷新页面']]);
        }
        $setting = Setting::getSetting();
        $model = $setting['translate_model'] ?? 'gpt-3.5-turbo';
        $prompt = $setting['translate_prompt'] ?? '请将以下内容转换成英文';

        $userMessageId = $request->post('user_message_id');
        $assistantMessageId = $request->post('assistant_message_id');
        $roleId = $request->post('role_id');
        $chatId = $request->post('chat_id');
        $userId = session('user.id') ?? session('user.uid');
        $sessionId = $request->sessionId();
        $realIp = $request->getRealIp();
        $this->saveMessage($userId, $sessionId, $userMessageId, $roleId, 'user', $content, $realIp, $model, $chatId);
        return $this->chat([
            'model' => $model,
            'temperature' => 0.1,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
                ['role' => 'assistant', 'content' => '好的，请发送要转换的内容'],
                ['role' => 'user', 'content' => $content]
            ],
            'stream' => $request->post('stream'),
            'request' => $request,
            'connection' => $request->connection
        ], function ($responseText) use ($userId, $sessionId, $assistantMessageId, $roleId, $realIp, $model, $chatId) {
            $this->saveMessage($userId, $sessionId, $assistantMessageId, $roleId, 'assistant',
                $responseText, $realIp, $model, $chatId);
        });
    }


    /**
     * 聊天
     * @param $options
     * @param null $callback
     * @return Response
     * @throws BusinessException
     */
    protected function chat($options, $callback = null): Response
    {
        // chatgpt 配置
        $model = $options['model'];
        $modelType = Common::getModelType($model);
        $temperature = max(0.1, (float)($options['temperature']??0.1));
        $messages = $options['messages'];
        $stream = (bool)($options['stream']??false);
        $request = $options['request']??null;
        $connection = $options['connection'];

        $handler = "\\plugin\\ai\\app\\modelHandler\\" . ucfirst($modelType);
        $handler = new $handler($request);

        // 向 chatgpt api 发送数据
        [$schema, $address] = $handler->getApi();
        $con = new AsyncTcpConnection("tcp://$address", ['ssl' => [
            'verify_peer' => false,
        ]]);
        $con->transport = in_array($schema, ['wss', 'https']) ? 'ssl' : 'tcp';
        if ($isWebsocket = in_array($schema, ['wss', 'ws'])) {
            $con->protocol = Ws::class;
        }

        $buffer = $handler->buildData($model, $messages, $temperature, $stream);
        echo $buffer;
        $con->send($buffer);
        $con->buffer = '';
        // 失败时
        $con->onError = function ($con, $code, $msg) use ($connection) {
            echo $code, $msg;
            $con->buffer = $msg;
            if ($connection) {
                $connection->send(new Chunk(json_encode(['error' => ['message' => $msg]])));
                $connection->send(new Chunk(''));
            }
        };
        // api接口返回数据时
        $con->onMessage = function ($con, $buffer) use ($connection, $handler, $isWebsocket) {
            static $headerCompleted = false, $header = '';
            if (!$isWebsocket && !$headerCompleted) {
                $header .= $buffer;
                if (!$position = strpos($header, "\r\n\r\n")) {
                    return;
                }
                $bodyLength = 0;
                if (preg_match("/Content-Length: (\d+)\r\n/i", $header, $match)) {
                    $bodyLength = $match[1];
                }
                if(!$buffer = substr($header, $position + 4)) {
                    return;
                }
                $headerCompleted = true;
                if ($bodyLength) {
                    $con->buffer .= $buffer;
                    if ($connection) {
                        $connection->send(new Chunk($buffer));
                        $connection->send(new Chunk(''));
                    }
                    return;
                }
            }
            $buffer = $isWebsocket ? "data: $buffer\n" : $buffer;
            $con->buffer .= $buffer;
            if ($connection) {
                if ($isWebsocket) {
                    $connection->send(new Chunk($buffer));
                } else {
                    $connection->send($buffer, true);
                }
            }
        };
        $con->onClose = function ($con) use ($callback, $model, $handler, $isWebsocket, $connection) {
            if ($connection && $isWebsocket) {
                $connection->send(new Chunk(''));
            }
            if ($con->buffer && $callback) {
                call_user_func($callback, $handler->formatResponse($con->buffer));
            }
        };
        $con->connect();
        // 向浏览器发送头部响应
        return response("\n")->withHeaders([
            "Content-Type" => "application/octet-stream",
            "Transfer-Encoding" => "chunked",
        ]);
    }

    /**
     * 保存消息
     * @param $userId
     * @param $sessionId
     * @param $userMessageId
     * @param $roleId
     * @param $role
     * @param $content
     * @param $remoteIp
     * @param $model
     * @param null $chatId
     * @return void
     */
    protected function saveMessage($userId, $sessionId, $userMessageId, $roleId, $role, $content, $remoteIp, $model, $chatId = null)
    {
        $aiMessage = new AiMessage();
        $aiMessage->user_id = $userId;
        $aiMessage->session_id = $sessionId;
        $aiMessage->message_id = $userMessageId;
        $aiMessage->role_id = $roleId;
        $aiMessage->role = $role;
        $aiMessage->content = $content;
        $aiMessage->ip = $remoteIp;
        $aiMessage->model = $model;
        $aiMessage->chat_id = $chatId;
        $aiMessage->save();
    }

}