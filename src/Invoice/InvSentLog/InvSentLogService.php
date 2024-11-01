<?php

declare(strict_types=1);

namespace App\Invoice\InvSentLog;

use App\Invoice\Entity\InvSentLog;
use App\Invoice\InvSentLog\InvSentLogRepository;
use DateTimeImmutable;

final class InvSentLogService
{
    private InvSentLogRepository $repository;

    public function __construct(InvSentLogRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveInvSentLog(InvSentLog $model, array $array): void
    {
        isset($array['inv_id']) ? $model->setInv_id((int)$array['inv_id']) : '';

        $datetime_created = new DateTimeImmutable('now');
        /**
         * @var string $array['date_sent']
         */
        $date_sent = $array['date_sent'] ?? '';
        $model->setDate_sent($datetime_created::createFromFormat('Y-m-d', $date_sent) ?: new \DateTimeImmutable('1901/01/01'));
        $this->repository->save($model);
    }

    public function deleteInvSentLog(InvSentLog $model): void
    {
        $this->repository->delete($model);
    }
}
