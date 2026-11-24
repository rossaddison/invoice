<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice;

use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Invoice\Quote\QuoteIndexFilter;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\SalesOrder\SalesOrderRepository;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLite\MemoryConnectionConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\Factory;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\ORM;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Translator\Translator;

/**
 * The client filter of the quote and sales-order lists. Its drop-down used to be keyed by the client's full name and
 * the filter matched the first name and surname, so two clients with the same name were ONE entry and picking it
 * listed both clients' records. The drop-down now holds the client id and the filter matches it, so each client is
 * chosen on their own; an address saved with a name still works. Real queries on in-memory SQLite, virtual data:
 *
 *   client 1 and client 2 are both called Virtual Tester; client 3 is Test Other
 *   quote 1, 2, 3 and sales order 1, 2 belong to clients 1, 2, 3 and 1, 2
 */
#[Test]
final class ClientFilterByIdSqliteTest
{
    private ?DatabaseManager $manager = null;

    private function database(): DatabaseInterface
    {
        $this->manager = new DatabaseManager(new DatabaseConfig([
            'default' => 'default',
            'databases' => ['default' => ['connection' => 'sqlite']],
            'connections' => ['sqlite' => new SQLiteDriverConfig(connection: new MemoryConnectionConfig())],
        ]));
        $db = $this->manager()->database();
        foreach ([
            'CREATE TABLE client (id INTEGER PRIMARY KEY, client_name TEXT, client_surname TEXT)',
            'CREATE TABLE grp (id INTEGER PRIMARY KEY)',
            'CREATE TABLE usr (id INTEGER PRIMARY KEY)',
            'CREATE TABLE quote (id INTEGER PRIMARY KEY, client_id INTEGER, group_id INTEGER, user_id INTEGER, status_id INTEGER)',
            'CREATE TABLE sales_order (id INTEGER PRIMARY KEY, client_id INTEGER, group_id INTEGER, user_id INTEGER, quote_id INTEGER)',
        ] as $sql) {
            $db->execute($sql);
        }
        foreach ([[1, 'Virtual', 'Tester'], [2, 'Virtual', 'Tester'], [3, 'Test', 'Other']] as [$id, $name, $surname]) {
            $db->insert('client')->values(['id' => $id, 'client_name' => $name, 'client_surname' => $surname])->run();
        }
        $db->insert('grp')->values(['id' => 1])->run();
        $db->insert('usr')->values(['id' => 1])->run();
        foreach ([[1, 1], [2, 2], [3, 3]] as [$id, $client]) {
            $db->insert('quote')->values(['id' => $id, 'client_id' => $client, 'group_id' => 1, 'user_id' => 1, 'status_id' => 2])->run();
        }
        foreach ([[1, 1], [2, 2]] as [$id, $client]) {
            $db->insert('sales_order')->values(['id' => $id, 'client_id' => $client, 'group_id' => 1, 'user_id' => 1, 'quote_id' => $id])->run();
        }

        return $db;
    }

    /**
     * @param list<string> $columns
     * @param array<string, array<int|string, mixed>> $relations
     *
     * @return array<int, mixed>
     */
    private function entity(string $table, array $columns, array $relations = []): array
    {
        return [
            Schema::ENTITY => \stdClass::class,
            Schema::MAPPER => StdMapper::class,
            Schema::DATABASE => 'default',
            Schema::TABLE => $table,
            Schema::PRIMARY_KEY => 'id',
            Schema::COLUMNS => array_combine($columns, $columns),
            Schema::TYPECAST => [],
            Schema::RELATIONS => $relations,
        ];
    }

    /**
     * One belongs-to relation to the entity $target, through the column $key.
     *
     * @return array<int, mixed>
     */
    private function relation(string $target, string $key): array
    {
        return [
            Relation::TYPE => Relation::BELONGS_TO,
            Relation::TARGET => $target,
            Relation::SCHEMA => [
                Relation::CASCADE => false,
                Relation::NULLABLE => true,
                Relation::INNER_KEY => $key,
                Relation::OUTER_KEY => 'id',
            ],
        ];
    }

    private function orm(): ORM
    {
        return new ORM(new Factory($this->manager()), new Schema([
            'client' => $this->entity('client', ['id', 'client_name', 'client_surname']),
            'group' => $this->entity('grp', ['id']),
            'user' => $this->entity('usr', ['id']),
            'quote' => $this->entity(
                'quote',
                ['id', 'client_id', 'group_id', 'user_id', 'status_id'],
                [
                    'client' => $this->relation('client', 'client_id'),
                    'group' => $this->relation('group', 'group_id'),
                    'user' => $this->relation('user', 'user_id'),
                ],
            ),
            'sales_order' => $this->entity(
                'sales_order',
                ['id', 'client_id', 'group_id', 'user_id', 'quote_id'],
                [
                    'client' => $this->relation('client', 'client_id'),
                    'group' => $this->relation('group', 'group_id'),
                    'user' => $this->relation('user', 'user_id'),
                    'quote' => $this->relation('quote', 'quote_id'),
                ],
            ),
        ]));
    }

    private function manager(): DatabaseManager
    {
        return $this->manager ?? throw new \LogicException('database() first');
    }

    /** @return list<int> the ids a reader returns, in its own order */
    private static function ids(EntityReader $reader): array
    {
        $ids = [];
        foreach ($reader->read() as $row) {
            $ids[] = is_object($row) ? (int) ($row->id ?? 0) : 0;
        }
        sort($ids);

        return $ids;
    }

    /** @return list<int> */
    private function quotes(?string $filterClient): array
    {
        $this->database();
        /** @var Select<Quote> $select */
        $select = new Select($this->orm(), 'quote');
        $repository = new QuoteRepository($select, TypedMock::of(EntityWriter::class), new Translator());
        $filter = new QuoteIndexFilter();
        $filter->filterClient = $filterClient;

        return self::ids($repository->filterCombined($filter, [], 0));
    }

    /** @return list<int> */
    private function salesOrders(string $filterClient): array
    {
        $this->database();
        /** @var Select<SalesOrder> $select */
        $select = new Select($this->orm(), 'sales_order');
        $repository = new SalesOrderRepository($select, TypedMock::of(EntityWriter::class), new Translator());

        return self::ids($repository->filterClient($filterClient));
    }

    public function theQuoteListFiltersByClientIdSoTwoClientsWithTheSameNameAreSeparate(): void
    {
        Assert::same($this->quotes('1'), [1]);
        Assert::same($this->quotes('2'), [2]);
        Assert::same($this->quotes('3'), [3]);
    }

    public function noQuoteClientFilterListsEveryQuote(): void
    {
        Assert::same($this->quotes(null), [1, 2, 3]);
        Assert::same($this->quotes(''), [1, 2, 3]);
    }

    public function anOldQuoteAddressWithANameStillWorksAndStillCoversBothSameNamedClients(): void
    {
        Assert::same($this->quotes('Virtual Tester'), [1, 2]);
        Assert::same($this->quotes('Test Other'), [3]);
    }

    public function theSalesOrderListFiltersByClientIdSoTwoClientsWithTheSameNameAreSeparate(): void
    {
        Assert::same($this->salesOrders('1'), [1]);
        Assert::same($this->salesOrders('2'), [2]);
        Assert::same($this->salesOrders('3'), []);
    }

    public function anOldSalesOrderAddressWithANameStillWorks(): void
    {
        Assert::same($this->salesOrders('Virtual Tester'), [1, 2]);
    }
}
