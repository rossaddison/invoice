<?php

declare(strict_types=1);

namespace App\Invoice\QuoteCustom;

use App\Invoice\Entity\QuoteCustom;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\Quote\QuoteRepository as QR;

final readonly class QuoteCustomService
{
    public function __construct(
        private QuoteCustomRepository $repository,
        private CFR $cfR,
        private QR $qR,
    ) {
    }

    /**
     * @param array $array
     * @param QuoteCustom $model
     * @return void
     */
    private function persist(array $array, QuoteCustom $model): void
    {
        if (isset($array['quote_id'])) {
            $quote = $this->qR->repoQuoteUnLoadedquery(
                (string) $array['quote_id']
            );
            if ($quote) {
                $model->setQuote($quote);
            }
        }
        if (isset($array['custom_field_id'])) {
            $custom_field = $this->cfR->repoCustomFieldquery(
                (string) $array['custom_field_id']
            );
            if ($custom_field) {
                $model->setCustomField($custom_field);
            }
        }
    }

    /**
     * @param QuoteCustom $model
     * @param array $array
     */
    public function saveQuoteCustom(
        QuoteCustom $model,
        array $array
    ): void {
        $this->persist($array, $model);

        isset($array['quote_id']) ?
            $model->setQuote_id((int) $array['quote_id']) : '';
        isset($array['custom_field_id']) ?
            $model->setCustom_field_id(
                (int) $array['custom_field_id']
            ) : '';
        isset($array['value']) ?
            $model->setValue((string) $array['value']) : '';
        $this->repository->save($model);
    }

    /**
     * @param array|QuoteCustom|null $model
     */
    public function deleteQuoteCustom(
        array|QuoteCustom|null $model
    ): void {
        $this->repository->delete($model);
    }
}
