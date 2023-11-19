<?php

namespace plugin\ai\app\modelHandler;


use support\exception\BusinessException;

class Qwen extends Base
{

    protected $api = 'https://dashscope.aliyuncs.com';

    protected $path = '/api/v1/services/aigc/text-generation/generation';

    /**
     * @param $model
     * @param $messages
     * @param $temperature
     * @param $stream
     * @return string
     * @throws BusinessException
     */
    public function buildData($model, $messages, $temperature, $stream): string
    {
        $path = $this->path;
        [$schema, $host] = $this->getApi();
        $this->apikey = \plugin\ai\app\service\Qwen::getSetting('apikey');
        if (!$this->apikey) {
            throw new BusinessException('阿里灵积未设置apikey');
        }
        $data = array(
            'model' => $model,
            'parameters' => [
                'temperature' => $temperature,
            ],
            'input' => [
                'messages' => $messages
            ]
        );
        $body = json_encode($data);
        $bodyLength = strlen($body);
        $streamHeader = $stream ? "Accept: text/event-stream\r\n" : '';
        return "POST $path HTTP/1.1\r\n" .
            "Host: $host\r\n" .
            "Connection: close\r\n" .
            "Content-Type: application/json\r\n" .
            "Authorization: Bearer $this->apikey\r\n" .
            $streamHeader .
            "Content-Length: $bodyLength\r\n\r\n" .
            $body;
    }

    public function formatResponse($buffer)
    {
        foreach (array_reverse(explode("\n", $buffer)) as $chunk) {
            if (preg_match('/data:/', $chunk)) {
                $json = json_decode(substr($chunk, 5), true);
                if (isset($json['output']['text'])) {
                    return $json['output']['text'];
                }
            }
        }
        return $buffer;
    }

}