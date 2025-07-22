<?php

declare(strict_types=1);

use Yiisoft\Access\AccessCheckerInterface;
use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Manager;
use Yiisoft\Rbac\Php\AssignmentsStorage;
use Yiisoft\Rbac\Php\ItemsStorage;

/**
 * @var array $params
 * @var array $params['yiisoft/aliases']
 * @var array $params['yiisoft/aliases']['aliases']
 * @var string $params['yiisoft/aliases']['aliases']['@root']
 */


return [
    ItemsStorageInterface::class => [
        'class' => ItemsStorage::class,
        '__construct()' => [
            'filePath' => $params['yiisoft/aliases']['aliases']['@root'] . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'rbac' . DIRECTORY_SEPARATOR . 'items.php',
        ],
    ],
    AssignmentsStorageInterface::class => [
        'class' => AssignmentsStorage::class,
        '__construct()' => [
            'filePath' => $params['yiisoft/aliases']['aliases']['@root'] . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'rbac' . DIRECTORY_SEPARATOR . 'assignments.php',
        ],
    ],
    AccessCheckerInterface::class => Manager::class,
];
