<?php

namespace plugin\ai\app\service;


use support\exception\BusinessException;

class Common
{
    /**
     * 获取模型类型
     * @param $model
     * @return string
     * @throws BusinessException
     */
    public static function getModelType($model)
    {
        $modelTypes = [
            'gpt-3' => 'gpt3',
            'gpt-4' => 'gpt4',
            'dall.e' => 'dalle',
            'chatglm' => 'chatglm',
            'midjourney' => 'midjourney',
            'qwen' => 'qwen',
            'ernie' => 'ernie',
        ];
        foreach($modelTypes as $prefix => $type) {
           if (strpos($model, $prefix) === 0) {
               return $type;
           }
        }
        return explode('-', $model)[0];
    }

    /**
     * 获取模型每天免费额度
     * @param $model
     * @return array|mixed|null
     * @throws BusinessException
     */
    public static function getDayFreeCount($modelType)
    {
        if (strpos($modelType, 'gpt') !== false || strpos($modelType, 'dall') !== false) {
            return ChatGpt::getSetting("{$modelType}_day_free_count");
        }
        $service = "\\plugin\\ai\\app\\service\\" . ucfirst($modelType);
        return $service::getSetting('day_free_count');
    }

    /**
     * 检测是否开启了某个模型
     * @param $modelType
     * @return array|bool|mixed|null
     */
    public static function modelEnabled($modelType)
    {
        $gptSetting = ChatGpt::getSetting();
        if (in_array($modelType, ['gpt3', 'gpt4', 'dalle'])) {
            return !empty($gptSetting["enable_$modelType"]);
        }
        $service = "\\plugin\\ai\\app\\service\\" . ucfirst($modelType);
        return $service::getSetting('enable');
    }

    /**
     * 解析 http chunked 包体
     * @param string $data
     * @return string
     */
    public static function decodeChunked(string $data): string
    {
        $pos = 0;
        $len = strlen($data);
        $decoded = '';

        while ($pos < $len) {
            if (($newlinePos = strpos($data, "\n", $pos)) === false) {
                return $data; // 包体格式错误
            }

            $chunkSizeHex = trim(substr($data, $pos, $newlinePos - $pos));
            $chunkSize = hexdec($chunkSizeHex);

            if ($chunkSize === 0) {
                break; // 包体解析完成
            }

            $pos = $newlinePos + 1;
            $chunkData = substr($data, $pos, $chunkSize);
            $decoded .= $chunkData;
            $pos += $chunkSize + 2; // 跳过当前 chunk 和下一个 chunk 的换行符

            if ($pos >= $len) {
                return $data; // 包体格式错误
            }
        }

        return $decoded;
    }


}