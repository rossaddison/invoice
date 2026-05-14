<?php

declare(strict_types=1);

use Cycle\Database\DatabaseManager;
use Yiisoft\Access\AccessCheckerInterface;
use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\Cycle\AssignmentsStorage as CycleAssignmentsStorage;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Manager;
use Yiisoft\Rbac\Php\ItemsStorage;

/**
 * @psalm-var array{
 *     'yiisoft/aliases': array{
 *         aliases: array{
 *             '@root': string
 *         }
 *     }
 * } $params
 */

return [
    ItemsStorageInterface::class => [
        'class' => ItemsStorage::class,
        '__construct()' => [
            'filePath' => $params['yiisoft/aliases']['aliases']['@root'] .
            DIRECTORY_SEPARATOR . 'resources' .
            DIRECTORY_SEPARATOR . 'rbac' .
            DIRECTORY_SEPARATOR . 'items.php',
        ],
    ],
    AssignmentsStorageInterface::class =>
        static function (DatabaseManager $databaseManager): CycleAssignmentsStorage {
            return new CycleAssignmentsStorage($databaseManager->database());
    },
    AccessCheckerInterface::class => Manager::class,
];
