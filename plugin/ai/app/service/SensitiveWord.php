<?php

namespace plugin\ai\app\service;

use plugin\admin\app\model\Option;

class SensitiveWord extends Base
{
    /**
     * options表对应的name字段
     */
    const OPTION_NAME = 'plugin_ai.sensitive-words';

    /**
     * @param $content
     * @return bool
     */
    public static function contentSafe($content): bool
    {
        $sensitiveWords = SensitiveWord::getSetting();
        $words = $sensitiveWords ? explode("\n", $sensitiveWords) : [];
        foreach ($words as $word) {
            $word = trim($word);
            if (!$word) continue;
            // 英文单词不做正则匹配
            if (preg_match('/^[a-zA-Z ]+$/', $word)) {
                if (strpos($content, $word) !== false) {
                    file_put_contents(runtime_path('logs/unsafe-content.'  . date('Y-m-d') . '.log'), date('Y-m-d H:i:s') . " " . $word . " " . $match[0] . "\n" . $content . "\n", FILE_APPEND);
                    return false;
                }
                continue;
            }
            // 中文词组使用正则匹配
            preg_match_all('/./u', $word, $matches);
            if (empty($matches[0])) {
                continue;
            }
            $preg = '/' . implode(".?", $matches[0]) . '/i';
            if (preg_match($preg, $content, $match)) {
                file_put_contents(runtime_path('logs/unsafe-content.'  . date('Y-m-d') . '.log'), date('Y-m-d H:i:s') . " " . $word . " " . $match[0] . "\n" . $content . "\n", FILE_APPEND);
                return false;
            }
        }
        return true;
    }

    /**
     * 保存配置
     *
     * @param $data
     * @return void
     */
    public static function saveSetting($data)
    {
        $data = implode("\n",  $data ? array_unique(explode("\n", $data)) : []);
        if (!$item = Option::where('name', static::OPTION_NAME)->first()) {
            $item = new Option;
        }
        $item->name = static::OPTION_NAME;
        $item->value = json_encode($data, JSON_UNESCAPED_UNICODE);
        $item->save();
    }
}