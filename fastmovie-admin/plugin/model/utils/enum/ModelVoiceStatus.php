<?php

namespace plugin\model\utils\enum;

use app\expose\enum\builder\Enum;

class ModelVoiceStatus extends Enum
{
    const WAIT = [
        'label' => '待提交',
        'value' => 'wait',
        'props' => [
            'type' => 'info'
        ]
    ];
    const CLONING = [
        'label' => '克隆中',
        'value' => 'cloning',
        'props' => [
            'type' => 'primary'
        ]
    ];
    const SUCCESS = [
        'label' => '成功',
        'value' => 'success',
        'props' => [
            'type' => 'success'
        ]
    ];
    const FAIL = [
        'label' => '失败',
        'value' => 'fail',
        'props' => [
            'type' => 'danger'
        ]
    ];
}
