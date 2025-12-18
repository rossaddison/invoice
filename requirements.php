<?php

declare(strict_types=1);

require_once 'vendor/yiisoft/requirements/src/RequirementsChecker.php';

use Yiisoft\Requirements\RequirementsChecker;

$requirementsChecker = new RequirementsChecker();

// Requirements based on invoice_build.yml workflow configuration
// Extensions: apcu, fileinfo, pdo, pdo_sqlite, intl, gd, openssl, dom, json, mbstring, curl, uopz, sodium
// PHP versions: 8.3, 8.4
// Memory limit: 1024M, Max execution time: 400s

$config = [
    // Core PHP Requirements
    [
        'name' => 'PHP version 8.3.0 or higher',
        'mandatory' => true,
        'condition' => version_compare(PHP_VERSION, '8.3.0', '>='),
        'by' => '<a href="https://www.yiiframework.com">Yii Framework</a>',
        'memo' => 'PHP 8.3.0 or higher is required (8.3-8.4 supported in CI).',
    ],
    [
        'name' => 'Maximum Execution Time of 400 seconds',
        'mandatory' => true,
        'condition' => $requirementsChecker->checkMaxExecutionTime('400'),
        'by' => '<a href="https://www.php.net/manual/en/info.configuration.php#ini.max-execution-time">php.ini setting</a>',
        'memo' => 'A php.ini max_execution_time minimum of 400 seconds is required for installation and complex operations.',
    ],
    [
        'name' => 'Memory limit of 1024M or higher',
        'mandatory' => true,
        'condition' => $requirementsChecker->checkPhpIniOn('memory_limit') && 
                      ($requirementsChecker->getBytes(ini_get('memory_limit')) >= $requirementsChecker->getBytes('1024M') || 
                       ini_get('memory_limit') === '-1'),
        'by' => 'Application performance',
        'memo' => 'Memory limit should be at least 1024M for optimal performance.',
    ],

    // Core Extensions (Mandatory)
    [
        'name' => 'PDO extension',
        'mandatory' => true,
        'condition' => extension_loaded('pdo'),
        'by' => 'Database abstraction layer',
        'memo' => 'Required for database connectivity (core dependency).',
    ],
    [
        'name' => 'JSON extension',
        'mandatory' => true,
        'condition' => extension_loaded('json'),
        'by' => 'API responses and configuration',
        'memo' => 'Required for JSON data processing (core dependency).',
    ],
    [
        'name' => 'MBString extension',
        'mandatory' => true,
        'condition' => extension_loaded('mbstring'),
        'by' => 'Multi-byte string handling',
        'memo' => 'Required for UTF-8 text processing (core dependency).',
    ],
    [
        'name' => 'DOM extension',
        'mandatory' => true,
        'condition' => extension_loaded('dom'),
        'by' => 'XML processing and HTML manipulation',
        'memo' => 'Required for XML/HTML document processing (core dependency).',
    ],
    [
        'name' => 'OpenSSL extension',
        'mandatory' => true,
        'condition' => extension_loaded('openssl'),
        'by' => 'Security and encryption',
        'memo' => 'Required for SSL/TLS connections and cryptographic operations (core dependency).',
    ],

    // Database Extensions
    [
        'name' => 'PDO SQLite extension',
        'mandatory' => false,
        'condition' => extension_loaded('pdo_sqlite'),
        'by' => 'SQLite database support',
        'memo' => 'Required for SQLite database operations and testing.',
    ],
    [
        'name' => 'PDO MySQL extension', 
        'mandatory' => false,
        'condition' => extension_loaded('pdo_mysql'),
        'by' => 'MySQL database support',
        'memo' => 'Required for MySQL database operations in production.',
    ],

    // Functionality Extensions
    [
        'name' => 'cURL extension',
        'mandatory' => false,
        'condition' => extension_loaded('curl'),
        'by' => 'HTTP client functionality',
        'memo' => 'Required for external API calls, webhooks, and payment notifications.',
    ],
    [
        'name' => 'GD extension',
        'mandatory' => false,
        'condition' => extension_loaded('gd'),
        'by' => 'Image processing',
        'memo' => 'Required for image generation, logos, and graphics processing.',
    ],
    [
        'name' => 'Fileinfo extension',
        'mandatory' => false,
        'condition' => extension_loaded('fileinfo'),
        'by' => 'File type detection',
        'memo' => 'Required for secure file upload validation and MIME type detection.',
    ],
    [
        'name' => 'Intl extension',
        'mandatory' => false,
        'condition' => extension_loaded('intl'),
        'by' => '<a href="https://secure.php.net/manual/en/book.intl.php">Internationalization</a> support',
        'memo' => 'Required for locale-aware formatting, currency, and date/time operations.',
    ],
    
    // Performance and Caching Extensions
    [
        'name' => 'APCu extension',
        'mandatory' => false,
        'condition' => extension_loaded('apcu'),
        'by' => 'In-memory caching',
        'memo' => 'Recommended for performance optimization and Prometheus metrics storage.',
    ],
    [
        'name' => 'Sodium extension',
        'mandatory' => false,
        'condition' => extension_loaded('sodium'),
        'by' => 'Modern cryptography',
        'memo' => 'Required for secure password hashing and encryption operations.',
    ],

    // Development and Testing Extensions
    [
        'name' => 'uopz extension',
        'mandatory' => false,
        'condition' => version_compare(PHP_VERSION, '8.4.0', '<') ? extension_loaded('uopz') : true,
        'by' => 'Testing framework',
        'memo' => version_compare(PHP_VERSION, '8.4.0', '>=') 
            ? 'WARNING: uopz extension is not compatible with PHP 8.4+. Tests may be limited.' 
            : 'Required for advanced testing scenarios and mocking in development.',
    ],
];

$result = $requirementsChecker
    ->check($config)
    ->getResult();
$requirementsChecker->render();

exit($result['summary']['errors'] === 0 ? 0 : 1);
