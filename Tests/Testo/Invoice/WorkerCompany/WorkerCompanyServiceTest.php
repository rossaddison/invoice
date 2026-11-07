<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\WorkerCompany;

use App\Infrastructure\Persistence\WorkerCompany\WorkerCompany;
use App\Invoice\Worker\WorkerForm;
use App\Invoice\WorkerCompany\WorkerCompanyRepositoryInterface;
use App\Invoice\WorkerCompany\WorkerCompanyService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * The WorkerCompany link table: worker.id + company.id as a combined primary key.
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

        $link = (new WorkerCompanyService($repository))->link(7, 2);

        Assert::same($saved, [[7, 2]]);
        Assert::same($link->reqCompanyId(), 2);
    }

    public function linkingTheSamePairAgainChangesNothing(): void
    {
        $existing = new WorkerCompany(7, 2);
        $repository = $this->repository([$existing]);
        $repository->shouldNotReceive('save');

        Assert::same((new WorkerCompanyService($repository))->link(7, 2), $existing);
    }

    public function aWorkerMayBeLinkedToMoreThanOneBusinessAtTheTableLevel(): void
    {
        $repository = $this->repository([new WorkerCompany(7, 1)]);
        $repository->shouldReceive('save')->once();
        $service = new WorkerCompanyService($repository);

        $service->link(7, 2);

        Assert::same($service->companyIdsFor(7), [1]);
    }

    public function unlinkingDeletesOnlyThatPair(): void
    {
        $keep = new WorkerCompany(7, 1);
        $drop = new WorkerCompany(7, 2);
        $repository = $this->repository([$keep, $drop]);
        $repository->shouldReceive('delete')->once()->with($drop);

        (new WorkerCompanyService($repository))->unlink(7, 2);
    }

    public function unlinkingAPairThatDoesNotExistDoesNothing(): void
    {
        $repository = $this->repository([new WorkerCompany(7, 1)]);
        $repository->shouldNotReceive('delete');

        (new WorkerCompanyService($repository))->unlink(7, 9);
    }

    public function unlinkingAWorkerRemovesEveryLinkTheyHave(): void
    {
        $a = new WorkerCompany(7, 1);
        $b = new WorkerCompany(7, 2);
        $other = new WorkerCompany(8, 1);
        $repository = $this->repository([$a, $b, $other]);
        $repository->shouldReceive('delete')->once()->with($a);
        $repository->shouldReceive('delete')->once()->with($b);

        (new WorkerCompanyService($repository))->unlinkWorker(7);
    }

    public function theFormsChoiceLeavesTheWorkerLinkedToExactlyThatBusiness(): void
    {
        $old = new WorkerCompany(7, 1);
        $repository = $this->repository([$old]);
        $repository->shouldReceive('delete')->once()->with($old);
        $saved = [];
        $repository->shouldReceive('save')->once()->andReturnUsing(static function (WorkerCompany $link) use (&$saved): void {
            $saved[] = [$link->reqWorkerId(), $link->reqCompanyId()];
        });

        (new WorkerCompanyService($repository))->syncFromChoice(7, '2');

        Assert::same($saved, [[7, 2]]);
    }

    public function choosingTheBusinessTheWorkerAlreadyHasChangesNothing(): void
    {
        $repository = $this->repository([new WorkerCompany(7, 2)]);
        $repository->shouldNotReceive('save');
        $repository->shouldNotReceive('delete');

        (new WorkerCompanyService($repository))->syncFromChoice(7, '2');
    }

    public function anEmptyOrNonsenseChoiceMeansNoBusiness(): void
    {
        foreach (['', '0', '-3', 'abc'] as $choice) {
            $link = new WorkerCompany(7, 1);
            $repository = $this->repository([$link]);
            $repository->shouldReceive('delete')->once()->with($link);
            $repository->shouldNotReceive('save');

            (new WorkerCompanyService($repository))->syncFromChoice(7, $choice);
        }
    }

    public function theLookupsReadTheLinkTableBothWays(): void
    {
        $service = new WorkerCompanyService($this->repository([
            new WorkerCompany(7, 1),
            new WorkerCompany(7, 2),
            new WorkerCompany(8, 2),
        ]));

        Assert::same($service->companyIdsFor(7), [1, 2]);
        Assert::same($service->workerIdsFor(2), [7, 8]);
        Assert::same($service->companyIdsByWorker(), [7 => [1, 2], 8 => [2]]);
        Assert::same($service->companyIdsFor(99), []);
    }

    public function theWorkerFormShowsTheChosenBusinessOrNone(): void
    {
        $worker = new \App\Infrastructure\Persistence\Worker\Worker('Dave', 'Smith');

        Assert::same(WorkerForm::show($worker, 2)->getCompanyId(), '2');
        Assert::same(WorkerForm::show($worker, null)->getCompanyId(), '');
        Assert::same(WorkerForm::show($worker)->getCompanyId(), '');
    }
}
