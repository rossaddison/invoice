<?php

declare(strict_types=1);

namespace Tests\Testo\Config;

use App\Infrastructure\Persistence\PurchaseEntry\PurchaseEntry;
use App\Invoice\PurchaseEntry\PurchaseEntryRepository;
use App\Invoice\PurchaseEntry\PurchaseEntryRepositoryInterface;
use Closure;
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
use Cycle\ORM\Select;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * config/common/di/purchase-entry.php wires the worksheet-upload purchase repository (petrol, materials and
 * other purchases kept for tax). PurchaseEntryRepository extends Select\Repository, so the container cannot
 * build it on its own: it needs this explicit definition. The file used to exist only on the live server,
 * untracked, so every other checkout failed on the purchase-entry pages. These tests fail if the wiring is
 * removed or stops matching the repository's constructor.
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

    public function theInterfaceResolvesToTheRepository(): void
    {
        Assert::same(
            $this->definitions()[PurchaseEntryRepositoryInterface::class] ?? null,
            PurchaseEntryRepository::class,
        );
    }

    public function theRepositoryHasAnExplicitFactoryBecauseTheContainerCannotBuildIt(): void
    {
        Assert::true(($this->definitions()[PurchaseEntryRepository::class] ?? null) instanceof Closure);
    }

    public function theFactoryTakesTheOrmAndTheEntityWriterAndReturnsTheRepository(): void
    {
        $factory = $this->definitions()[PurchaseEntryRepository::class] ?? null;
        Assert::true($factory instanceof Closure);

        $function = new ReflectionFunction($factory);
        $parameterTypes = array_map(
            static fn (\ReflectionParameter $parameter): ?string => $parameter->getType() instanceof ReflectionNamedType
                ? $parameter->getType()->getName()
                : null,
            $function->getParameters(),
        );

        Assert::same($parameterTypes, [ORMInterface::class, EntityWriter::class]);
        $returnType = $function->getReturnType();
        Assert::true($returnType instanceof ReflectionNamedType);
        Assert::same($returnType->getName(), PurchaseEntryRepository::class);
    }

    public function whatTheFactoryPassesStillMatchesTheRepositorysConstructor(): void
    {
        // the factory builds `new PurchaseEntryRepository(new Select(...), $entityWriter)`
        $constructor = new ReflectionMethod(PurchaseEntryRepository::class, '__construct');
        $types = array_map(
            static fn (\ReflectionParameter $parameter): ?string => $parameter->getType() instanceof ReflectionNamedType
                ? $parameter->getType()->getName()
                : null,
            $constructor->getParameters(),
        );

        Assert::same($types, [Select::class, EntityWriter::class]);
    }

    public function theFactoryReallyBuildsTheRepositoryFromAnOrm(): void
    {
        // A real Cycle ORM on in-memory SQLite with a one-entity schema: Select cannot be faked with mocks.
        $database = new DatabaseManager(new DatabaseConfig([
            'default' => 'default',
            'databases' => ['default' => ['connection' => 'sqlite']],
            'connections' => ['sqlite' => new SQLiteDriverConfig(connection: new MemoryConnectionConfig())],
        ]));
        $orm = new ORM(new Factory($database), new Schema([
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

        $factory = $this->definitions()[PurchaseEntryRepository::class] ?? null;
        Assert::true($factory instanceof Closure);

        $repository = $factory($orm, TypedMock::of(EntityWriter::class));

        Assert::true($repository instanceof PurchaseEntryRepository);
    }
}
