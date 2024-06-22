<?php

require_once('vendor/yiisoft/requirements/src/RequirementsChecker.php');

use Yiisoft\Requirements\RequirementsChecker;

$requirementsChecker = new RequirementsChecker;

// Add here the conditions that must be verified
$config = [
    [
        'name' => 'PHP version',
        'mandatory' => true,
        'condition' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'by' => '<a href="https://www.yiiframework.com">Yii Framework</a>',
        'memo' => 'PHP 7.4.0 or higher is required.',
    ],
    [
        'name' => 'PDO MySQL extension',
        'mandatory' => false,
        'condition' => extension_loaded('pdo_mysql'),
        'by' => 'All DB-related classes',
        'memo' => 'Required for MySQL database.',
    ],
    [
        'name' => 'Intl extension',
        'mandatory' => false,
        'condition' => $requirementsChecker->checkPhpExtensionVersion('intl', '1.0.2', '>='),
        'by' => '<a href="https://secure.php.net/manual/en/book.intl.php">Internationalization</a> support',
        'memo' => 'PHP Intl extension 1.0.2 or higher is required.'
    ],
    [
        'name' => 'php.ini => Just In Time (jit)',
        'mandatory' => false,
        'condition' => $requirementsChecker->checkPhpIniOff('opcache.jit'),
        'by' => '<a href="https://php.watch/articles/jit-in-depth">JIT in depth</a>',
        'memo' => 'Opcache Just In Time must be off'
    ],
    [
        'name' => 'php.ini => xDebug Mode',
        'mandatory' => false,
        'condition' => $requirementsChecker->checkPhpIniOff('xdebug.mode'),
        'by' => '',
        'memo' => 'xdebug mode must be off'
    ],
];

$result = $requirementsChecker
    ->check($config)
    ->getResult();
$requirementsChecker->render();

exit($result['summary']['errors'] === 0 ? 0 : 1);