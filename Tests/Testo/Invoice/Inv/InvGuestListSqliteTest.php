<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Inv\InvGuestFilter;
use App\Invoice\Inv\InvRepository;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLite\MemoryConnectionConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\Factory;
use Cycle\ORM\Mapper\Mapper;
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
 * The guest invoice list against a real (in-memory SQLite) database, through the real InvRepository, so the actual
 * SQL runs: the scope, every filter, the relation joins behind the amount filters, and the worker allocation.
 * The companion InvGuestListFilterScopeTest only checks which conditions are added; this checks what comes back.
 *
 * Only the tables the guest list touches exist, with the same relation names as the real Inv entity (client,
 * invAmount). The data is virtual: ids and invoice numbers only.
 *
 *   guest A is assigned clients 1 and 2; client 3 belongs to somebody else; client 4 is inactive
 *   inv 1  client 1  INV-1001  sent      total 100.00 paid   0.00 balance 100.00
 *   inv 2  client 2  INV-1002  viewed    total 250.00 paid 250.00 balance   0.00
 *   inv 3  client 3  INV-1003  sent      total 100.00  (someone else's)
 *   inv 4  client 1  INV-1004  DRAFT     (never shown to a client guest)
 *   inv 5  client 1  INV-1005  sent, deleted
 *   inv 6  client 4  INV-1006  sent      (client inactive)
 *   inv 7  client 1  CRN-0001  credit note of inv 1
 *   inv 8  client 3  CRN-0002  credit note of inv 3 (someone else's)
 *   worker 9 is allocated inv 3 first, then inv 1 (inv 1 by worker_id, inv 3 by an allocation row); inv 2 is nobody's
 */
#[Test]
final class InvGuestListSqliteTest
{
    private const array GUEST_CLIENTS = [1, 2];

    private function repository(): InvRepository
    {
        $manager = new DatabaseManager(new DatabaseConfig([
            'default' => 'default',
            'databases' => ['default' => ['connection' => 'sqlite']],
            'connections' => ['sqlite' => new SQLiteDriverConfig(connection: new MemoryConnectionConfig())],
        ]));
        $db = $manager->database();
        $this->createTables($db);
        $this->seed($db);

        $orm = new ORM(new Factory($manager), new Schema([
            'client' => $this->entity('client', ['id', 'client_active', 'dwelling_id']),
            'inv_amount' => $this->entity('inv_amount', ['id', 'inv_id', 'total', 'paid', 'balance']),
            'inv' => $this->entity(
                'inv',
                [
                    'id', 'number', 'client_id', 'status_id', 'deleted_at', 'worker_id', 'worker_allocated_at',
                    'creditinvoice_parent_id',
                ],
                [
                    'client' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => 'client',
                        Relation::SCHEMA => [
                            Relation::CASCADE => false,
                            Relation::NULLABLE => false,
                            Relation::INNER_KEY => 'client_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                    'invAmount' => [
                        Relation::TYPE => Relation::HAS_ONE,
                        Relation::TARGET => 'inv_amount',
                        Relation::SCHEMA => [
                            Relation::CASCADE => false,
                            Relation::NULLABLE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'inv_id',
                        ],
                    ],
                ],
            ),
        ]));

        /** @var Select<Inv> $select */
        $select = new Select($orm, 'inv');

        return new InvRepository($select, TypedMock::of(EntityWriter::class), new Translator());
    }

    /**
     * @param list<string> $columns
     * @param array<string, array<int|string, mixed>> $relations
     *
     * @return array<int, mixed>
     */
    private function entity(string $table, array $columns, array $relations = []): array
    {
        // the invoice rows are InvRowStub (it has reqId(), which the credit-note lookup calls); the others are plain
        $invoice = $table === 'inv';
        return [
            Schema::ENTITY => $invoice ? InvRowStub::class : \stdClass::class,
            Schema::MAPPER => $invoice ? Mapper::class : StdMapper::class,
            Schema::DATABASE => 'default',
            Schema::TABLE => $table,
            Schema::PRIMARY_KEY => 'id',
            Schema::COLUMNS => array_combine($columns, $columns),
            Schema::TYPECAST => [],
            Schema::RELATIONS => $relations,
        ];
    }

    private function createTables(DatabaseInterface $db): void
    {
        foreach ([
            'CREATE TABLE client (id INTEGER PRIMARY KEY, client_active INTEGER, dwelling_id INTEGER)',
            'CREATE TABLE inv_amount (id INTEGER PRIMARY KEY, inv_id INTEGER, total REAL, paid REAL, balance REAL)',
            'CREATE TABLE inv_worker_allocation (id INTEGER PRIMARY KEY, inv_id INTEGER, worker_id INTEGER)',
            'CREATE TABLE inv (id INTEGER PRIMARY KEY, number TEXT, client_id INTEGER, status_id INTEGER,'
            . ' deleted_at TEXT, worker_id INTEGER, worker_allocated_at TEXT, creditinvoice_parent_id INTEGER)',
        ] as $sql) {
            $db->execute($sql);
        }
    }

    private function seed(DatabaseInterface $db): void
    {
        foreach ([1 => 1, 2 => 1, 3 => 1, 4 => 0] as $id => $active) {
            $db->insert('client')->values(['id' => $id, 'client_active' => $active, 'dwelling_id' => null])->run();
        }
        // id, number, client, status, deleted_at, worker_id, parent, total, paid, balance
        $rows = [
            [1, 'INV-1001', 1, 2, null, 9, null, 100.00, 0.00, 100.00],
            [2, 'INV-1002', 2, 3, null, null, null, 250.00, 250.00, 0.00],
            [3, 'INV-1003', 3, 2, null, null, null, 100.00, 0.00, 100.00],
            [4, 'INV-1004', 1, 1, null, null, null, 40.00, 0.00, 40.00],
            [5, 'INV-1005', 1, 2, '2026-01-01 00:00:00', null, null, 60.00, 0.00, 60.00],
            [6, 'INV-1006', 4, 2, null, null, null, 70.00, 0.00, 70.00],
            [7, 'CRN-0001', 1, 2, null, null, 1, 10.00, 0.00, 10.00],
            [8, 'CRN-0002', 3, 2, null, null, 3, 20.00, 0.00, 20.00],
        ];
        /** @var array<int, string> $allocatedAt */
        $allocatedAt = [1 => '2026-01-02 08:00:00', 3 => '2026-01-01 08:00:00'];
        foreach ($rows as [$id, $number, $client, $status, $deleted, $worker, $parent, $total, $paid, $balance]) {
            $db->insert('inv')->values([
                'id' => $id, 'number' => $number, 'client_id' => $client, 'status_id' => $status,
                'deleted_at' => $deleted, 'worker_id' => $worker,
                // the worker's order: inv 3 was allocated before inv 1
                'worker_allocated_at' => $allocatedAt[$id] ?? null,
                'creditinvoice_parent_id' => $parent,
            ])->run();
            $db->insert('inv_amount')->values([
                'id' => $id, 'inv_id' => $id, 'total' => $total, 'paid' => $paid, 'balance' => $balance,
            ])->run();
        }
        $db->insert('inv_worker_allocation')->values(['id' => 1, 'inv_id' => 3, 'worker_id' => 9])->run();
    }

    /** @return list<int> the invoice ids a reader returns, in its own order */
    private static function ids(EntityReader $reader): array
    {
        $ids = [];
        foreach ($reader->read() as $row) {
            $ids[] = is_object($row) ? (int) ($row->id ?? 0) : 0;
        }
        return $ids;
    }

    private function filter(
        string $number = '',
        string $client = '',
        string $total = '',
        string $paid = '',
        string $balance = '',
        string $credit = '',
    ): InvGuestFilter {
        $filter = new InvGuestFilter();
        $filter->filterInvNumber = $number;
        $filter->filterClient = $client;
        $filter->filterInvAmountTotal = $total;
        $filter->filterInvAmountPaid = $paid;
        $filter->filterInvAmountBalance = $balance;
        $filter->filterCreditInvNumber = $credit;

        return $filter;
    }

    /** @return list<int> */
    private function guestList(InvGuestFilter $filter, int $status = 0): array
    {
        return self::ids($this->repository()->repoGuestClientsPostDraft($status, self::GUEST_CLIENTS, $filter));
    }

    /** @return list<int> */
    private function workerList(InvGuestFilter $filter, int $status = 0): array
    {
        return self::ids($this->repository()->repoWorkerVisible($status, 9, false, $filter));
    }

    public function theFixtureCanSeeALeak(): void
    {
        // the old unscoped filter methods still exist on the repository: they find the other guest's invoice, which
        // proves this data would show it if the guest list ever used them again
        Assert::same(self::ids($this->repository()->filterInvNumber('INV-1003')), [3]);
    }

    public function withNoFilterTheGuestSeesOnlyTheirClientsSentInvoices(): void
    {
        // not inv 3 (another client), 4 (draft), 5 (deleted), 6 (inactive client), 8 (another client's credit note)
        Assert::same($this->guestList($this->filter()), [7, 2, 1]);
    }

    public function anInvoiceNumberOfAnotherClientFindsNothing(): void
    {
        Assert::same($this->guestList($this->filter(number: 'INV-1003')), []);
        Assert::same($this->guestList($this->filter(number: 'INV-1001')), [1]);
    }

    public function aClientFilterOnAnotherClientFindsNothingAndOnTheirOwnNarrows(): void
    {
        Assert::same($this->guestList($this->filter(client: '3')), []);
        Assert::same($this->guestList($this->filter(client: '2')), [2]);
        Assert::same($this->guestList($this->filter(client: '1')), [7, 1]);
    }

    public function theAmountFiltersNarrowToTheGuestsOwnInvoices(): void
    {
        // 100.00 is on inv 1 and on another client's inv 3: only the guest's own comes back
        Assert::same($this->guestList($this->filter(total: '100')), [1]);
        Assert::same($this->guestList($this->filter(paid: '250')), [2]);
        Assert::same($this->guestList($this->filter(balance: '100')), [1]);
    }

    public function anInvoiceNumberTogetherWithATotalMustMatchBoth(): void
    {
        Assert::same($this->guestList($this->filter(number: 'INV-1001', total: '100')), [1]);
        Assert::same($this->guestList($this->filter(number: 'INV-1001', total: '250')), []);
    }

    public function aCreditNoteFilterOnlyReturnsTheGuestsOwnCreditNotes(): void
    {
        // both credit notes' parents match 'INV-1', but credit note 8 belongs to another client
        Assert::same($this->guestList($this->filter(credit: 'INV-1')), [7]);
        Assert::same($this->guestList($this->filter(credit: 'NO-SUCH')), []);
    }

    public function severalFiltersTogetherAllApply(): void
    {
        Assert::same($this->guestList($this->filter(client: '1', total: '100')), [1]);
        Assert::same($this->guestList($this->filter(client: '2', total: '100')), []);
    }

    public function theStatusTabKeepsTheScopeWhenFiltered(): void
    {
        Assert::same($this->guestList($this->filter(), 3), [2]);
        Assert::same($this->guestList($this->filter(number: 'INV-1003'), 2), []);
        Assert::same($this->guestList($this->filter(number: 'INV-1001'), 2), [1]);
    }

    public function emptyAndZeroFiltersAreNoFilter(): void
    {
        Assert::same($this->guestList($this->filter(number: '', client: '0', total: '0', balance: '0')), [7, 2, 1]);
    }

    public function aWorkerSeesOnlyWhatIsAllocatedToThemAndFiltersNarrowThat(): void
    {
        // inv 1 by worker_id and inv 3 by an allocation row; never inv 2, which is nobody's
        Assert::same($this->workerList($this->filter()), [3, 1]);
        Assert::same($this->workerList($this->filter(number: 'INV-1002')), []);
        Assert::same($this->workerList($this->filter(number: 'INV-1003')), [3]);
        Assert::same($this->workerList($this->filter(client: '2')), []);
    }
}

/** An invoice row as the real Inv entity looks to the repository: public columns and reqId(). */
final class InvRowStub
{
    public int $id = 0;
    public ?string $number = null;
    public int $client_id = 0;
    public int $status_id = 0;
    public ?string $deleted_at = null;
    public ?int $worker_id = null;
    public ?string $worker_allocated_at = null;
    public ?int $creditinvoice_parent_id = null;
    public ?object $client = null;
    public ?object $invAmount = null;

    public function reqId(): int
    {
        return $this->id;
    }
}
