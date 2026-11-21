<?php

declare(strict_types=1);

namespace Tests\Testo\Config;

use App\Infrastructure\Persistence\PurchaseEntry\PurchaseEntry;
use App\Invoice\PurchaseEntry\PurchaseEntryRepository;
use App\Invoice\PurchaseEntry\PurchaseEntryRepositoryFactory;
use App\Invoice\PurchaseEntry\PurchaseEntryRepositoryInterface;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLite\MemoryConnectionConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\Factory;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

/**
 * config/common/di/purchase-entry.php wires the worksheet-upload purchase repository (petrol, materials and
 * other purchases kept for tax). PurchaseEntryRepository extends Select\Repository, so the container cannot
 * build it on its own: it needs this explicit definition. The file used to exist only on the live server,
 * untracked, so every other checkout failed on the purchase-entry pages. These tests fail if the wiring is
 * removed or stops working, and run it through a real container.
 */
#[Test]
final class PurchaseEntryDiConfigTest
{
    /** @return array<string, mixed> */
    private function definitions(): array
    {
        /** @var array<string, mixed> $definitions */
        $definitions = require dirname(__DIR__, 3) . '/config/common/di/purchase-entry.php';
        return $definitions;
    }

    /** A real Cycle ORM on in-memory SQLite with a one-entity schema: Select cannot be faked with mocks. */
    private function orm(): ORM
    {
        $database = new DatabaseManager(new DatabaseConfig([
            'default' => 'default',
            'databases' => ['default' => ['connection' => 'sqlite']],
            'connections' => ['sqlite' => new SQLiteDriverConfig(connection: new MemoryConnectionConfig())],
        ]));
        return new ORM(new Factory($database), new Schema([
            PurchaseEntry::class => [
                SchemaInterface::ENTITY => PurchaseEntry::class,
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'purchase_entry',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id' => 'id'],
                SchemaInterface::TYPECAST => [],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
            ],
        ]));
    }

    public function theInterfaceResolvesToTheRepository(): void
    {
        Assert::same(
            $this->definitions()[PurchaseEntryRepositoryInterface::class] ?? null,
            PurchaseEntryRepository::class,
        );
    }

    public function theRepositoryHasAnExplicitFactoryBecauseTheContainerCannotBuildIt(): void
    {
        Assert::same(
            $this->definitions()[PurchaseEntryRepository::class] ?? null,
            [PurchaseEntryRepositoryFactory::class, 'create'],
        );
    }

    public function theFactoryBuildsTheRepositoryFromAnOrm(): void
    {
        $repository = PurchaseEntryRepositoryFactory::create($this->orm(), TypedMock::of(EntityWriter::class));

        Assert::same($repository::class, PurchaseEntryRepository::class);
    }

    public function aRealContainerBuildsTheRepositoryFromTheConfigFile(): void
    {
        $container = new Container(ContainerConfig::create()->withDefinitions([
            ...$this->definitions(),
            ORMInterface::class => $this->orm(),
            EntityWriter::class => TypedMock::of(EntityWriter::class),
        ]));

        Assert::true($container->get(PurchaseEntryRepositoryInterface::class) instanceof PurchaseEntryRepository);
        Assert::true($container->get(PurchaseEntryRepository::class) instanceof PurchaseEntryRepository);
    }
}
