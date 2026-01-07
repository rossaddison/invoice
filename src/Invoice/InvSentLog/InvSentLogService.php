<?php

declare(strict_types=1);

namespace App\Invoice\InvSentLog;

use App\Invoice\Entity\InvSentLog;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Inv\InvRepository as IR;
use DateTimeImmutable;

final readonly class InvSentLogService
{
    public function __construct(
        private InvSentLogRepository $repository,
        private CR $cR,
        private IR $iR,
    ) {
    }

    public function saveInvSentLog(
        InvSentLog $model,
        array $array
    ): void {
        $this->persist($model, $array);
        isset($array['inv_id']) ? 
            $model->setInv_id((int) $array['inv_id']) : '';

        $datetime_created = new DateTimeImmutable('now');
        /**
         * @var string $array['date_sent']
         */
        $date_sent = $array['date_sent'] ?? '';
        $model->setDate_sent(
            $datetime_created::createFromFormat(
                'Y-m-d', 
                $date_sent) ?: 
            new DateTimeImmutable('1901/01/01'));
        $this->repository->save($model);
    }

    private function persist(
        InvSentLog $model,
        array $array
    ): InvSentLog {
        $client = 'client_id';
        if (isset($array[$client])) {
            $model->setClient(
                $this->cR->repoClientquery(
                    (string) $array[$client]));
        }
        $inv = 'inv_id';
        if (isset($array[$inv])) {
            $invEntity = $this->iR->repoInvUnLoadedquery(
                (string) $array[$inv]);
            if ($invEntity) {
                $model->setInv($invEntity);
            }
        }
        return $model;
    }

    public function deleteInvSentLog(InvSentLog $model): void
    {
        $this->repository->delete($model);
    }
}
