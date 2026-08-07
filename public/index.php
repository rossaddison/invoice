<?php

declare(strict_types=1);

use Yiisoft\Yii\Runner\Http\HttpApplicationRunner;

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

/**
 * Must come after autoload.php: c3.php's own fallback autoload logic
 * assumes it's been copied to the project root (per its README) — which
 * it has, via codeception/c3's own Composer installer plugin
 * (Codeception\c3\Installer, gitignored, regenerated on every composer
 * install/update). Use THAT copy, not vendor/codeception/c3/c3.php
 * directly: c3.php's own __DIR__-relative path lookups (its fallback
 * autoloader, and — separately — codeception.yml itself) only resolve
 * correctly from the project root, where __DIR__ actually matches
 * codeception.yml's own location. Requiring the vendor copy instead
 * crashed two different ways: "Undefined constant
 * C3_CODECOVERAGE_MEDIATE_STORAGE" when \Codeception\Codecept wasn't
 * loaded yet (fixed by this block also running after autoload.php), and
 * "Codeception config file not found" once it was, since
 * vendor/codeception/c3/codeception.yml doesn't exist. See
 * docs/CODECEPTION_COVERAGE_AUGUST_2026.md.
 *
 * @psalm-suppress RiskyTruthyFalsyComparison getenv('YII_C3')
 */
if (getenv('YII_C3')) {
    $c3 = dirname(__DIR__) . '/c3.php';
    if (file_exists($c3)) {
        // c3.php's own error handler falls back to the undefined constant
        // C3_CODECOVERAGE_MEDIATE_STORAGE when this isn't set, crashing
        // with a misleading "Undefined constant" fatal on top of whatever
        // the real error was. Defining it (per c3's own README) turns
        // that into a readable message in runtime/logs instead.
        if (!defined('C3_CODECOVERAGE_ERROR_LOG_FILE')) {
            define('C3_CODECOVERAGE_ERROR_LOG_FILE', dirname(__DIR__) . '/runtime/logs/c3_error.log');
        }
        require_once $c3;
    }
}

// Suppress E_DEPRECATED from vendor libraries (e.g. curl_close in payment SDKs)
// that cannot be patched directly. Remove once upstream packages are updated.
error_reporting(E_ALL & ~E_DEPRECATED);

// Run HTTP application runner
$runner = new HttpApplicationRunner(
    rootPath: dirname(__DIR__),
    debug: !empty($_ENV['YII_DEBUG']) ? $_ENV['YII_DEBUG'] : false,
    checkEvents: !empty($_ENV['YII_DEBUG']) ? $_ENV['YII_DEBUG'] : false,
    environment: $_ENV['YII_ENV'],
);
$runner->run();
