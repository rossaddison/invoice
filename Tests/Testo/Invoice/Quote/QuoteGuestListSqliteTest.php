<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Quote;

use App\Infrastructure\Persistence\Quote\Quote;
use App\Invoice\Quote\QuoteRepository;
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
 * The guest quote list against a real (in-memory SQLite) database, through the real QuoteRepository, so the actual
 * SQL runs: the scope, both filters, and the relation join behind the amount filter. The companion
 * QuoteGuestListFilterScopeTest only checks which conditions are added; this checks what comes back.
 *
 * The data is virtual: ids and quote numbers only.
 *
 *   guest A is assigned clients 1 and 2; client 3 belongs to somebody else
 *   quote 1  client 1  QUO-1001  sent       total 100.00
 *   quote 2  client 2  QUO-1002  viewed     total 250.00
 *   quote 3  client 3  QUO-1003  sent       total 100.00  (someone else's)
 *   quote 4  client 1  QUO-1004  DRAFT      total  40.00  (never shown to a client guest)
 *   quote 5  client 1  QUO-1005  cancelled  total  60.00
 */
#[Test]
final class QuoteGuestListSqliteTest
{
    private const array GUEST_CLIENTS = [1, 2];

    private function repository(): QuoteRepository
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
            'quote_amount' => $this->entity('quote_amount', ['id', 'quote_id', 'total']),
            'quote' => $this->entity('quote', ['id', 'number', 'client_id', 'status_id'], [
                'quoteAmount' => [
                    Relation::TYPE => Relation::HAS_ONE,
                    Relation::TARGET => 'quote_amount',
                    Relation::SCHEMA => [
                        Relation::CASCADE => false,
                        Relation::NULLABLE => true,
                        Relation::INNER_KEY => 'id',
                        Relation::OUTER_KEY => 'quote_id',
                    ],
                ],
            ]),
        ]));

        /** @var Select<Quote> $select */
        $select = new Select($orm, 'quote');

        return new QuoteRepository($select, TypedMock::of(EntityWriter::class), new Translator());
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

    private function createTables(DatabaseInterface $db): void
    {
        foreach ([
            'CREATE TABLE quote_amount (id INTEGER PRIMARY KEY, quote_id INTEGER, total REAL)',
            'CREATE TABLE quote (id INTEGER PRIMARY KEY, number TEXT, client_id INTEGER, status_id INTEGER)',
        ] as $sql) {
            $db->execute($sql);
        }
    }

    private function seed(DatabaseInterface $db): void
    {
        // id, number, client, status, total
        $rows = [
            [1, 'QUO-1001', 1, 2, 100.00],
            [2, 'QUO-1002', 2, 3, 250.00],
            [3, 'QUO-1003', 3, 2, 100.00],
            [4, 'QUO-1004', 1, 1, 40.00],
            [5, 'QUO-1005', 1, 6, 60.00],
        ];
        foreach ($rows as [$id, $number, $client, $status, $total]) {
            $db->insert('quote')->values([
                'id' => $id, 'number' => $number, 'client_id' => $client, 'status_id' => $status,
            ])->run();
            $db->insert('quote_amount')->values(['id' => $id, 'quote_id' => $id, 'total' => $total])->run();
        }
    }

    /** @return list<int> the quote ids a reader returns, in its own order */
    private static function ids(EntityReader $reader): array
    {
        $ids = [];
        foreach ($reader->read() as $row) {
            $ids[] = is_object($row) ? (int) ($row->id ?? 0) : 0;
        }
        return $ids;
    }

    /** @return list<int> */
    private function guestList(string $number = '', string $total = '', int $status = 0): array
    {
        return self::ids(
            $this->repository()->repoGuestClientsSentViewedApprovedRejectedCancelled(
                $status,
                self::GUEST_CLIENTS,
                $number,
                $total,
            ),
        );
    }

    public function theFixtureCanSeeALeak(): void
    {
        // the old unscoped filter method still exists on the repository: it finds the other guest's quote, which
        // proves this data would show it if the guest list ever used it again
        Assert::same(self::ids($this->repository()->filterQuoteNumber('QUO-1003')), [3]);
    }

    public function withNoFilterTheGuestSeesOnlyTheirClientsSentToCancelledQuotes(): void
    {
        // not quote 3 (another client) or 4 (a draft)
        Assert::same($this->guestList(), [5, 2, 1]);
    }

    public function aQuoteNumberOfAnotherClientFindsNothing(): void
    {
        Assert::same($this->guestList(number: 'QUO-1003'), []);
        Assert::same($this->guestList(number: 'QUO-1001'), [1]);
    }

    public function aDraftNumberFindsNothing(): void
    {
        Assert::same($this->guestList(number: 'QUO-1004'), []);
    }

    public function anAmountTotalNarrowsToTheGuestsOwnQuotes(): void
    {
        // 100.00 is on quote 1 and on another client's quote 3: only the guest's own comes back
        Assert::same($this->guestList(total: '100'), [1]);
        Assert::same($this->guestList(total: '250'), [2]);
    }

    public function aNumberAndATotalTogetherMustMatchBoth(): void
    {
        Assert::same($this->guestList(number: 'QUO-1001', total: '100'), [1]);
        Assert::same($this->guestList(number: 'QUO-1001', total: '250'), []);
    }

    public function theStatusTabKeepsTheScopeWhenFiltered(): void
    {
        Assert::same($this->guestList(status: 3), [2]);
        Assert::same($this->guestList(number: 'QUO-1003', status: 2), []);
        Assert::same($this->guestList(number: 'QUO-1001', status: 2), [1]);
    }

    public function blankFiltersAreNoFilter(): void
    {
        Assert::same($this->guestList(number: '  ', total: ''), [5, 2, 1]);
    }
}
