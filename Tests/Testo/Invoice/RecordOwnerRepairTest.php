<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice;

use App\Command\Invoice\RepairRecordOwnersCommand;
use App\Invoice\RecordOwnerRepair;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLite\MemoryConnectionConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Symfony\Component\Console\Tester\CommandTester;
use Testo\Assert;
use Testo\Test;

/**
 * Quotes, sales orders and invoices moved to another client before the fix kept the old client's user. The repair
 * finds them and gives them to their client's user, and the command shows what it would do unless --force. Run on an
 * in-memory SQLite database with the real queries; the data is virtual (ids and numbers only):
 *
 *   clients: 1 -> user 10 (active), 2 -> user 20 (active), 3 -> user 30 (INACTIVE), 4 -> nobody
 *   quote 1   client 1  user 10   fine
 *   quote 2   client 1  user 20   moved from client 2: repair to 10
 *   so 5      client 2  user 10   moved from client 1: repair to 20
 *   inv 7     client 1  user 20   repair to 10
 *   inv 8     client 1  user 20   deleted: left alone
 *   inv 9     client 3  user 10   the client's user is inactive: reported, not repaired
 *   inv 10    client 4  user 10   the client has no user: not a candidate
 *   inv 11    client 2  user 20   fine
 */
#[Test]
final class RecordOwnerRepairTest
{
    private ?DatabaseInterface $db = null;
    private ?RecordOwnerRepair $repair = null;

    private function setUpDatabase(bool $withMismatches = true): void
    {
        $manager = new DatabaseManager(new DatabaseConfig([
            'default' => 'default',
            'databases' => ['default' => ['connection' => 'sqlite']],
            'connections' => ['sqlite' => new SQLiteDriverConfig(connection: new MemoryConnectionConfig())],
        ]));
        $db = $manager->database();
        foreach ([
            'CREATE TABLE user_client (id INTEGER PRIMARY KEY, user_id INTEGER, client_id INTEGER)',
            'CREATE TABLE user_inv (id INTEGER PRIMARY KEY, user_id INTEGER, active INTEGER)',
            'CREATE TABLE quote (id INTEGER PRIMARY KEY, number TEXT, client_id INTEGER, user_id INTEGER)',
            'CREATE TABLE sales_order (id INTEGER PRIMARY KEY, number TEXT, client_id INTEGER, user_id INTEGER)',
            'CREATE TABLE inv (id INTEGER PRIMARY KEY, number TEXT, client_id INTEGER, user_id INTEGER, deleted_at TEXT)',
        ] as $sql) {
            $db->execute($sql);
        }
        foreach ([[1, 10], [2, 20], [3, 30]] as [$client, $user]) {
            $db->insert('user_client')->values(['client_id' => $client, 'user_id' => $user])->run();
        }
        foreach ([[10, 1], [20, 1], [30, 0]] as [$user, $active]) {
            $db->insert('user_inv')->values(['user_id' => $user, 'active' => $active])->run();
        }
        $rows = [
            'quote' => [[1, 'QUO-1', 1, 10], [2, 'QUO-2', 1, $withMismatches ? 20 : 10]],
            'sales_order' => [[5, 'SO-5', 2, $withMismatches ? 10 : 20]],
        ];
        foreach ($rows as $table => $records) {
            foreach ($records as [$id, $number, $client, $user]) {
                $db->insert($table)->values(['id' => $id, 'number' => $number, 'client_id' => $client, 'user_id' => $user])->run();
            }
        }
        // id, number, client, user, deleted_at
        $invoices = [
            [7, 'INV-7', 1, $withMismatches ? 20 : 10, null],
            [8, 'INV-8', 1, 20, '2026-01-01 00:00:00'],
            [9, 'INV-9', 3, $withMismatches ? 10 : 30, null],
            [10, 'INV-10', 4, 10, null],
            [11, 'INV-11', 2, 20, null],
        ];
        foreach ($invoices as [$id, $number, $client, $user, $deleted]) {
            $db->insert('inv')->values([
                'id' => $id, 'number' => $number, 'client_id' => $client, 'user_id' => $user, 'deleted_at' => $deleted,
            ])->run();
        }

        $this->db = $db;
        $this->repair = new RecordOwnerRepair($manager);
    }

    private function repair(): RecordOwnerRepair
    {
        return $this->repair ?? throw new \LogicException('setUpDatabase() first');
    }

