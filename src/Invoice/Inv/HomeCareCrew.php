<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Setting\SettingRepository;

/**
 * Today's HomeCare window-cleaning crew (two or three workers, the lead
 * included) and whether houses are allocated by Front/Back or whole. There is
 * exactly one lead who allocates, so this is a pair of global settings rather
 * than per-user state. The inv/guest chips and crew form read it; the lead
 * saves it from the crew form.
 */
final readonly class HomeCareCrew
{
    public const string KEY_WORKER_IDS = 'homecare_crew_worker_ids';
    public const string KEY_FRONT_BACK = 'homecare_crew_front_back';

    /** A crew is never more than three. */
    public const int MAX_SIZE = 3;

    public function __construct(private SettingRepository $sR)
    {
    }

    /**
     * The saved crew, in saved order, at most three distinct worker ids.
     *
     * @return list<int>
     */
    public function workerIds(): array
    {
        $ids = [];
        foreach (explode(',', $this->sR->getSetting(self::KEY_WORKER_IDS)) as $raw) {
            $id = (int) trim($raw);
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
        return array_slice($ids, 0, self::MAX_SIZE);
    }

    /**
     * True when houses are allocated Front and Back separately.
     */
    public function frontBack(): bool
    {
        return '1' === $this->sR->getSetting(self::KEY_FRONT_BACK);
    }

    /**
     * Saves the crew, keeping only distinct active workers (at most three) in
     * the order given.
     *
     * @param list<int> $requested worker ids chosen on the crew form
     * @param list<int> $activeWorkerIds ids of the active workers
     */
    public function save(array $requested, bool $frontBack, array $activeWorkerIds): void
    {
        $crew = [];
        foreach ($requested as $id) {
            if (in_array($id, $activeWorkerIds, true) && !in_array($id, $crew, true)) {
                $crew[] = $id;
            }
        }
        $crew = array_slice($crew, 0, self::MAX_SIZE);
        $this->persist(self::KEY_WORKER_IDS, implode(',', $crew));
        $this->persist(self::KEY_FRONT_BACK, $frontBack ? '1' : '0');
    }

    /**
     * Creates the setting row when it does not exist yet (existing installs
     * only get a row for a new key once the settings page is saved).
     */
    private function persist(string $key, string $value): void
    {
        $setting = $this->sR->withKey($key) ?? new Setting($key, $value);
        $setting->setSettingValue($value);
        $this->sR->save($setting);
        $this->sR->setSetting($key, $value);
    }
}
