<?php

declare(strict_types=1);

use App\Environment;
use Yiisoft\Yii\Runner\Http\HttpApplicationRunner;

$autoloadPath = dirname(__DIR__)  . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    fwrite(
        STDERR,
        "Dependencies not found. Please run 'composer install' in the project directory first.\n" .
        "If Composer is not installed, visit https://getcomposer.org/download/ for instructions.\n"
    );
    exit(1);
}

require_once $autoloadPath;

Environment::prepare();

if (Environment::appC3()) {
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
    $path = (string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file(__DIR__ . $path)) {
        return false;
    }

    // Explicitly set for URLs with dot.
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}



// Run HTTP application runner
$runner = new HttpApplicationRunner(
    rootPath: dirname(__DIR__),
    debug: Environment::appDebug(),
    checkEvents: Environment::appDebug(),
    environment: Environment::appEnv(),
);
$runner->run();
