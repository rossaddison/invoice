<?php

declare(strict_types=1);

use Yiisoft\Yii\Runner\Http\HttpApplicationRunner;

/**
 * @psalm-suppress RiskyTruthyFalsyComparison getenv('YII_C3')
 */
if (getenv('YII_C3')) {
    $c3 = dirname(__DIR__) . '/c3.php';
    if (file_exists($c3)) {
        require_once $c3;
    }
}

/**
 * @psalm-var string $_SERVER['REQUEST_URI']
 */
// PHP built-in server routing.
if (PHP_SAPI === 'cli-server') {
    // Serve static files as is.
    $path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file(__DIR__ . $path)) {
        return false;
    }

    // Explicitly set for URLs with dot.
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}

chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/autoload.php';

// Run HTTP application runner
$runner = new HttpApplicationRunner(
    rootPath: dirname(__DIR__),
    debug: !empty($_ENV['YII_DEBUG']) ? $_ENV['YII_DEBUG'] : false,
    checkEvents: !empty($_ENV['YII_DEBUG']) ? $_ENV['YII_DEBUG'] : false,
    environment: $_ENV['YII_ENV'],
);
$runner->run();
