<?php

namespace plugin\ai\app\process;

use plugin\ai\app\model\AiBan;
use plugin\ai\app\model\AiMessage;
use support\Request;
use Throwable;
use Webman\Push\Api;
use Workerman\Timer;
use Workerman\Worker;

class SecurityCheck
{
    protected $lastMessageId = 0;
    public function onWorkerStart()
    {
        $this->lastMessageId = AiMessage::max('id');
        Timer::add(1, function () {
            $lastMessageId = AiMessage::max('id');
            if ($lastMessageId === $this->lastMessageId) {
                return;
            }
            $items = AiMessage::where('id', '>', $this->lastMessageId)
                ->select('user_id', 'session_id', 'role_id')->distinct('user_id', 'session_id', 'role_id')->get();
            foreach ($items as $item) {
                $messages = AiMessage::where(['session_id' => $item['session_id'], 'role_id' => $item['role_id']])
                    ->orderBy('id', 'desc')->select('content', 'id', 'ip', 'session_id', 'user_id', 'role_id')->limit(2)->get();
                $content = implode("\n", $messages->pluck('content')->toArray());
                if ($this->textHasSensitiveContent($content)) {
                    try {
                        $api = new Api(
                            'http://127.0.0.1:3232',
                            config('plugin.webman.push.app.app_key'),
                            config('plugin.webman.push.app.app_secret')
                        );
                        $api->trigger($item['session_id'], 'sensitive-content', ['roleId' => $item['role_id']]);
                    } catch (Throwable $e) {}

                    $ip = $messages->first()['ip'];
                    $log = "uid: {$item['user_id']} ip: {$ip} sid: {$item['session_id']} role_id: {$item['role_id']}\n" . $content;
                    // 内网ip不限制
                    if (!Request::isIntranetIp($ip)) {
                        $aiBan = new AiBan();
                        $aiBan->type = 'ip';
                        $aiBan->value = $ip;
                        $aiBan->expired_at = date('Y-m-d H:i:s', time() + 12*60*60);
                        $aiBan->log = $log;
                        $aiBan->save();
                    }
                    if ($userId = $messages->first()['user_id']) {
                        $aiBan = new AiBan();
                        $aiBan->type = 'user';
                        $aiBan->value = $userId;
                        $aiBan->expired_at = date('Y-m-d H:i:s', time() + 12*60*60);
                        $aiBan->log = $log;
                        $aiBan->save();
                    }
                }
            }
            $this->lastMessageId = $lastMessageId;
        });
    }

    public function textHasSensitiveContent($text)
    {
        $access_token = $this->getAccessToken();
        if (!$access_token) {
            return false;
        }

        $result = $this->post("https://api.weixin.qq.com/wxa/msg_sec_check?access_token=$access_token", json_encode([
            'content'      => $text
        ], JSON_UNESCAPED_UNICODE));
        if (!$result) {
            return false;
        }

        $result = json_decode($result, true);

        if (!empty($result['errcode']) && 87014 == $result['errcode']) {
            return true;
        }

        if (!empty($result['errcode'])) {
            echo "security check fail " . json_encode($result, JSON_UNESCAPED_UNICODE) . " and try restart security process\n";
            Worker::stopAll();
            return false;
        }

        return false;
    }

    public function imageHasSensitiveContent($path)
    {
        $access_token = $this->getAccessToken();
        if (!$access_token) {
            return false;
        }

        $result = $this->post("https://api.weixin.qq.com/wxa/img_sec_check?access_token=$access_token", ['media' => new \CURLFile($path)]);
        if (!$result) {
            return false;
        }

        $result = json_decode($result, true);

        if (!empty($result['errcode']) && 87014 == $result['errcode']) {
            return true;
        }

        return false;
    }

    public function get($url)
    {
        return $this->curl('get', $url);
    }

    public function post($url, $data)
    {
        return $this->curl('post', $url, $data);
    }

    public function curl($method, $url, $data = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function getAccessToken()
    {
        static $last_update_time, $access_token;
        if (!$last_update_time) {
            $last_update_time = 0;
        }
        $now = time();
        if ($now - $last_update_time < 60*60 && $access_token) {
            return $access_token;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx63eba93551610b67&secret=aa3dca255bd266e5a7c4649cb102c6e9";
        $result = file_get_contents($url);
        if (!$result) {
            return $access_token;
        }
        $json = json_decode($result, true);
        if (!$json || !isset($json['access_token'])) {
            return $access_token;
        }
        $access_token = $json['access_token'];
        $last_update_time = $now;
        return $access_token;
    }
}