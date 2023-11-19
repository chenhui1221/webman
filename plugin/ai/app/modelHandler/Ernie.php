<?php

namespace plugin\ai\app\modelHandler;


use support\exception\BusinessException;

/**
 * 百度千帆模型(文心一言)
 */
class Ernie extends Gpt3
{

    protected $api = 'https://aip.baidubce.com';

    protected $path = '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/eb-instant';

    /**
     * @throws BusinessException
     */
    public function __construct($request)
    {
        $model = $request->post('model');
        if ($model === 'ernie-bot') {
            $this->path = '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/completions';
        } else if ($model === 'ernie-bot-4') {
            $this->path = '/rpc/2.0/ai_custom/v1/wenxinworkshop/chat/completions_pro';
        }
        $this->path .= '?access_token=' . $this->getAccessToken();
    }

    public function getAccessToken()
    {
        static $accessToken, $apiKey, $secretKey;
        $setting = \plugin\ai\app\service\Ernie::getSetting();
        $apikeyFromDb = $setting['apikey'] ?? '';
        $secretKeyFromDb = $setting['secret_key'] ?? '';
        if (!$apikeyFromDb || !$secretKeyFromDb) {
            throw new BusinessException('百度千帆模型ApiKey或者SecretKey未设置');
        }
        if ($apikeyFromDb !== $apiKey || $secretKeyFromDb !== $secretKey) {
            $accessToken = '';
            $apiKey = $apikeyFromDb;
            $secretKey = $secretKeyFromDb;
        }
        if (!$accessToken) {
            $buffer = file_get_contents("https://aip.baidubce.com/oauth/2.0/token?grant_type=client_credentials&client_id=$apiKey&client_secret=$secretKey");
            if (!$buffer) {
                throw new BusinessException('access_token获取失败，访问 https://aip.baidubce.com/oauth/2.0/token 失败');
            }
            $json = json_decode($buffer, true);
            if (!$json || !isset($json['access_token'])) {
                throw new BusinessException('access_token获取失败，' . $buffer);
            }
            $accessToken = $json['access_token'];
        }
        return $accessToken;
    }

    public function formatResponse($buffer): string
    {
        if ($buffer && $buffer[0] === '{') {
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
                if (isset($data['error_msg'])) {
                    $content .= $data['error_msg'] ?? "";
                } else {
                    $content .= $data['result'] ?? "";
                }
            } catch (Exception $e) {
                echo $e;
            }
        }
        return $content;
    }

}