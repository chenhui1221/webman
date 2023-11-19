<?php

namespace plugin\ai\app\modelHandler;

use Firebase\JWT\JWT;
use plugin\ai\app\service\Common;
use support\exception\BusinessException;

class Chatglm extends Base
{
    protected $api = 'https://open.bigmodel.cn';

    protected $path = '';

    protected $setting = [];


    public function __construct()
    {
        $this->setting = \plugin\ai\app\service\Chatglm::getSetting();
        $this->path = "/api/paas/v3/model-api/{$this->setting['version']}/sse-invoke";
    }

    /**
     * @throws BusinessException
     */
    public function buildData($model, $messages, $temperature, $stream)
    {
        extract(parse_url($this->api));

        [$appId, $apiKey] = explode('.', $this->setting['apikey']);

        if (!$apiKey) {
            throw new BusinessException('清华智普配置不正确');
        }

        $timestampMs = ceil(microtime(true) * 1000);
        $token = static::JwtEncode([
            'api_key'   => $appId,
            'exp'       => $timestampMs + 2 * 24 * 60 * 60 * 1000,
            'timestamp' => $timestampMs
        ], $apiKey, 'HS256', null, [
            'alg'       => 'HS256',
            'sign_type' => 'SIGN',
        ]);

        $data = [
            'model'         => $model,
            'prompt'        => $messages,
            'temperature'   => $temperature,
            'return_type'   => 'text'
        ];

        $body = json_encode($data);

        $bodyLength = strlen($body);
        $streamHeader = $stream ? "Accept: text/event-stream\r\n" : '';

        return "POST $this->path HTTP/1.1\r\n" .
            "Host: $host\r\n" .
            "Connection: close\r\n" .
            "Content-Type: application/json\r\n" .
            "Authorization: Bearer $token\r\n" .
            $streamHeader .
            "Content-Length: $bodyLength\r\n\r\n" .
            $body;
    }

    public static function urlSafeB64Encode(string $input): string
    {
        return \str_replace('=', '', \strtr(\base64_encode($input), '+/', '-_'));
    }

    public static function JwtEncode(
        array $payload,
              $key,
        string $alg,
        string $keyId = null,
        array $head = null
    ): string {
        $header = ['typ' => 'JWT', 'alg' => $alg];
        if ($keyId !== null) {
            $header['kid'] = $keyId;
        }
        if (isset($head) && \is_array($head)) {
            $header = \array_merge($head, $header);
        }
        $segments = [];
        $segments[] = static::urlsafeB64Encode(static::jsonEncode($header));
        $segments[] = static::urlsafeB64Encode(static::jsonEncode($payload));
        $signing_input = \implode('.', $segments);
        $signature = \hash_hmac('SHA256', $signing_input, $key, true);
        $segments[] = static::urlsafeB64Encode($signature);
        return \implode('.', $segments);
    }

    public static function jsonEncode(array $input): string
    {
        $json = \json_encode($input, \JSON_UNESCAPED_SLASHES);
        if ($errno = \json_last_error()) {
            throw new BusinessException($errno);
        } elseif ($json === 'null') {
            throw new BusinessException('Null result with non-null input');
        }
        if ($json === false) {
            throw new BusinessException('Provided object could not be encoded to valid JSON');
        }
        return $json;
    }

    public function formatResponse($buffer): string
    {
        $buffer = Common::decodeChunked($buffer);
        if ($buffer && $buffer[0] === '{') {
            return $buffer;
        }
        $thunks = explode("\n\n", $buffer);
        $extractedData = '';
        for ($i = 0; $i < count($thunks); $i++) {
            $lines = explode("\n", $thunks[$i]);
            $dataFieldCount = 0;
            for ($j = 0; $j < count($lines); $j++) {
                $line = $lines[$j];
                if (strpos($line, "event:finish") === 0) {
                    break;
                } else if (strpos($line, "data:") === 0) {
                    // 多行data需要加一个换行
                    if ($dataFieldCount++ > 0) {
                        $extractedData .= "\n";
                    }
                    $extractedData .= substr($line, 5);
                }
            }
        }
        return $extractedData;
    }
}