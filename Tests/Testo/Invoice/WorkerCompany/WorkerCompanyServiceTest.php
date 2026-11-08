<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\WorkerCompany;

use App\Infrastructure\Persistence\Company\Company;
use App\Infrastructure\Persistence\Worker\Worker;
use App\Infrastructure\Persistence\WorkerCompany\WorkerCompany;
use App\Invoice\Company\CompanyRepository;
use App\Invoice\Worker\WorkerForm;
use App\Invoice\Worker\WorkerRepository;
use App\Invoice\WorkerCompany\WorkerCompanyRepositoryInterface;
use App\Invoice\WorkerCompany\WorkerCompanyService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * The WorkerCompany link table (worker.id + company.id as a combined primary key) and the default
 * company every worker belongs to (the current company).
 */
#[Test]
final class WorkerCompanyServiceTest
{
    /**
     * @param list<WorkerCompany> $existing every link currently stored
     */
    private function repository(array $existing): WorkerCompanyRepositoryInterface&m\MockInterface
    {
        /** @var WorkerCompanyRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(WorkerCompanyRepositoryInterface::class);
        $repository->shouldReceive('repoLinkquery')->andReturnUsing(
            static function (int $workerId, int $companyId) use ($existing): ?WorkerCompany {
                foreach ($existing as $link) {
                    if ($link->reqWorkerId() === $workerId && $link->reqCompanyId() === $companyId) {
                        return $link;
                    }
                }
                return null;
            }
        );
        $repository->shouldReceive('repoWorkerquery')->andReturnUsing(
            static fn (int $workerId): array => array_values(array_filter(
                $existing,
                static fn (WorkerCompany $link): bool => $link->reqWorkerId() === $workerId
            ))
        );
        $repository->shouldReceive('repoCompanyquery')->andReturnUsing(
            static fn (int $companyId): array => array_values(array_filter(
                $existing,
                static fn (WorkerCompany $link): bool => $link->reqCompanyId() === $companyId
            ))
        );
        $repository->shouldReceive('repoAllquery')->andReturn($existing);
        return $repository;
    }

    /**
     * @param list<int> $workerIds every worker that exists
     * @param int|null $default the current company (the default every worker belongs to), if any
     */
    private function service(
        WorkerCompanyRepositoryInterface $repository,
        array $workerIds = [7, 8],
        ?int $default = null,
    ): WorkerCompanyService {
        $workers = array_map(static function (int $id): Worker {
            $worker = new Worker('W' . $id, 'Test');
            $worker->setId($id);
            return $worker;
        }, $workerIds);
        /** @var EntityReader&m\MockInterface $reader */
        $reader = m::mock(EntityReader::class);
        $reader->shouldReceive('read')->andReturn($workers);
        /** @var WorkerRepository&m\MockInterface $workerRepository */
        $workerRepository = m::mock(WorkerRepository::class);
        $workerRepository->shouldReceive('findAllPreloaded')->andReturn($reader);
        /** @var CompanyRepository&m\MockInterface $companies */
        $companies = m::mock(CompanyRepository::class);
        $companies->shouldReceive('repoCompanyActivequery')->andReturn(null === $default ? null : new Company(id: $default, current: 1));
        return new WorkerCompanyService($repository, $workerRepository, $companies);
    }

    public function aLinkIsExactlyThePairOfKeys(): void
    {
        $link = new WorkerCompany(7, 2);

        Assert::same($link->reqWorkerId(), 7);
        Assert::same($link->reqCompanyId(), 2);
    }

    public function linkingAWorkerToABusinessSavesOneNewRow(): void
    {
        $repository = $this->repository([]);
        $saved = [];
        $repository->shouldReceive('save')->once()->andReturnUsing(static function (WorkerCompany $link) use (&$saved): void {
            $saved[] = [$link->reqWorkerId(), $link->reqCompanyId()];
        });

        $link = $this->service($repository)->link(7, 2);

        Assert::same($saved, [[7, 2]]);
        Assert::same($link->reqCompanyId(), 2);
    }

    public function linkingTheSamePairAgainChangesNothing(): void
    {
        $existing = new WorkerCompany(7, 2);
        $repository = $this->repository([$existing]);
        $repository->shouldNotReceive('save');

        Assert::same($this->service($repository)->link(7, 2), $existing);
    }

    public function unlinkingDeletesOnlyThatPair(): void
    {
        $keep = new WorkerCompany(7, 1);
        $drop = new WorkerCompany(7, 2);
        $repository = $this->repository([$keep, $drop]);
        $repository->shouldReceive('delete')->once()->with($drop);

        $this->service($repository)->unlink(7, 2);
    }

    public function unlinkingAPairThatDoesNotExistDoesNothing(): void
    {
        $repository = $this->repository([new WorkerCompany(7, 1)]);
        $repository->shouldNotReceive('delete');

        $this->service($repository)->unlink(7, 9);
    }

    public function unlinkingAWorkerRemovesEveryLinkTheyHave(): void
    {
        $a = new WorkerCompany(7, 1);
        $b = new WorkerCompany(7, 2);
        $other = new WorkerCompany(8, 1);
        $repository = $this->repository([$a, $b, $other]);
        $repository->shouldReceive('delete')->once()->with($a);
        $repository->shouldReceive('delete')->once()->with($b);

        $this->service($repository)->unlinkWorker(7);
    }

    public function theTickedBusinessesBecomeTheWorkersLinksExactly(): void
    {
        $keep = new WorkerCompany(7, 1);
        $drop = new WorkerCompany(7, 3);
        $repository = $this->repository([$keep, $drop]);
        $repository->shouldReceive('delete')->once()->with($drop);
        $saved = [];
        $repository->shouldReceive('save')->once()->andReturnUsing(static function (WorkerCompany $link) use (&$saved): void {
            $saved[] = [$link->reqWorkerId(), $link->reqCompanyId()];
        });

        // ticked: 1 (already linked, left alone) and 2 (new); 3 is no longer ticked
        $this->service($repository)->syncCompanies(7, ['1', '2']);

        Assert::same($saved, [[7, 2]]);
    }

    public function aWorkerCanBeRegisteredUnderSeveralBusinessesAtOnce(): void
    {
        $repository = $this->repository([]);
        $saved = [];
        $repository->shouldReceive('save')->twice()->andReturnUsing(static function (WorkerCompany $link) use (&$saved): void {
            $saved[] = [$link->reqWorkerId(), $link->reqCompanyId()];
        });

        $this->service($repository)->syncCompanies(7, ['1', '2']);

        Assert::same($saved, [[7, 1], [7, 2]]);
    }

    public function tickingWhatTheWorkerAlreadyHasChangesNothing(): void
    {
        $repository = $this->repository([new WorkerCompany(7, 1), new WorkerCompany(7, 2)]);
        $repository->shouldNotReceive('save');
        $repository->shouldNotReceive('delete');

        $this->service($repository)->syncCompanies(7, [1, 2]);
    }

    public function aDuplicatedTickIsLinkedOnce(): void
    {
        $repository = $this->repository([]);
        $repository->shouldReceive('save')->once();

        $this->service($repository)->syncCompanies(7, ['2', 2, '2']);
    }

    public function ticksThatAreNotPositiveIdsAreIgnoredAndNothingTickedClearsTheLinks(): void
    {
        foreach ([[], [''], ['0'], ['-3'], ['abc'], [0]] as $choices) {
            $link = new WorkerCompany(7, 1);
            $repository = $this->repository([$link]);
            $repository->shouldReceive('delete')->once()->with($link);
            $repository->shouldNotReceive('save');

            $this->service($repository)->syncCompanies(7, $choices);
        }
    }

    public function everyWorkerBelongsToTheDefaultCompanyWithoutAnyStoredRow(): void
    {
        $service = $this->service($this->repository([]), [7, 8], 5);

        Assert::same($service->defaultCompanyId(), 5);
        Assert::same($service->companyIdsFor(7), [5]);
        Assert::same($service->companyIdsFor(8), [5]);
        Assert::same($service->workerIdsFor(5), [7, 8]);
        Assert::same($service->companyIdsByWorker(), [7 => [5], 8 => [5]]);
    }

    public function theDefaultComesFirstThenTheWorkersOtherBusinesses(): void
    {
        $service = $this->service($this->repository([new WorkerCompany(7, 9), new WorkerCompany(7, 2)]), [7, 8], 5);

        Assert::same($service->companyIdsFor(7), [5, 2, 9]);
        Assert::same($service->companyIdsByWorker(), [7 => [5, 2, 9], 8 => [5]]);
        // the form ticks only the extras: the default is shown separately
        Assert::same($service->storedCompanyIdsFor(7), [9, 2]);
    }

    public function aStoredLinkToTheDefaultIsNotListedTwice(): void
    {
        $service = $this->service($this->repository([new WorkerCompany(7, 5), new WorkerCompany(7, 2)]), [7], 5);

        Assert::same($service->companyIdsFor(7), [5, 2]);
        Assert::same($service->storedCompanyIdsFor(7), [2]);
    }

    public function savingTheFormNeverAddsOrRemovesTheDefault(): void
    {
        $toDefault = new WorkerCompany(7, 5);
        $extra = new WorkerCompany(7, 2);
        $repository = $this->repository([$toDefault, $extra]);
        // 2 is unticked and removed; the stored link to the default (5) is kept, and a tick of the
        // default itself adds nothing
        $repository->shouldReceive('delete')->once()->with($extra);
        $repository->shouldNotReceive('save');

        $this->service($repository, [7], 5)->syncCompanies(7, ['5']);
    }

    public function theDefaultFollowsTheCurrentCompanyWhenItChanges(): void
    {
        $repository = $this->repository([new WorkerCompany(7, 2)]);

        Assert::same($this->service($repository, [7], 5)->companyIdsFor(7), [5, 2]);
        // the run's company is now 2: it is the default, and 5 is no longer implied
        Assert::same($this->service($repository, [7], 2)->companyIdsFor(7), [2]);
        Assert::same($this->service($repository, [7, 8], 2)->workerIdsFor(2), [7, 8]);
    }

    public function withNoCurrentCompanyThereIsNoDefaultAndOnlyStoredLinksCount(): void
    {
        $service = $this->service($this->repository([new WorkerCompany(7, 2)]), [7, 8], null);

        Assert::null($service->defaultCompanyId());
        Assert::same($service->companyIdsFor(7), [2]);
        Assert::same($service->companyIdsFor(8), []);
        Assert::same($service->workerIdsFor(2), [7]);
        Assert::same($service->companyIdsByWorker(), [7 => [2], 8 => []]);
    }

    public function aNonDefaultBusinessOnlyHasItsStoredWorkers(): void
    {
        $service = $this->service(
            $this->repository([new WorkerCompany(7, 2), new WorkerCompany(8, 2), new WorkerCompany(7, 3)]),
            [7, 8, 9],
            5,
        );

        Assert::same($service->workerIdsFor(2), [7, 8]);
        Assert::same($service->workerIdsFor(3), [7]);
        Assert::same($service->companyIdsFor(99), [5]);
    }

    public function theWorkerFormShowsEveryBusinessTheWorkerIsRegisteredUnder(): void
    {
        $worker = new Worker('Dave', 'Smith');

        Assert::same(WorkerForm::show($worker, [1, 2])->getCompanyIds(), ['1', '2']);
        Assert::same(WorkerForm::show($worker, [])->getCompanyIds(), []);
        Assert::same(WorkerForm::show($worker)->getCompanyIds(), []);
    }
}
