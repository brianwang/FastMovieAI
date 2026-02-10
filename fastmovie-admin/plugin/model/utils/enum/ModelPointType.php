<?php

namespace plugin\model\utils\enum;

use app\expose\enum\builder\Enum;

class ModelPointType extends Enum
{
    const TIMES = [
        'label' => '次',
        'value' => 'times',
        'unit' => '次'
    ];
    const FIELD_RULE = [
        'label' => '字段规则',
        'value' => 'field_rule',
        'unit' => '秒起'
    ];
}
