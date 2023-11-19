<?php

namespace plugin\ai\app\modelHandler;


use plugin\ai\app\service\ChatGpt;

class Dalle extends Gpt3
{
    protected $path = '/v1/images/generations';
    public function __construct()
    {
        $this->api = ChatGpt::getSetting('dalle_api_host');
    }

    public function buildData($model, $messages, $temperature, $stream)
    {
        $path = $this->path;
        [$schema, $host] = $this->getApi();
        $apiKey = $this->getApiKey('gpt3');
        $prompt = last($messages)['content'] ?? '';
        $data = array(
            'prompt' => $prompt,
            'n' => 1,
            'size' => "512x512"
        );
        $body = json_encode($data);
        $bodyLength = strlen($body);
        return "POST $path HTTP/1.1\r\n" .
            "Host: $host\r\n" .
            "Connection: close\r\n" .
            "Content-Type: application/json\r\n" .
            "Authorization: Bearer $apiKey\r\n" .
            "Content-Length: $bodyLength\r\n\r\n" .
            $body;
    }

    public function formatResponse($buffer)
    {
        if ($buffer && $buffer[0] === '{') {
            try {
                $data = json_decode($buffer, true);
                if (isset($data['data'][0]['url'])) {
                    return $data['data'][0]['url'];
                }
            } catch (Throwable $e) {}
        }
        return $buffer;
    }
}