<?php

declare(strict_types=1);

namespace App\Invoice\QuoteAllowanceCharge;

use App\Invoice\Entity\QuoteAllowanceCharge;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository as ACR;
use App\Invoice\Quote\QuoteRepository as QR;

final readonly class QuoteAllowanceChargeService
{
    public function __construct(
        private QuoteAllowanceChargeRepository $repository,
        private ACR $acR,
        private QR $qR,
    ) {
    }

    /**
     * @param array $array
     * @param QuoteAllowanceCharge $model
     * @return void
     */
    private function persist(
        array $array,
        QuoteAllowanceCharge $model
    ): void {
        if (isset($array['allowance_charge_id'])) {
            $allowance_charge = $this->acR->repoAllowanceChargequery(
                (string) $array['allowance_charge_id']
            );
            if ($allowance_charge) {
                $model->setAllowanceCharge($allowance_charge);
            }
        }
        if (isset($array['quote_id'])) {
            $quote = $this->qR->repoQuoteUnLoadedquery(
                (string) $array['quote_id']
            );
            if ($quote) {
                $model->setQuote($quote);
            }
        }
    }

    /**
     * @param QuoteAllowanceCharge $model
     * @param array $array
     */
    public function saveQuoteAllowanceCharge(
        QuoteAllowanceCharge $model,
        array $array
    ): void {
        $this->persist($array, $model);

        isset($array['id']) ?
            $model->setId((int) $array['id']) : '';
        isset($array['quote_id']) ?
            $model->setQuote_id((int) $array['quote_id']) : '';
        isset($array['allowance_charge_id']) ?
            $model->setAllowance_charge_id(
                (int) $array['allowance_charge_id']
            ) : '';
        isset($array['amount']) ?
            $model->setAmount((float) $array['amount']) : 0.00;
        $allowance_charge =
            $this->acR->repoAllowanceChargequery(
                (string) $array['allowance_charge_id']
            );
        if (null !== $allowance_charge
                && null !== $allowance_charge->getTaxRate()) {
            $allowanceChargeTaxRate = $allowance_charge->getTaxRate();
            if (null !== $allowanceChargeTaxRate) {
                if ($array['amount'] == '') {
                    $amount = 0.00;
                } else {
                    $amount = (float) $array['amount'];
                }
                $vatOrTax = $amount *
                    ($allowanceChargeTaxRate->getTaxRatePercent()
                        ?? 0.00) / 100.00;
                $model->setVatOrTax($vatOrTax);
            }
        }
        $this->repository->save($model);
    }

    /**
     * @param QuoteAllowanceCharge $model
     */
    public function deleteQuoteAllowanceCharge(
        QuoteAllowanceCharge $model
    ): void {
        $this->repository->delete($model);
    }
}
