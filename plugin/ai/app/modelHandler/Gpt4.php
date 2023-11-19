<?php

namespace plugin\ai\app\modelHandler;


use plugin\ai\app\service\ChatGpt;

class Gpt4 extends Gpt3
{
    public function __construct($request)
    {
        $this->api = ChatGpt::getSetting('gpt4_api_host');
        if ($authorization = $request->header('Authorization')) {
            $this->apikey = explode(' ', $authorization)[1] ?? '';
            $this->isUserApikey = true;
        }
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
        $this->apikey = $this->getApiKeyFromDb('gpt4');
        return $this->apikey;
    }
}