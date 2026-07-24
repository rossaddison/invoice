<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Install\RequirementsConfig;
use Yiisoft\Requirements\RequirementsChecker;

// Requirements based on invoice_build.yml workflow configuration
// Extensions: apcu, fileinfo, pdo, pdo_sqlite, intl, gd, openssl, dom, json, mbstring, curl, uopz, sodium
// PHP versions: 8.3, 8.4
// Memory limit: 1024M, Max execution time: 400s
//
// The check list itself lives in App\Install\RequirementsConfig so this CLI
// script and the web-based /install wizard never drift apart.

$requirementsChecker = new RequirementsChecker();
/** @var array{summary: array{errors: int}}|null $result */
$result = $requirementsChecker
    ->check(RequirementsConfig::checks())
    ->getResult();
$requirementsChecker->render();

exit(($result['summary']['errors'] ?? 1) === 0 ? 0 : 1);
