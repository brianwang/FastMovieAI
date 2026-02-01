<?php

namespace plugin\user\app\model;

use app\expose\helper\Config;
use app\expose\utils\Str;
use app\model\Basic;
use loong\oauth\utils\Str as UtilsStr;
use plugin\finance\expose\helper\Account;
use plugin\finance\utils\enum\PointsBillScene;

class PluginUserInvitationCode extends Basic
{
    // 关联创建者用户
    public function user()
    {
        return $this->hasOne(PluginUser::class, 'id', 'uid');
    }

    // 关联使用邀请码的用户
    public function useUser()
    {
        return $this->hasOne(PluginUser::class, 'id', 'use_uid');
    }

    public static function addCode($uid, $num = 1, $channels_uid = 0)
    {
        $needNum = $num;
        $finalCodes = [];
        while ($needNum > 0) {
            $codes = [];
            for ($i = 0; $i < $needNum * 2; $i++) {
                $code = UtilsStr::random(6);
                //转大写
                $codes[] = strtoupper($code);
            }
            $codes = array_unique($codes);
            $exists = self::whereIn('code', $codes)
                ->column('code');
            $available = array_values(array_diff($codes, $exists));
            $take = array_slice($available, 0, $needNum);
            $finalCodes = array_merge($finalCodes, $take);
            $needNum -= count($take);
        }
        $insertData = [];
        foreach ($finalCodes as $code) {
            $insertData[] = [
                'uid' => $uid,
                'code' => $code,
                'channels_uid' => $channels_uid,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ];
        }
        self::insertAll($insertData);
        return $finalCodes;
    }


    public  function onAfterUpdate($model)
    {
        if ($model->status == 'used') {
            $register =  new Config('register', 'user', $model->channels_uid);
            if ($register->invite_reward_points) {
                Account::incPoints($model->uid, $model->channels_uid, $register->invite_reward_points, PointsBillScene::INVITE['value'], 0, '邀请奖励积分');
            }
            PluginUserInvitationCode::where('id', $model->id)->update(['points' => $register->invite_reward_points]);
        }
    }
}
