<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\UserRbacLink\UserRbacLink;
use App\Invoice\UserInv\UserRbacLinkRepository;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\Collection\DoctrineCollectionFactory;
use Cycle\ORM\Factory;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\ORMInterface;

/** @var array $params */

return [
    // Replace Factory definition to redefine default collection type
    // https://github.com/yiisoft/yii-cycle/pull/141/files#diff-09651a8d339eb91e3f0a340f94f4b0caf4df642c48812a526f8c80f7b8ba7ad4
    FactoryInterface::class => static function (DatabaseManager $dbManager, Spiral\Core\FactoryInterface $factory) {
        return new Factory(
            $dbManager,
            null,
            $factory,
            new DoctrineCollectionFactory(),
        );
    },

    // Explicit binding so Yii3 DI can resolve UserRbacLinkRepository directly,
    // bypassing RepositoryContainer delegate lookup which does not fire for this class.
    UserRbacLinkRepository::class => static function (ORMInterface $orm): UserRbacLinkRepository {
        /** @var UserRbacLinkRepository */
        return $orm->getRepository(UserRbacLink::class);
    },
];
