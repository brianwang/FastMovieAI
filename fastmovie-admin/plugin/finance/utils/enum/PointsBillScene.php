<?php

namespace plugin\finance\utils\enum;

use app\expose\enum\builder\Enum;

class PointsBillScene extends Enum
{
    const ADMIN = [
        'label' => '管理员操作',
        'value' => 'admin',
        'props' => [
            'type' => 'warning'
        ]
    ];
    const RECHARGE = [
        'label' => '充值',
        'value' => 'recharge',
        'props' => [
            'type' => 'success'
        ]
    ];
    const VIP_UPGRADE = [
        'label' => 'VIP升级',
        'value' => 'vip_upgrade',
        'props' => [
            'type' => 'success'
        ]
    ];
    const REGISTER = [
        'label' => '注册',
        'value' => 'register',
        'props' => [
            'type' => 'warning'
        ]
    ];
    const INVITE = [
        'label' => '邀请',
        'value' => 'invite',
        'props' => [
            'type' => 'warning'
        ]
    ];
    const CONSUME = [
        'label' => '消费',
        'value' => 'consume',
        'props' => [
            'type' => 'primary'
        ]
    ];
    const REFUNDED = [
        'label' => '退款',
        'value' => 'refunded',
        'props' => [
            'type' => 'danger'
        ]
    ];
}
