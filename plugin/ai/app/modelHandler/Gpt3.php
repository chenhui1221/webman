<?php

namespace plugin\ai\app\modelHandler;


use plugin\ai\app\model\Apikey;
use plugin\ai\app\service\ChatGpt;
use Throwable;

class Gpt3 extends Base
{
    protected $isUserApikey = false;

    public function __construct($request = null)
    {
        $this->api = ChatGpt::getSetting('gpt3_api_host');
        if ($request && $authorization = $request->header('Authorization')) {
            $this->apikey = explode(' ', $authorization)[1] ?? '';
            $this->isUserApikey = true;
        }
    }

    public function buildData($model, $messages, $temperature, $stream)
    {
        $path = $this->path;
        [$schema, $host] = $this->getApi();
        $apiKey = $this->getApiKey('gpt3');
        $data = array(
            'model' => $model,
            'temperature' => $temperature,
            'stream' => (bool)$stream,
            'messages' => $messages
        );
        $body = json_encode($data);
        $bodyLength = strlen($body);
        return "POST $path HTTP/1.1\r\n" .
            "Host: $host\r\n" .
            "Connection: close\r\n" .
            "Content-Type: application/json\r\n" .
            "Authorization: Bearer $apiKey\r\n" .
            "Accept: text/event-stream\r\n" .
            "Content-Length: $bodyLength\r\n\r\n" .
            $body;
    }

    public function formatResponse($buffer)
    {
        if ($buffer && $buffer[0] === '{') {
            if (!$this->isUserApikey) {
                $this->checkApiKeyAvailable($buffer);
            }
            return $buffer;
        }
        $chunks = explode("\n", $buffer);
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
            } catch (Throwable $e) {
                echo $e;
            }
        }
        return $content;
    }

    /**
     * 获取apikey
     *
     * @return mixed
     */
    public function getApiKey()
    {
        if ($this->apikey) {
            return $this->apikey;
        }
        $request = \request();
        if ($request && $authorization = $request->header('Authorization')) {
            $this->apikey = explode(' ', $authorization)[1] ?? '';
        } else {
            $this->apikey = $this->getApiKeyFromDb('gpt3');
        }
        return $this->apikey;
    }

    /**
     * 从数据库中获取一条可用apikey
     *
     * @return string
     */
    public function getApiKeyFromDb($modelType = 'gpt3')
    {
        try {
            $where = ['state'=> 0, 'suspended' => 0];
            if ($modelType === 'gpt4') {
                $where['gpt4'] = 1;
            }
            $unavailableApiKey = strpos($this->api, 'ai.fakeopen.com') ? '' :  'pk-this-is-a-real-free-pool-token-for-everyone';
            $item = Apikey::where($where)->where('apikey', '<>', $unavailableApiKey)->orderBy('last_message_at')->orderBy('id')->first();
            if (!$item) {
                unset($where['suspended']);
                $item = Apikey::where($where)->where('apikey', '<>', $unavailableApiKey)->orderBy('last_message_at')->orderBy('id')->first();
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
     * @param $errorMessage
     * @return void
     */
    protected function checkApiKeyAvailable($errorMessage)
    {
        if (!strpos($errorMessage, 'error') || $this->apikey === 'pk-this-is-a-real-free-pool-token-for-everyone') {
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
        $item = Apikey::where('apikey', $this->apikey)->first();
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