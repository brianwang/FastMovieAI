<?php

use plugin\model\process\audio\AudioTransfer;
use plugin\model\process\chat\Submit;
use plugin\model\process\draw\ImageTransfer;
use plugin\model\process\video\VideoTransfer;

// Swoole is optional — falls back to Workerman default event loop
$swooleClass = class_exists(\Swoole\Coroutine::class) ? \Workerman\Events\Swoole::class : null;

return [
    'ChatSubmit' => [
        'eventLoop' => $swooleClass,
        'handler'  => Submit::class,
        'count' => 5
    ],
    'ImageTransfer' => [
        'eventLoop' => $swooleClass,
        'handler'  => ImageTransfer::class,
        'count' => 5
    ],
    'VideoTransfer' => [
        'eventLoop' => $swooleClass,
        'handler'  => VideoTransfer::class,
        'count' => 5
    ],
    'AudioTransfer' => [
        'eventLoop' => $swooleClass,
        'handler'  => AudioTransfer::class,
        'count' => 5
    ],
];
