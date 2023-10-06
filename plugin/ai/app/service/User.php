<?php

namespace plugin\ai\app\service;

use plugin\ai\app\model\AiUser;
use plugin\user\api\Limit;
use support\Db;
use support\exception\BusinessException;
use Throwable;

class User
{
    /**
     * @param $userId
     * @param $model
     * @return bool
     * @throws BusinessException
     */
    public static function reduceBalance($userId, $model)
    {
        static::checkNewUser($userId);
        $modelType = Common::getModelType($model);
        $field = "available_$modelType";
        $affectedRows  = AiUser::where('user_id', $userId)
            ->where($field, '>=', 1)
            ->update([
                $field => Db::raw("$field - 1"),
                'message_count' => Db::raw('message_count + 1'),
            ]);
        return (bool)$affectedRows;
    }

    /**
     * 是否是VIP(vip未过期)
     * @param $userId
     * @param $aiUser
     * @return bool
     */
    public static function isVip($userId, $aiUser = null)
    {
        $aiUser = $aiUser ?? AiUser::where('user_id', $userId)->first();
        return $aiUser && $aiUser->expired_at && strtotime($aiUser->expired_at) > time();
    }

    /**
     * 新用户新增会员信息，赠送相关余额
     * @param $userId
     * @return void
     * @throws BusinessException
     */
    public static function checkNewUser($userId)
    {
        if (AiUser::where('user_id', $userId)->first()) {
            return;
        }
        $chatGptSetting = ChatGpt::getSetting();
        $midjourneySetting = Midjourney::getSetting();

        $aiUser = new AiUser();
        $aiUser->user_id = $userId;
        $aiUser->available_gpt3 = $chatGptSetting['gpt3_reg_free_count'];
        $aiUser->available_gpt4 = $chatGptSetting['gpt4_reg_free_count'];
        $aiUser->available_dalle = $chatGptSetting['dalle_reg_free_count'];
        $aiUser->available_midjourney = $midjourneySetting['reg_free_count'];
        $aiUser->save();
    }
}