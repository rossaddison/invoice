<?php

declare(strict_types=1);

namespace App\Invoice\Contract;

use App\Invoice\Entity\Contract;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Setting\SettingRepository as SR;

final readonly class ContractService
{
    public function __construct(
        private ContractRepository $repository,
        private CR $cR,
    ) {
    }

    /**
     * @param Contract $model
     * @param array $array
     * @param SR $s
     */
    public function saveContract(
        Contract $model,
        array $array,
        SR $s
    ): void {
        $this->persist($model, $array);
        $datehelper = new DateHelper($s);

        $datetime_immutable_period_start = new \DateTimeImmutable();
        $period_start =
            $datetime_immutable_period_start::createFromFormat(
                'Y-m-d',
                (string) $array['period_start'],
            );

        $period_start ? $model->setPeriod_start($period_start) : '';

        $datetime_immutable_period_end = new \DateTimeImmutable();
        $period_end =
            $datetime_immutable_period_end::createFromFormat(
                'Y-m-d',
                (string) $array['period_end'],
            );

        $period_end ? $model->setPeriod_end($period_end) : '';

        isset($array['client_id']) ?
            $model->setClient_id((int) $array['client_id']) : '';
        isset($array['name']) ?
            $model->setName((string) $array['name']) : '';
        isset($array['reference']) ?
            $model->setReference((string) $array['reference']) : '';
        $this->repository->save($model);
    }

    private function persist(
        Contract $model,
        array $array
    ): Contract {
        $client = 'client_id';
        if (isset($array[$client])) {
            $model->setClient(
                $this->cR->repoClientquery(
                    (string) $array[$client]));
        }
        return $model;
    }

    public function deleteContract(Contract $model): void
    {
        $this->repository->delete($model);
    }
}
