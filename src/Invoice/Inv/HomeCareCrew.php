<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\HomeCareSchedule\RunCalendarService;
use App\Invoice\Setting\SettingRepository;

/**
 * Today's HomeCare window-cleaning crew (two or three workers, the lead
 * included) and whether houses are allocated by Front/Back or whole. There is
 * exactly one lead who allocates, so this is a pair of global settings rather
 * than per-user state. The inv/guest chips and crew form read it; the lead
 * saves it from the crew form.
 *
 * The run calendar fills it in: when the calendar names a crew for today, that crew is today's crew
 * until the lead saves one himself today, which always wins. With no calendar crew for today the
 * last saved crew stays, as before.
 */
final readonly class HomeCareCrew
{
    public const string KEY_WORKER_IDS = 'homecare_crew_worker_ids';
    public const string KEY_FRONT_BACK = 'homecare_crew_front_back';

    /** The date (Y-m-d) the lead last saved the crew, so a crew saved today overrides the calendar. */
    public const string KEY_SAVED_ON = 'homecare_crew_saved_on';

    /** A crew is never more than three. */
    public const int MAX_SIZE = 3;

    /**
     * @param string|null $today Y-m-d; null means the real date (a test can fix it)
     */
    public function __construct(
        private SettingRepository $sR,
        private ?RunCalendarService $calendar = null,
        private ?string $today = null,
    ) {
    }

    /**
     * Today's crew: the one the lead saved today, else the run calendar's crew for today, else the
     * last saved crew. In that order, at most three distinct worker ids.
     *
     * @return list<int>
     */
    public function workerIds(): array
    {
        $saved = $this->savedIds();
        $calendar = $this->calendarIds();
        if ([] === $calendar || ([] !== $saved && $this->savedToday())) {
            return $saved;
        }
        return $calendar;
    }

    /**
     * True when today's crew came from the run calendar rather than from what the lead saved.
     */
    public function fromCalendar(): bool
    {
        return [] !== $this->calendarIds() && !([] !== $this->savedIds() && $this->savedToday());
    }

    /**
     * The crew the run calendar names for today (empty: none, or no calendar).
     *
     * @return list<int>
     */
    private function calendarIds(): array
    {
        return null === $this->calendar ? [] : $this->calendar->crewFor($this->today());
    }

    private function savedToday(): bool
    {
        return $this->today() === $this->sR->getSetting(self::KEY_SAVED_ON);
    }

    private function today(): string
    {
        return $this->today ?? date('Y-m-d');
    }

    /**
     * The saved crew, in saved order, at most three distinct worker ids.
     *
     * @return list<int>
     */
    private function savedIds(): array
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
        $this->persist(self::KEY_SAVED_ON, $this->today());
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
