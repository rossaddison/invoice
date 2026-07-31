<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Qa;

use App\Infrastructure\Persistence\Qa\Qa;
use App\Invoice\Qa\QaRepository;
use App\Invoice\Qa\QaService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers QaService: saveQa's field assignment (including the always-set
 * active flag) and deleteQa.
 */
#[Test]
final class QaServiceTest
{
    private function makeService(?QaRepository $repository = null): QaService
    {
        $repository = $repository ?? m::mock(QaRepository::class);
        return new QaService($repository);
    }

    public function saveQaSetsAllFieldsAndSaves(): void
    {
        $model = new Qa();
        $array = [
            'active' => '1',
            'question' => 'How do I reset my password?',
            'answer' => 'Use the forgot password link on the login page.',
        ];

        /** @var QaRepository&m\MockInterface $repository */
        $repository = m::mock(QaRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveQa($model, $array);

        Assert::same(1, $model->getActive());
        Assert::same('How do I reset my password?', $model->getQuestion());
        Assert::same('Use the forgot password link on the login page.', $model->getAnswer());
    }

    public function saveQaSetsActiveToZeroWhenNotOne(): void
    {
        $model = new Qa();
        $array = ['active' => '0'];

        /** @var QaRepository&m\MockInterface $repository */
        $repository = m::mock(QaRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveQa($model, $array);

        Assert::same(0, $model->getActive());
        Assert::same('', $model->getQuestion());
    }

    public function deleteQaCallsRepositoryDelete(): void
    {
        $model = new Qa();

        /** @var QaRepository&m\MockInterface $repository */
        $repository = m::mock(QaRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteQa($model);
    }
}
