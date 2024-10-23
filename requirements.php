<?php

require_once('vendor/yiisoft/requirements/src/RequirementsChecker.php');

use Yiisoft\Requirements\RequirementsChecker;

$requirementsChecker = new RequirementsChecker();

// Add here the conditions that must be verified
$config = [
    [
        'name' => 'PHP version',
        'mandatory' => true,
        'condition' => version_compare(PHP_VERSION, '8.3.0', '>='),
        'by' => '<a href="https://www.yiiframework.com">Yii Framework</a>',
        'memo' => 'PHP 8.3.0 or higher is required.',
    ],
    [
        'name' => 'PDO MySQL extension',
        'mandatory' => false,
        'condition' => extension_loaded('pdo_mysql'),
        'by' => 'All DB-related classes',
        'memo' => 'Required for MySQL database.',
    ],
    [
        'name' => 'cURL',
        'mandatory' => false,
        'condition' => extension_loaded('curl'),
        'by' => '<a href="https://github.com/php-http/curl-client">cURL </a>',
        'memo' => 'Required for the Telegram Bot Api for sending payment notification messages to an admin mobile when clients pay online.',
    ],
    [
        'name' => 'Intl extension',
        'mandatory' => false,
        'condition' => $requirementsChecker->checkPhpExtensionVersion('intl', '1.0.2', '>='),
        'by' => '<a href="https://secure.php.net/manual/en/book.intl.php">Internationalization</a> support',
        'memo' => 'PHP Intl extension 1.0.2 or higher is required.'
    ],
];

$result = $requirementsChecker
    ->check($config)
    ->getResult();
$requirementsChecker->render();

exit($result['summary']['errors'] === 0 ? 0 : 1);
