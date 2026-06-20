<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use Yiisoft\Data\Cycle\Reader\EntityReader;

trait QuoteFilterTrait
{
    public function filterQuoteNumber(string $quoteNumber): EntityReader
    {
        $select = $this->select();
        $query = $select->where(['number' => ltrim(rtrim($quoteNumber))]);
        return $this->prepareDataReader($query);
    }

    public function filterQuoteAmountTotal(string $quoteAmountTotal): EntityReader
    {
        $select = $this->select();
        $query = $select
                 ->load('quoteAmount')
                 ->where(['quoteAmount.total' => $quoteAmountTotal]);
        return $this->prepareDataReader($query);
    }

    public function filterQuoteNumberAndQuoteAmountTotal(string $quoteNumber, float $quoteAmountTotal): EntityReader
    {
        $select = $this->select();
        $query = $select
                 ->load('quoteAmount')
                 ->where(['number' => $quoteNumber])
                 ->andWhere(['quoteAmount.total' => $quoteAmountTotal]);
        return $this->prepareDataReader($query);
    }

    public function filterClient(string $fullName): EntityReader
    {
        $nameParts = explode(' ', $fullName);
        $firstName = $nameParts[0];
        $secondName = $nameParts[1] ?? '';
        $query = $this->select()
                       ->load(['client'])
                       ->where(['client.client_name' => $firstName])
                       ->where(['client.client_surname' => $secondName]);
        return $this->prepareDataReader($query);
    }
}
