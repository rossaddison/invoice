<?php

declare(strict_types=1);

namespace App\Invoice\AllowanceCharge;

use App\Invoice\Entity\AllowanceCharge as AC;
use App\Invoice\TaxRate\TaxRateRepository as trR;

final readonly class AllowanceChargeService
{
    public function __construct(
        private AllowanceChargeRepository $repository,
        private trR $trR,
    ) {
    }

    public function saveAllowanceCharge(
        AC $model,
        array $array
    ): void {
        $model = $this->persist($model, $array);
        isset($array['identifier']) ?
            $model->setIdentifier((bool) $array['identifier']) : '';
        isset($array['reason_code']) ?
            $model->setReasonCode((string) $array['reason_code']) : '';
        isset($array['reason']) ?
            $model->setReason((string) $array['reason']) : '';
        isset($array['multiplier_factor_numeric']) ?
            $model->setMultiplierFactorNumeric(
                    (int) $array['multiplier_factor_numeric']) : '';
        isset($array['amount']) ?
            $model->setAmount((int) $array['amount']) : '';
        isset($array['base_amount']) ?
            $model->setBaseAmount((int) $array['base_amount']) : '';
        isset($array['tax_rate_id']) ?
            $model->setTaxRateId((int) $array['tax_rate_id']) : '';
        $this->repository->save($model);
    }
    
    private function persist(AC $model, array $array): AC
    {
        $tr = 'tax_rate_id';
        if (isset($array[$tr])) {
            $model->setTaxRate(
                $this->trR->repoTaxRatequery(
                    (string) $array[$tr]));
        }
        return $model;
    }

    public function deleteAllowanceCharge(AC $model): void
    {
        $this->repository->delete($model);
    }
}
