<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Client;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\Client\ClientDwellingRepository;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLite\MemoryConnectionConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\Select;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Select is mocked (not the repository) -- see
 * BookkeepingTransactionLineRepositoryTest for why a mocked Select still
 * behaves after Select\Repository::select() clones it.
 */
#[Test]
final class ClientDwellingRepositoryTest
{
    private function client(int $id, ?int $dwellingId): Client
    {
        $client = new Client();
        $client->setId($id);
        if ($dwellingId !== null) {
            $client->setDwellingId($dwellingId);
        }
        return $client;
    }

    /**
     * @param list<Client> $clients
     */
    private function repository(array $where, array $clients): ClientDwellingRepository
    {
        /** @var Select<Client>&m\MockInterface $select */
        $select = m::mock(Select::class);
        $select->shouldReceive('where')->once()->with($where)->andReturnSelf();
        $select->shouldReceive('fetchAll')->andReturn($clients);
        return new ClientDwellingRepository($select);
    }

    public function claimedIdsAreEveryNonNullDwellingId(): void
    {
        $repository = $this->repository(
            ['dwelling_id' => ['!=' => null]],
            [$this->client(1, 10), $this->client(2, 20)]
        );

        Assert::same($repository->repoClaimedDwellingIds(), [10, 20]);
    }

    public function dwellingIsClaimedByAnotherClient(): void
    {
        $repository = $this->repository(['dwelling_id' => 10], [$this->client(1, 10)]);

        Assert::true($repository->isClaimedByAnother(10, 2));
    }

    public function clientsOwnDwellingIsNotClaimedByAnother(): void
    {
        $repository = $this->repository(['dwelling_id' => 10], [$this->client(1, 10)]);

        Assert::false($repository->isClaimedByAnother(10, 1));
    }

    public function newClientCannotTakeAClaimedDwelling(): void
    {
        $repository = $this->repository(['dwelling_id' => 10], [$this->client(1, 10)]);

        Assert::true($repository->isClaimedByAnother(10, null));
    }

    public function unclaimedDwellingIsFree(): void
    {
        $repository = $this->repository(['dwelling_id' => 10], []);

        Assert::false($repository->isClaimedByAnother(10, null));
    }

    /**
     * Regression: the claimed-ids filter was once written ['not' => null],
     * which Cycle compiles to the invalid SQL "dwelling_id NOT NULL" and
     * crashed the Client edit page (MySQL error 1064 near 'NULL'). This runs
     * the exact where() array the repository uses through Cycle's real SQL
     * compiler and executes it.
     */
    public function claimedIdsFilterCompilesAndRunsAsIsNotNull(): void
    {
        $databases = new DatabaseManager(new DatabaseConfig([
            'default' => 'default',
            'databases' => ['default' => ['connection' => 'sqlite']],
            'connections' => ['sqlite' => new SQLiteDriverConfig(connection: new MemoryConnectionConfig())],
        ]));
        $database = $databases->database();
        $database->execute('CREATE TABLE client (id INTEGER PRIMARY KEY, dwelling_id INTEGER NULL)');
        $database->execute('INSERT INTO client (id, dwelling_id) VALUES (1, 10), (2, NULL), (3, 30)');

        $query = $database->select('id')->from('client')->where(['dwelling_id' => ['!=' => null]]);

        Assert::true(str_contains((string) $query, 'IS NOT NULL'));
        Assert::same(array_map('intval', array_column($query->fetchAll(), 'id')), [1, 3]);
    }
}
