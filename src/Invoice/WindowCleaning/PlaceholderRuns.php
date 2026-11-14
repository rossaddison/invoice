<?php

declare(strict_types=1);

namespace App\Invoice\WindowCleaning;

use App\Infrastructure\Persistence\CategoryPrimary\CategoryPrimary;
use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Invoice\CategoryPrimary\CategoryPrimaryService;
use App\Invoice\CategorySecondary\CategorySecondaryService;
use App\Invoice\Family\FamilyService;
use Cycle\Database\DatabaseManager;

/**
 * Creates the placeholder areas, runs and streets of a new window-cleaning install: for each run an
 * area ("Example Area 01"), the run in it ("Example Run 01") and STREETS_PER_RUN streets under the run
 * ("Street 1 on Run 1" to "Street 30 on Run 1"), so the sign up form's run dropdown is not empty and
 * each run has streets to work with.
 *
 * All of it happens in one database transaction: if a save fails part way nothing is kept, so the
 * install is never left with half a set of runs that the "no run yet" check in WindowCleaningPreset
 * would then mistake for the business's own.
 */
final readonly class PlaceholderRuns
{
    public const int STREETS_PER_RUN = 30;

    public function __construct(
        private DatabaseManager $dbal,
        private CategoryPrimaryService $cpS,
        private CategorySecondaryService $csS,
        private FamilyService $fS,
    ) {
    }

    /**
     * @param int $runs how many runs, each in an area of its own
     * @return array{areas: int, runs: int, streets: int} how many were created
     */
    public function create(int $runs): array
    {
        return $this->dbal->database()->transaction(fn (): array => $this->createAll($runs));
    }

    /**
     * @return array{areas: int, runs: int, streets: int}
     */
    private function createAll(int $runs): array
    {
        $streets = 0;
        for ($n = 1; $n <= $runs; $n++) {
            $area = new CategoryPrimary();
            $this->cpS->saveCategoryPrimary($area, ['name' => sprintf('Example Area %02d', $n)]);
            $run = new CategorySecondary();
            $this->csS->saveCategorySecondary($run, [
                'category_primary_id' => (string) $area->reqId(),
                'name' => sprintf('Example Run %02d', $n),
            ]);
            for ($s = 1; $s <= self::STREETS_PER_RUN; $s++) {
                $this->fS->findOrCreateByStreetName(sprintf('Street %d on Run %d', $s, $n), $run->reqId());
                ++$streets;
            }
        }
        return ['areas' => $runs, 'runs' => $runs, 'streets' => $streets];
    }
}
