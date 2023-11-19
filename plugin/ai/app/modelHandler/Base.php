<?php

namespace plugin\ai\app\modelHandler;

abstract class Base
{

    protected $api = 'https://api.openai.com';

    protected $path = '/v1/chat/completions';

    protected $apikey = '';

    abstract public function buildData($model, $messages, $temperature, $stream);

    public function getApi()
    {
        [$schema, $host] = explode('://', $this->api, 2);
        return [$schema, $host];
    }
}