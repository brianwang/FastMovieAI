<?php

namespace plugin\finance\app\model;

use app\model\Basic;
use plugin\notification\expose\helper\Push;

class PluginFinanceWallet extends Basic
{
    protected $pk = 'uid';
    protected function getOptions(): array
    {
        return [
            'type' => [
                'balance'                   =>    'float',
                'balance_sum'               =>    'float',
                'balance_used'              =>    'float',
            ]
        ];
    }
    public static function onAfterRead($model)
    {
        $model->available_points = $model->points + $model->tmp_points;
    }
    public static function onAfterUpdate($model)
    {
        Push::send([
            'uid' => $model->uid,
            'channels_uid' => $model->channels_uid,
            'event' => 'wallet',
        ], []);
    }
}
