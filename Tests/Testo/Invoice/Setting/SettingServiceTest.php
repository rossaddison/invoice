<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Setting;

use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Setting\SettingService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers SettingService: saveSetting's field assignment and deleteSetting.
 */
#[Test]
final class SettingServiceTest
{
    private function makeService(?SettingRepository $repository = null): SettingService
    {
        $repository = $repository ?? m::mock(SettingRepository::class);
        return new SettingService($repository);
    }

    public function saveSettingSetsAllFieldsAndSaves(): void
    {
        $model = new Setting();
        $array = [
            'setting_key' => 'timezone',
            'setting_value' => 'Europe/London',
        ];

        /** @var SettingRepository&m\MockInterface $repository */
        $repository = m::mock(SettingRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveSetting($model, $array);

        Assert::same('timezone', $model->getSettingKey());
        Assert::same('Europe/London', $model->getSettingValue());
    }

    public function saveSettingSkipsFieldsWhenMissing(): void
    {
        $model = new Setting();

        /** @var SettingRepository&m\MockInterface $repository */
        $repository = m::mock(SettingRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveSetting($model, []);

        Assert::same('', $model->getSettingKey());
        Assert::same('', $model->getSettingValue());
    }

    public function deleteSettingCallsRepositoryDelete(): void
    {
        $model = new Setting();

        /** @var SettingRepository&m\MockInterface $repository */
        $repository = m::mock(SettingRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteSetting($model);
    }
}
