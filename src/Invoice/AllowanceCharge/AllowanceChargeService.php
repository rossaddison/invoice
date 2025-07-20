<?php

declare(strict_types=1);

namespace App\Invoice\AllowanceCharge;

use App\Invoice\Entity\AllowanceCharge;

final readonly class AllowanceChargeService
{
    public function __construct(private AllowanceChargeRepository $repository) {}

    public function saveAllowanceCharge(AllowanceCharge $model, array $array): void
    {
        isset($array['identifier']) ? $model->setIdentifier((bool) $array['identifier']) : '';
        isset($array['reason_code']) ? $model->setReasonCode((string) $array['reason_code']) : '';
        isset($array['reason']) ? $model->setReason((string) $array['reason']) : '';
        isset($array['multiplier_factor_numeric']) ? $model->setMultiplierFactorNumeric((int) $array['multiplier_factor_numeric']) : '';
        isset($array['amount']) ? $model->setAmount((int) $array['amount']) : '';
        isset($array['base_amount']) ? $model->setBaseAmount((int) $array['base_amount']) : '';
        isset($array['tax_rate_id']) ? $model->setTaxRateId((int) $array['tax_rate_id']) : '';
        $this->repository->save($model);
    }

    public function deleteAllowanceCharge(AllowanceCharge $model): void
    {
        $this->repository->delete($model);
    }
}
