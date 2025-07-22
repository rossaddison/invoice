<?php

declare(strict_types=1);

namespace App\Invoice\InvRecurring;

use App\Invoice\Entity\InvRecurring;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Setting\SettingRepository;

final readonly class InvRecurringService
{
    public function __construct(private InvRecurringRepository $repository, private InvRepository $invR, private SettingRepository $s)
    {
    }

    public function saveInvRecurring(InvRecurring $model, array $array): void
    {
        $model->setInv_id((int) $array['inv_id']);

        isset($array['frequency']) ? $model->setFrequency((string) $array['frequency']) : '';

        $baseInvoice = $this->invR->repoInvUnloadedquery((string) $array['inv_id']);
        if (null !== $baseInvoice) {
            $dateHelper = new DateHelper($this->s);

            // Next is not null because currently running
            // The start has been adjusted
            // A new next = start + frequency
            $invNext = $model->getNext();
            if (null !== $invNext && !is_string($invNext) && isset($array['start'])) {
                $nextDate = $dateHelper->incrementDateStringToDateTime((string) $array['start'], (string) $array['frequency']);
                $model->setNext($nextDate);
                $model->setStart(new \DateTime((string) $array['start']));
            }

            // Next is null because it has stopped
            // Restart => allow new start and new next
            // A new next = start + frequency
            if (null == $invNext && isset($array['start'])) {
                $nextDate = $dateHelper->incrementDateStringToDateTime((string) $array['start'], (string) $array['frequency']);
                $model->setNext($nextDate);
                $model->setStart(new \DateTime((string) $array['start']));
            }

            /**
             * @var string|null $array['end']
             */
            $end = isset($array['end']) ? new \DateTime($array['end']) : null;
            $end ? $model->setEnd($end) : '';

            $this->repository->save($model);
        }
    }

    public function deleteInvRecurring(InvRecurring $model): void
    {
        $this->repository->delete($model);
    }
}
