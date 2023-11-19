<?php

namespace plugin\ai\app\modelHandler;


use support\exception\BusinessException;

class Spark extends Base
{

    protected $setting = [];

    /**
     * @throws BusinessException
     */
    public function __construct()
    {
        $this->setting = \plugin\ai\app\service\Spark::getSetting();
        $version = $this->setting['version'];
        $addr = "wss://spark-api.xf-yun.com/v{$version}.1/chat";
        $apiKey = $this->setting['apikey'];
        $apiSecret = $this->setting['secret_key'];
        $appid = $this->setting['appid'];
        if (!$apiSecret || !$apiKey || !$appid) {
            throw new BusinessException('讯飞星火配置不正确');
        }

        $ul = parse_url($addr);
        $rfc1123_format = gmdate("D, d M Y H:i:s \G\M\T", time());
        $signString = array("host: " . $ul["host"], "date: " . $rfc1123_format,  "GET " . $ul["path"] . " HTTP/1.1");
        $sign = implode("\n", $signString);
        $sha = hash_hmac('sha256', $sign, $apiSecret,true);
        $signature_sha_base64 = base64_encode($sha);
        $authUrl = "api_key=\"$apiKey\", algorithm=\"hmac-sha256\", headers=\"host date request-line\", signature=\"$signature_sha_base64\"";
        $authUrlBase64 = base64_encode($authUrl);
        $authAddr = $addr . '?' . http_build_query([
                'authorization' => $authUrlBase64,
                'date' => $rfc1123_format,
                'host' => $ul['host']
            ]);
        $this->api = $authAddr;
    }

    public function buildData($model, $messages, $temperature, $stream)
    {
        $appid = $this->setting['appid'];
        $header = [
            "app_id" => $appid,
            "uid" => "12345"
        ];

        $version = $this->setting['version'];
        $domain = $version != 1 ? "generalv$version" : "general";
        $parameter = [
            "chat" => [
                "domain" => $domain,
                "temperature" => $temperature,
                //"max_tokens" => 1024
            ]
        ];

        $payload = [
            "message" => [
                "text" => $messages
            ]
        ];

        return json_encode([
            "header" => $header,
            "parameter" => $parameter,
            "payload" => $payload
        ]);
    }

    public function formatResponse($buffer): string
    {
        $chunks = explode("\n", $buffer);
        $content = '';
        foreach ($chunks as $chunk) {
            if ($chunk === "") {
                continue;
            }
            $chunk = trim(substr($chunk, 6));
            try {
                $data = json_decode($chunk, true);
                if (!empty($data['header']['code'])) {
                    $content .= $data['header']['message'] ?? "";
                } else {
                    $content .= $data['payload']['choices']['text'][0]['content'] ?? "";
                }
            } catch (Throwable $e) {
                echo $e;
            }
        }
        return $content;
    }

}