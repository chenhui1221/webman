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
        foreach(['gpt-3' => 'gpt3', 'gpt-4' => 'gpt4', 'dall.e' => 'dalle', 'midjourney' => 'midjourney'] as $prefix => $type) {
           if (strpos($model, $prefix) === 0) {
               return $type;
           }
        }
        throw new BusinessException("未知模型 $model");
    }

    /**
     * 获取模型每天免费额度
     * @param $model
     * @return array|mixed|null
     * @throws BusinessException
     */
    public static function getDayFreeCount($model)
    {
        $modelType = static::getModelType($model);
        if ($modelType === 'midjourney') {
            return Midjourney::getSetting('day_free_count');
        }
        return ChatGpt::getSetting("{$modelType}_day_free_count");
    }

    /**
     * 检测是否开启了某个模型
     * @param $model
     * @return array|bool|mixed|null
     * @throws BusinessException
     */
    public static function modelEnabled($model)
    {
        $modelType = static::getModelType($model);
        $gptSetting = ChatGpt::getSetting();
        if (in_array($modelType, ['gpt3', 'gpt4', 'dalle'])) {
            return !empty($gptSetting["enable_$modelType"]);
        }
        if ($modelType === 'midjourney') {
            return Midjourney::getSetting('enable');
        }
        return false;
    }

}