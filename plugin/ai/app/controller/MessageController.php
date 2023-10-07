<?php

namespace plugin\ai\app\controller;

use Exception;
use plugin\ai\app\admin\controller\ChatGptController;
use plugin\ai\app\model\AiMessage;
use plugin\ai\app\model\Apikey;
use plugin\ai\app\service\SensitiveWord;
use plugin\ai\app\service\Setting;
use plugin\ai\app\service\ChatGpt;
use plugin\ai\app\service\Common;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Protocols\Http\Chunk;

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
        if (!$request->post('messages')) {
            return json(['error' => ['message' => 'AI已经升级，请刷新页面继续使用']]);
        }

        $json = json_encode(array_slice($request->post('messages'), -1), JSON_UNESCAPED_UNICODE);
        if (!SensitiveWord::contentSafe($json)) {
            return json(['error' => ['message' => '出于政策隐私和安全的考虑，我们无法提供相关信息']]);
        }
        $model = $request->post('model');

        // 传了Authorization代表用户使用了自己的apikey则不做鉴权
        if (!$request->header('Authorization')) {
            if ($error = static::tryReduceBalance($model)) {
                return json(['error' => ['message' => $error]]);
            }
        }

        if ($model === 'dall.e') {
            return $this->dallE($request);
        }
        return $this->chat($request);
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
        return $this->chat($request, [
            ['role' => 'assistant', 'content' => '请把内容中的中文转换为英文'],
            ['role' => 'user', 'content' => $content]
        ]);
    }

    /**
     * 聊天
     * @param Request $request
     * @param array $messages
     * @return Response
     * @throws BusinessException
     */
    protected function chat(Request $request, $messages = []): Response
    {
        // chatgpt 配置
        $chatGptApiHost = ChatGpt::getSetting('api_host');
        $model = $request->post('model');
        $modelType = Common::getModelType($model);
        $apiKey = $this->getApiKey($modelType);
        $maxTokens = (int)$request->post('max_tokens');
        $temperature = (float)$request->post('temperature');
        $keyBelongsUser = (bool)$request->header('Authorization');
        $messages = $messages ?: $request->post('messages', []);
        $userMessageId = $request->post('user_message_id');
        $assistantMessageId = $request->post('assistant_message_id');
        $roleId = $request->post('role_id');
        $userId = session('user.id') ?? session('user.uid');
        $sessionId = $request->sessionId();
        $remoteIp = $request->getRealIp();

        $content = last($messages)['content'];
        $aiMessage = new AiMessage();
        $aiMessage->user_id = $userId;
        $aiMessage->session_id = $sessionId;
        $aiMessage->message_id = $userMessageId;
        $aiMessage->role_id = $roleId;
        $aiMessage->role = 'user';
        $aiMessage->content = $content;
        $aiMessage->ip = $remoteIp;
        $aiMessage->model = $model;
        $aiMessage->save();

        // 设置API URL和参数
        $data = array(
            'model' => $model,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'stream' => (bool)$request->post("stream"),
            'messages' => $messages
        );

        // 向 chatgpt api 发送数据
        [$schema, $host] = explode('://', $chatGptApiHost, 2);
        $con = new AsyncTcpConnection("tcp://$host", ['ssl' => [
            'verify_peer' => false,
        ]]);
        $con->transport = $schema === 'https' ? 'ssl' : 'tcp';
        $body = json_encode($data);
        $bodyLength = strlen($body);
        $con->send(
            "POST /v1/chat/completions HTTP/1.1\r\n" .
            "Host: $host\r\n" .
            "Connection: close\r\n" .
            "Content-Type: application/json\r\n" .
            "Authorization: Bearer $apiKey\r\n" .
            "Content-Length: $bodyLength\r\n\r\n" .
            $body
        );
        $con->buffer = '';
        // 获取浏览器链接
        $connection = $request->connection;
        // 失败时
        $con->onError = function ($con, $code, $msg) use ($connection) {
            $con->buffer = $msg;
            $connection->send(new Chunk(json_encode(['error' => ['message' => $msg]])));
            $connection->send(new Chunk(''));
        };
        // api接口返回数据时
        $con->onMessage = function ($con, $buffer) use ($connection, $keyBelongsUser, $apiKey, $request) {
            static $headerCompleted = false, $header = '';
            if (!$headerCompleted) {
                $header .= $buffer;
                if (!$position = strpos($header, "\r\n\r\n")) {
                    return;
                }
                $bodyLength = 0;
                if (preg_match("/Content-Length: (\d+)\r\n/", $header, $match)) {
                    $bodyLength = $match[1];
                }
                if(!$buffer = substr($header, $position + 4)) {
                    return;
                }
                $headerCompleted = true;
                if ($bodyLength) {
                    $con->buffer .= $buffer;
                    $connection->send(new Chunk($buffer));
                    $connection->send(new Chunk(''));
                    // 记录无法使用的key
                    if (!$keyBelongsUser) {
                        $this->checkApiKeyAvailable($apiKey, $buffer, $request);
                    }
                    return;
                }
            }
            $con->buffer .= $buffer;
            $connection->send($buffer, true);
        };
        $con->onClose = function ($con) use ($assistantMessageId, $userId, $sessionId, $roleId, $remoteIp, $model) {
            if ($con->buffer) {
                $aiMessage = new AiMessage;
                $aiMessage->user_id = $userId;
                $aiMessage->session_id = $sessionId;
                $aiMessage->message_id = $assistantMessageId;
                $aiMessage->role_id = $roleId;
                $aiMessage->role = 'assistant';
                $aiMessage->content = $this->formatContent($con->buffer);
                $aiMessage->ip = $remoteIp;
                $aiMessage->model = $model;
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

    /**
     * 格式化chatgpt返回的数据
     * @param $content
     * @return string
     */
    protected function formatContent($content)
    {
        if (!$content || $content[0] === '{') {
            try {
                $data = json_decode($content, true);
                if (isset($data['data'][0]['url'])) {
                    $content = $data['data'][0]['url'];
                }
            } catch (Throwable $e) {}
            return $content;
        }
        $chunks = explode("\n", $content);
        $content = '';
        foreach ($chunks as $chunk) {
            if ($chunk === "") {
                continue;
            }
            $chunk = trim(substr($chunk, 6));
            if ($chunk === "" || $chunk === "[DONE]") {
                continue;
            }
            try {
                $data = json_decode($chunk, true);
                if (isset($data['error'])) {
                    $content .= $data['error']['message'] ?? "";
                } else {
                    $content .= $data['choices'][0]['delta']['content'] ?? "";
                }
            } catch (Exception $e) {
                echo $e;
            }
        }
        return $content;
    }

    /**
     * dall.E 画图
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    protected function dallE(Request $request): Response
    {
        $apiKey = $this->getApiKey();
        $chatGptApiHost = ChatGpt::getSetting('api_host');
        $keyBelongsUser =  (bool)$request->header('Authorization');
        $messages = $request->post('messages');
        if (!$messages) {
            return json(['error' => ['message' => "请输入图片描述"]]);
        }
        $prompt = last($messages)['content'] ?? '';

        $userMessageId = $request->post('user_message_id');
        $assistantMessageId = $request->post('assistant_message_id');
        $roleId = $request->post('role_id');
        $userId = session('user.id') ?? session('user.uid');
        $sessionId = $request->sessionId();
        $remoteIp = $request->getRealIp();
        $aiMessage = new AiMessage();
        $aiMessage->user_id = $userId;
        $aiMessage->session_id = $sessionId;
        $aiMessage->message_id = $userMessageId;
        $aiMessage->role_id = $roleId;
        $aiMessage->role = 'user';
        $aiMessage->content = $prompt;
        $aiMessage->ip = $remoteIp;
        $aiMessage->model = 'dall.e';
        $aiMessage->save();

        // 设置API URL和参数
        $data = array(
            'prompt' => $prompt,
            'n' => 1,
            'size' => "512x512"
        );

        // 向 chatgpt api 发送数据
        [$schema, $host] = explode('://', $chatGptApiHost, 2);
        $con = new AsyncTcpConnection("tcp://$host", ['ssl' => [
            'verify_peer' => false,
        ]]);
        $con->transport = $schema === 'https' ? 'ssl' : 'tcp';
        $body = json_encode($data);
        $bodyLength = strlen($body);
        $con->send(
            "POST /v1/images/generations HTTP/1.1\r\n" .
            "Host: $host\r\n" .
            "Connection: close\r\n" .
            "Content-Type: application/json\r\n" .
            "Authorization: Bearer $apiKey\r\n" .
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
        $con->onMessage = function ($con, $buffer) use ($connection, $keyBelongsUser, $apiKey, $request) {
            static $headerCompleted = false, $header = '';
            if (!$headerCompleted) {
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
                    $connection->send(new Chunk($buffer));
                    $connection->send(new Chunk(''));
                    // 记录无法使用的key
                    if (!$keyBelongsUser) {
                        $this->checkApiKeyAvailable($apiKey, $buffer, $request);
                    }
                    return;
                }
            }
            $con->buffer .= $buffer;
            $connection->send($buffer, true);
        };
        $con->onClose = function ($con) use ($assistantMessageId, $userId, $sessionId, $roleId, $remoteIp) {
            if ($con->buffer) {
                $aiMessage = new AiMessage;
                $aiMessage->user_id = $userId;
                $aiMessage->session_id = $sessionId;
                $aiMessage->message_id = $assistantMessageId;
                $aiMessage->role_id = $roleId;
                $aiMessage->role = 'assistant';
                $aiMessage->content = $this->formatContent($con->buffer);
                $aiMessage->ip = $remoteIp;
                $aiMessage->model = 'dall.e';
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

    /**
     * 获取apikey
     *
     * @return mixed
     */
    protected function getApiKey($modelType = 'gpt3')
    {
        $request = \request();
        if ($authorization = $request->header('Authorization')) {
            return explode(' ', $authorization)[1] ?? '';
        }
        return $this->getApiKeyFromDb($modelType);
    }

    /**
     * 从数据库中获取一条可用apikey
     *
     * @return string
     */
    protected function getApiKeyFromDb($modelType = 'gpt3')
    {
        try {
            $where = ['state'=> 0, 'suspended' => 0];
            if ($modelType === 'gpt4') {
                $where['gpt4'] = 1;
            }
            $item = Apikey::where($where)->orderBy('last_message_at')->orderBy('id')->first();
            if (!$item) {
                unset($where['suspended']);
                $item = Apikey::where($where)->orderBy('last_message_at')->orderBy('id')->first();
                if (!$item) {
                    return '';
                }
            }
            $item->last_message_at = date('Y-m-d H:i:s');
            $item->message_count++;
            $item->save();
            return $item->apikey ?: '';
        } catch (Throwable $exception) {}
        return '';
    }

    /**
     * 通过关键字检测apikey是否可用
     *
     * @param $apiKey
     * @param $errorMessage
     * @param $request
     * @return void
     */
    protected function checkApiKeyAvailable($apiKey, $errorMessage, $request)
    {
        // 没有发生错误则返回
        if (!static::dbEnabled() || !strpos($errorMessage, '"error"')) {
            return;
        }
        // 账号被禁用关键字
        $unavailableKeyWords = ['account_deactivated', 'billing_not_active', 'invalid_api_key', 'insufficient_quota'];
        $disabled = false;
        foreach($unavailableKeyWords as $word) {
            if (strpos($errorMessage, $word) !== false) {
                $disabled = true;
                break;
            }
        }
        // 查找apikey的记录
        $item = Apikey::where('apikey', $apiKey)->first();
        if ($item) {
            // 标记不可用
            if ($disabled) {
                $item->state = 1;
                $item->suspended = 1;
            }
            // 记录错误相关信息
            $item->last_error = $errorMessage;
            $item->error_count = $item->error_count + 1;
            $item->save();
        }
    }

}
