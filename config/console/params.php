<?php

declare(strict_types=1);

use Yiisoft\Yii\Console\Application;

return [
    'yiisoft/yii-console' => [
        'name' => Application::NAME,
        'version' => Application::VERSION,
        'autoExit' => false,
        'commands' => require_once __DIR__ . '/commands.php',
    ],
];
