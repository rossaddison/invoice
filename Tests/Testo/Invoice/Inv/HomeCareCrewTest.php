<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Inv\HomeCareCrew;
use App\Invoice\Setting\SettingRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Today's crew (two or three workers) and the Front/Back mode, kept as two
 * global settings.
 */
#[Test]
final class HomeCareCrewTest
{
    private function settings(string $ids, string $frontBack = '0'): SettingRepository&m\MockInterface
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('getSetting')->with(HomeCareCrew::KEY_WORKER_IDS)->andReturn($ids);
        $sR->shouldReceive('getSetting')->with(HomeCareCrew::KEY_FRONT_BACK)->andReturn($frontBack);
        return $sR;
    }

    public function readsTheSavedCrewInOrder(): void
    {
        Assert::same((new HomeCareCrew($this->settings('3,1,2')))->workerIds(), [3, 1, 2]);
    }

    public function ignoresBlanksJunkDuplicatesAndAnythingPastThree(): void
    {
        Assert::same((new HomeCareCrew($this->settings('')))->workerIds(), []);
        Assert::same((new HomeCareCrew($this->settings('x, 0, -2, 4, 4, 5, 6, 7')))->workerIds(), [4, 5, 6]);
    }

    public function frontBackIsOnlyOnForTheExactValueOne(): void
    {
        Assert::true((new HomeCareCrew($this->settings('', '1')))->frontBack());
        Assert::false((new HomeCareCrew($this->settings('', '0')))->frontBack());
        Assert::false((new HomeCareCrew($this->settings('', '')))->frontBack());
        Assert::false((new HomeCareCrew($this->settings('', 'yes')))->frontBack());
    }

    public function savingKeepsOnlyDistinctActiveWorkersUpToThreeInTheOrderGiven(): void
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $existingIds = new Setting(HomeCareCrew::KEY_WORKER_IDS, '9');
        $existingMode = new Setting(HomeCareCrew::KEY_FRONT_BACK, '0');
        $sR->shouldReceive('withKey')->with(HomeCareCrew::KEY_WORKER_IDS)->andReturn($existingIds);
        $sR->shouldReceive('withKey')->with(HomeCareCrew::KEY_FRONT_BACK)->andReturn($existingMode);
        $sR->shouldReceive('save')->twice();
        $sR->shouldReceive('setSetting')->with(HomeCareCrew::KEY_WORKER_IDS, '2,1,4');
        $sR->shouldReceive('setSetting')->with(HomeCareCrew::KEY_FRONT_BACK, '1');

        // 99 is not active, the second 2 is a duplicate, 5 is a fourth member
        (new HomeCareCrew($sR))->save([2, 99, 1, 2, 4, 5], true, [1, 2, 4, 5]);

        Assert::same($existingIds->getSettingValue(), '2,1,4');
        Assert::same($existingMode->getSettingValue(), '1');
    }

    public function savingCreatesTheSettingRowsWhenTheyDoNotExistYet(): void
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('withKey')->andReturn(null);
        $saved = [];
        $sR->shouldReceive('save')->twice()->andReturnUsing(static function (Setting $setting) use (&$saved): void {
            $saved[$setting->getSettingKey()] = $setting->getSettingValue();
        });
        $sR->shouldReceive('setSetting');

        (new HomeCareCrew($sR))->save([7, 8], false, [7, 8]);

        Assert::same($saved, [HomeCareCrew::KEY_WORKER_IDS => '7,8', HomeCareCrew::KEY_FRONT_BACK => '0']);
    }

    public function savingAnEmptyCrewClearsIt(): void
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $sR->shouldReceive('withKey')->andReturn(null);
        $saved = [];
        $sR->shouldReceive('save')->twice()->andReturnUsing(static function (Setting $setting) use (&$saved): void {
            $saved[$setting->getSettingKey()] = $setting->getSettingValue();
        });
        $sR->shouldReceive('setSetting');

        (new HomeCareCrew($sR))->save([99], false, [1, 2]);

        Assert::same($saved[HomeCareCrew::KEY_WORKER_IDS], '');
    }
}
