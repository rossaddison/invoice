<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Task;

use App\Infrastructure\Persistence\Project\Project;
use App\Infrastructure\Persistence\Task\Task;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Invoice\Project\ProjectRepository as PR;
use App\Invoice\Task\TaskRepository;
use App\Invoice\Task\TaskService;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use Mockery as m;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

/**
 * Covers TaskService: saveTask's project/tax-rate relation persistence and
 * field assignment, and deleteTask.
 *
 * See PR #998 "Possible issues found": saveTask() unconditionally does
 * `$model->setFinishDate($datetime::createFromFormat('Y-m-d', $finish_date))`
 * with no isset guard and no fallback for a failed parse. When
 * `finish_date` is absent (`$array['finish_date'] ?? ''`) or not a valid
 * `Y-m-d` string, `DateTime::createFromFormat()` returns `false`, and
 * passing `false` to `setFinishDate(?DateTime $finish_date)` throws a
 * TypeError immediately under strict_types — so saveTask() can never
 * succeed for a task without an already-valid finish date, unlike every
 * other optional field in this method. Separately, `getFinishDate(): string
 * |DateTimeImmutable` doesn't accept the plain `\DateTime` that
 * setFinishDate() actually stores, so reading it back afterward would
 * throw too (same family as the Payment/Merchant/InvRecurring bugs above)
 * — reflection is used to avoid exercising that second crash.
 */
#[Test]
final class TaskServiceTest
{
    private function finishDateProperty(Task $model): mixed
    {
        $property = new \ReflectionProperty(Task::class, 'finish_date');
        return $property->getValue($model);
    }

    private function makeService(
        ?TaskRepository $repository = null,
        ?PR $pR = null,
        ?TRR $trR = null,
    ): TaskService {
        /** @var TaskRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(TaskRepository::class);
        /** @var PR&m\MockInterface $pR */
        $pR = $pR ?? m::mock(PR::class);
        /** @var TRR&m\MockInterface $trR */
        $trR = $trR ?? m::mock(TRR::class);
        return new TaskService($repository, $pR, $trR);
    }

    #[ExpectException(\TypeError::class)]
    public function saveTaskThrowsWhenFinishDateMissing(): void
    {
        $model = new Task();

        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $pR->shouldNotReceive('repoProjectquery');
        /** @var TRR&m\MockInterface $trR */
        $trR = m::mock(TRR::class);
        $trR->shouldNotReceive('repoTaxRatequery');
        /** @var TaskRepository&m\MockInterface $repository */
        $repository = m::mock(TaskRepository::class);
        $repository->shouldNotReceive('save');

        $this->makeService($repository, $pR, $trR)->saveTask($model, []);
    }

    public function saveTaskSetsAllFieldsAndSavesWhenFinishDateValid(): void
    {
        $model = new Task();
        $array = [
            'project_id' => 5,
            'tax_rate_id' => 2,
            'name' => 'Design mockups',
            'description' => 'Create initial design mockups',
            'price' => 250.00,
            'status' => 1,
            'finish_date' => '2026-06-15',
        ];

        /** @var Project&m\MockInterface $project */
        $project = m::mock(Project::class);
        $reqIdExpectation = $project->shouldReceive('reqId');
        $reqIdExpectation->andReturn(5);
        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $e = $pR->shouldReceive('repoProjectquery');
        $e->once()->with(5)->andReturn($project);

        /** @var TaxRate&m\MockInterface $taxRate */
        $taxRate = m::mock(TaxRate::class);
        $taxRateReqIdExpectation = $taxRate->shouldReceive('reqId');
        $taxRateReqIdExpectation->andReturn(2);
        /** @var TRR&m\MockInterface $trR */
        $trR = m::mock(TRR::class);
        $e2 = $trR->shouldReceive('repoTaxRatequery');
        $e2->once()->with(2)->andReturn($taxRate);

        /** @var TaskRepository&m\MockInterface $repository */
        $repository = m::mock(TaskRepository::class);
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $pR, $trR);
        $service->saveTask($model, $array);

        Assert::same($project, $model->getProject());
        Assert::same(5, $model->reqProjectId());
        Assert::same($taxRate, $model->getTaxRate());
        Assert::same(2, $model->reqTaxRateId());
        Assert::same('Design mockups', $model->getName());
        Assert::same('Create initial design mockups', $model->getDescription());
        Assert::same(250.00, $model->getPrice());
        Assert::same(1, $model->getStatus());

        $finishDate = $this->finishDateProperty($model);
        Assert::true($finishDate instanceof \DateTime);
        Assert::same('2026-06-15', $finishDate->format('Y-m-d'));
    }

    public function saveTaskDefaultsPriceToZeroWhenMissing(): void
    {
        $model = new Task();
        $array = ['finish_date' => '2026-06-15'];

        /** @var PR&m\MockInterface $pR */
        $pR = m::mock(PR::class);
        $pR->shouldNotReceive('repoProjectquery');
        /** @var TRR&m\MockInterface $trR */
        $trR = m::mock(TRR::class);
        $trR->shouldNotReceive('repoTaxRatequery');

        /** @var TaskRepository&m\MockInterface $repository */
        $repository = m::mock(TaskRepository::class);
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $pR, $trR);
        $service->saveTask($model, $array);

        Assert::same(0.00, $model->getPrice());
    }

    public function deleteTaskCallsRepositoryDelete(): void
    {
        $model = new Task();

        /** @var TaskRepository&m\MockInterface $repository */
        $repository = m::mock(TaskRepository::class);
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteTask($model);
    }
}
