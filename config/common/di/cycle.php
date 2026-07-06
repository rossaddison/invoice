<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\As4Message\As4Message;
use App\Infrastructure\Persistence\As4Message\CycleOrmAs4MessageRepository;
use App\Infrastructure\Persistence\UserRbacLink\UserRbacLink;
use App\Invoice\UserInv\UserRbacLinkRepository;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\Collection\DoctrineCollectionFactory;
use Cycle\ORM\Factory;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Select;
use Yiisoft\Data\Writer\DataWriterInterface;

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

    // Bind the default database so Cycle-based repositories can inject DatabaseInterface.
    DatabaseInterface::class => static function (DatabaseManager $dbManager): DatabaseInterface {
        return $dbManager->database();
    },

    // CycleOrmAs4MessageRepository extends Select\Repository and needs Select<As4Message>
    // which cannot be auto-resolved by the DI container — wire it explicitly here.
    // As4MessageRepositoryInterface binds to this class in config/common/di/as4.php.
    CycleOrmAs4MessageRepository::class => static function (
        ORMInterface $orm,
        DataWriterInterface $writer,
        DatabaseInterface $db,
    ): CycleOrmAs4MessageRepository {
        return new CycleOrmAs4MessageRepository(
            new Select($orm, As4Message::class),
            $writer,
            $db,
        );
    },
];
