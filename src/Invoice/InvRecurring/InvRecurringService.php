<?php

declare(strict_types=1);

namespace App\Invoice\InvRecurring;

use App\Infrastructure\Persistence\InvRecurring\InvRecurring;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Inv\InvRepository;

final readonly class InvRecurringService
{
    /**
     * @param InvRecurringRepository $repository
     */
    public function __construct(
        private InvRecurringRepository $repository,
        private InvRepository $invR,
        private SettingRepository $s,
    ) {
    }

    /**
     * @param InvRecurring $model
     * @param array $array
     */
    public function saveInvRecurring(
        InvRecurring $model,
        array $array
    ): void {
        // 'inv_id' is read via isset(), not a bare $array['inv_id'], since
        // it's genuinely allowed to be absent here (e.g. an unguarded
        // read used to warn on missing key -- confirmed live via
        // InvRecurringController::add(), whose hidden inv_id form field
        // submits empty when the controller forgets to pre-populate the
        // form, which is exactly the bug that read masked instead of
        // surfacing). Resolved ONCE and reused for both the `inv`
        // relation and the base-invoice gate below -- the two used to be
        // two separate repoInvUnLoadedquery() calls for the same id.
        $invId = isset($array['inv_id']) ? (int) $array['inv_id'] : null;
        $baseInvoice = null !== $invId
            ? $this->invR->repoInvUnLoadedquery($invId)
            : null;

        if (null !== $baseInvoice) {
            $model->setInv($baseInvoice);
        }
        if (null !== $invId) {
            $model->setInvId($invId);
        }

        isset($array['frequency']) ?
            $model->setFrequency(
                (string) $array['frequency']
            ) : '';

        if (null !== $baseInvoice) {
            $dateHelper = new DateHelper($this->s);

            // Next is not null because currently running
            // The start has been adjusted
            // A new next = start + frequency
            $invNext = $model->getNext();
            if (null !== $invNext
                && !is_string($invNext)
                && isset($array['start'])) {
                $nextDate = $dateHelper
                    ->incrementDateStringToDateTime(
                        (string) $array['start'],
                        (string) $array['frequency']
                    );
                $model->setNext($nextDate);
                $model->setStart(
                    new \DateTime((string) $array['start'])
                );
            }

            // Next is null because it has stopped
            // Restart => allow new start and new next
            // A new next = start + frequency
            if (null == $invNext && isset($array['start'])) {
                $nextDate = $dateHelper
                    ->incrementDateStringToDateTime(
                        (string) $array['start'],
                        (string) $array['frequency']
                    );
                $model->setNext($nextDate);
                $model->setStart(
                    new \DateTime((string) $array['start'])
                );
            }

            /**
             * @var string|null $array['end']
             */
            $end = isset($array['end']) ?
                new \DateTime($array['end']) : null;
            $end ? $model->setEnd($end) : '';

            $this->repository->save($model);
        }
    }

    /**
     * @param InvRecurring $model
     */
    public function deleteInvRecurring(InvRecurring $model): void
    {
        $this->repository->delete($model);
    }
}
