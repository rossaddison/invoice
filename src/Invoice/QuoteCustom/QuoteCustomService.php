<?php

declare(strict_types=1);

namespace App\Invoice\QuoteCustom;

use App\Invoice\Entity\QuoteCustom;

final class QuoteCustomService
{
    private QuoteCustomRepository $repository;

    public function __construct(QuoteCustomRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param QuoteCustom $model
     * @param array $array
     * @return void
     */
    public function saveQuoteCustom(QuoteCustom $model, array $array): void
    {
        isset($array['quote_id']) ? $model->setQuote_id((int)$array['quote_id']) : '';
        isset($array['custom_field_id']) ? $model->setCustom_field_id((int)$array['custom_field_id']) : '';
        isset($array['value']) ? $model->setValue((string)$array['value']) : '';
        $this->repository->save($model);
    }

    /**
     *
     * @param array|QuoteCustom|null $model
     * @return void
     */
    public function deleteQuoteCustom(array|QuoteCustom|null $model): void
    {
        $this->repository->delete($model);
    }
}