    private function userOf(string $table, int $id): int
    {
        $db = $this->db ?? throw new \LogicException('setUpDatabase() first');
        /** @var list<array<string, mixed>> $rows */
        $rows = $db->select('user_id')->from($table)->where('id', $id)->fetchAll();

        return (int) ($rows[0]['user_id'] ?? 0);
    }

    /** @return list<string> a readable line per mismatch, e.g. "quote 2 repair" */
    private function summary(): array
    {
        return array_map(
            static fn (array $m): string => $m['table'] . ' ' . $m['id'] . ($m['repairable'] ? ' repair' : ' skip'),
            $this->repair()->findMismatches(),
        );
    }

    private function runCommand(bool $force): CommandTester
    {
        // SymfonyStyle wraps to the terminal width, which differs on CI: a fixed wide terminal keeps the sentences whole
        putenv('COLUMNS=200');
        $tester = new CommandTester(new RepairRecordOwnersCommand($this->repair()));
        $tester->execute($force ? ['--force' => true] : []);

        return $tester;
    }

    private function display(CommandTester $tester): string
    {
        return (string) preg_replace('/\s+/', ' ', $tester->getDisplay());
    }

    public function itFindsTheRecordsWhoseOwnerIsNotTheirClientsUser(): void
    {
        $this->setUpDatabase();

        // not quote 1, inv 11 (fine), inv 8 (deleted) or inv 10 (its client has no user)
        Assert::same($this->summary(), ['quote 2 repair', 'sales_order 5 repair', 'inv 7 repair', 'inv 9 skip']);
    }

    public function itGivesEachRepairableRecordToItsClientsUserAndLeavesTheRest(): void
    {
        $this->setUpDatabase();

        $changed = $this->repair()->repair($this->repair()->findMismatches());

        Assert::same($changed, ['quote' => 1, 'sales_order' => 1, 'inv' => 1]);
        Assert::same($this->userOf('quote', 2), 10);
        Assert::same($this->userOf('sales_order', 5), 20);
        Assert::same($this->userOf('inv', 7), 10);
        // an inactive owner, a deleted invoice and a client with no user are not touched
        Assert::same($this->userOf('inv', 9), 10);
        Assert::same($this->userOf('inv', 8), 20);
        Assert::same($this->userOf('inv', 10), 10);
    }

    public function repairingTwiceChangesNothingTheSecondTime(): void
    {
        $this->setUpDatabase();
        $this->repair()->repair($this->repair()->findMismatches());

        Assert::same($this->summary(), ['inv 9 skip']);
        Assert::same($this->repair()->repair($this->repair()->findMismatches()), []);
    }

    public function aRecordWhoseOwnerChangedSinceItWasListedIsLeftAlone(): void
    {
        $this->setUpDatabase();
        $listed = $this->repair()->findMismatches();
        // somebody moves quote 2 again after the list was made
        $this->db?->update('quote', ['user_id' => 99], ['id' => 2])->run();

        $changed = $this->repair()->repair($listed);

        Assert::same($this->userOf('quote', 2), 99);
        Assert::same($changed, ['sales_order' => 1, 'inv' => 1]);
    }

    public function theCommandOnlyListsWithoutForce(): void
    {
        $this->setUpDatabase();

        $tester = $this->runCommand(false);
        $display = $this->display($tester);

        Assert::same($tester->getStatusCode(), 0);
        Assert::true(str_contains($display, '3 of 4 record(s) would be given to their client'));
        Assert::true(str_contains($display, 'setting/backup-database'));
        Assert::true(str_contains($display, 'the client\'s user is inactive'));
        Assert::same($this->userOf('quote', 2), 20);
        Assert::same($this->userOf('inv', 7), 20);
    }

    public function theCommandRepairsWithForceAndSaysWhatItLeft(): void
    {
        $this->setUpDatabase();

        $display = $this->display($this->runCommand(true));

        Assert::true(str_contains($display, 'Repaired: 1 quote, 1 sales_order, 1 inv.'));
        Assert::true(str_contains($display, '1 record(s) were left as they are'));
        Assert::same($this->userOf('quote', 2), 10);
        Assert::same($this->userOf('inv', 7), 10);
    }

    public function theCommandSaysSoWhenThereIsNothingToRepair(): void
    {
        $this->setUpDatabase(false);
        // inv 9 belongs to user 30 here, so nothing is out of step at all (inv 10 has no client user and is ignored)
        $display = $this->display($this->runCommand(true));

        Assert::true(str_contains($display, 'Nothing to repair'));
    }
}
