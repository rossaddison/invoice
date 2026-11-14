<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\WindowCleaning;

use App\Infrastructure\Persistence\CategoryPrimary\CategoryPrimary;
use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Infrastructure\Persistence\Family\Family;
use App\Invoice\CategoryPrimary\CategoryPrimaryService;
use App\Invoice\CategorySecondary\CategorySecondaryService;
use App\Invoice\Family\FamilyService;
use App\Invoice\WindowCleaning\PlaceholderRuns;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Mockery as m;
use RuntimeException;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

/**
 * The placeholder areas, runs and streets of a new window-cleaning install, created together in one
 * database transaction or not at all.
 */
#[Test]
final class PlaceholderRunsTest
{
    /** @var list<string> the names of the areas created */
    private array $areas = [];

    /** @var list<array{category_primary_id: string, name: string}> the runs created */
    private array $runs = [];

    /** @var list<array{string, int}> the streets created: name and the id of the run they are under */
    private array $streets = [];

    /** @var int how many transactions were opened */
    private int $transactions = 0;

    /**
     * @param int|null $failOnStreet the number of the street that fails when it is saved (none by default)
     */
    private function placeholderRuns(?int $failOnStreet = null): PlaceholderRuns
    {
        $this->areas = [];
        $this->runs = [];
        $this->streets = [];
        $this->transactions = 0;

        /** @var DatabaseInterface&m\MockInterface $database */
        $database = m::mock(DatabaseInterface::class);
        $database->shouldReceive('transaction')->andReturnUsing(function (callable $callback): array {
            ++$this->transactions;
            /** @var array{areas: int, runs: int, streets: int} */
            return $callback();
        });
        /** @var DatabaseManager&m\MockInterface $dbal */
        $dbal = m::mock(DatabaseManager::class);
        $dbal->shouldReceive('database')->andReturn($database);

        /** @var CategoryPrimaryService&m\MockInterface $cpS */
        $cpS = m::mock(CategoryPrimaryService::class);
        $cpS->shouldReceive('saveCategoryPrimary')->andReturnUsing(function (CategoryPrimary $area, array $array): void {
            $this->areas[] = (string) $array['name'];
            $area->setId(count($this->areas));
        });
        /** @var CategorySecondaryService&m\MockInterface $csS */
        $csS = m::mock(CategorySecondaryService::class);
        $csS->shouldReceive('saveCategorySecondary')->andReturnUsing(function (CategorySecondary $run, array $array): void {
            /** @var array{category_primary_id: string, name: string} $array */
            $this->runs[] = $array;
            $run->setId(count($this->runs));
        });
        /** @var FamilyService&m\MockInterface $fS */
        $fS = m::mock(FamilyService::class);
        $fS->shouldReceive('findOrCreateByStreetName')->andReturnUsing(function (string $name, int $runId) use ($failOnStreet): Family {
            if (count($this->streets) + 1 === $failOnStreet) {
                throw new RuntimeException('The database went away.');
            }
            $this->streets[] = [$name, $runId];
            return new Family();
        });

        return new PlaceholderRuns($dbal, $cpS, $csS, $fS);
    }

    public function itCreatesAnAreaAndARunForEachRun(): void
    {
        $done = $this->placeholderRuns()->create(16);

        Assert::same($done, ['areas' => 16, 'runs' => 16, 'streets' => 480]);
        Assert::count($this->areas, 16);
        Assert::count($this->runs, 16);
        Assert::same($this->areas[0], 'Example Area 01');
        Assert::same($this->runs[0], ['category_primary_id' => '1', 'name' => 'Example Run 01']);
        Assert::same($this->runs[15], ['category_primary_id' => '16', 'name' => 'Example Run 16']);
    }

    public function itCreatesThirtyStreetsUnderEachRun(): void
    {
        $this->placeholderRuns()->create(16);

        Assert::count($this->streets, 480);
        Assert::same($this->streets[0], ['Street 1 on Run 1', 1]);
        Assert::same($this->streets[29], ['Street 30 on Run 1', 1]);
        Assert::same($this->streets[30], ['Street 1 on Run 2', 2]);
        Assert::same($this->streets[479], ['Street 30 on Run 16', 16]);
    }

    public function theNumberOfRunsCanBeChosen(): void
    {
        $done = $this->placeholderRuns()->create(4);

        Assert::same($done, ['areas' => 4, 'runs' => 4, 'streets' => 120]);
    }

    public function allOfItHappensInOneTransaction(): void
    {
        $this->placeholderRuns()->create(16);

        Assert::same($this->transactions, 1);
    }

    #[ExpectException(RuntimeException::class)]
    public function aFailurePartWayIsNotSwallowedSoTheTransactionRollsBack(): void
    {
        // the 45th street, in the second run, fails: the exception reaches the transaction untouched
        $this->placeholderRuns(failOnStreet: 45)->create(16);
    }

    public function afterAFailureOnlyTheWorkInsideTheOneTransactionWasStarted(): void
    {
        try {
            $this->placeholderRuns(failOnStreet: 45)->create(16);
        } catch (RuntimeException) {
            // expected: the rollback of everything saved so far is the database's job
        }

        Assert::same($this->transactions, 1);
        Assert::count($this->streets, 44);
    }
}
