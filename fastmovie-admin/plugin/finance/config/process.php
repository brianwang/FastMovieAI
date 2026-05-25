<?php
use plugin\finance\process\Expire;

// Swoole is optional — falls back to Workerman default event loop
$swooleClass = class_exists(\Swoole\Coroutine::class) ? \Workerman\Events\Swoole::class : null;

return [
    'OrdersExpire'  => [
        'eventLoop' => $swooleClass,
        'handler'  => Expire::class
    ],
];
