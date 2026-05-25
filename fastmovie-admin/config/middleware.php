<?php
return [
    '' => [
        app\middleware\Template::class,
        app\middleware\Platform::class,
        app\middleware\Csrf::class
    ],
    'admin' => [
        app\admin\middleware\Auth::class
    ],
    'api' => [
        app\middleware\Csrf::class
    ]
];
