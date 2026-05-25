<?php

use plugin\article\process\JoinPushMessage;

// Swoole is optional — falls back to Workerman default event loop
$swooleClass = class_exists(\Swoole\Coroutine::class) ? \Workerman\Events\Swoole::class : null;

return [
    'JoinPushMessage' => [
        'eventLoop' => $swooleClass,
        'handler'  => JoinPushMessage::class
    ],
];
