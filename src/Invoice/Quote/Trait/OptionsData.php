<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Infrastructure\Persistence\Quote\Quote;
use App\Invoice\Client\ClientOptionLabels;
use App\Invoice\Quote\QuoteRepository as QR;

trait OptionsData
{
    /**
     * Keyed by client id, so two clients with the same name are two entries and the quote filter can tell them apart
     * (QuoteCombinedFilterTrait::applyClientCondition() matches on the id). It used to be keyed by the full name.
     *
     * @return array<int, string>
     */
    public function optionsDataClients(QR $qR): array
    {
        // Get all the quotes that have been made out to clients
        $quotes = $qR->findAllPreloaded();
        $clients = [];
        /**
         * @var \App\Infrastructure\Persistence\Quote\Quote $quote
         */
        foreach ($quotes as $quote) {
            $clients[] = $quote->getClient();
        }
        return ClientOptionLabels::forFilter($clients);
    }

    public function optionsDataClientGroup(QR $qR): array
    {
        $clientGroup = [];
        $quotes = $qR->findAllPreloaded();
        /**
         * @var \App\Infrastructure\Persistence\Quote\Quote $quote
         */
        foreach ($quotes as $quote) {
            $client = $quote->getClient();
            if (null !== $client) {
                $group = $client->getClientGroup();
                if (null !== $group && !in_array($group, $clientGroup)) {
                    $clientGroup[$group] = $group;
                }
            }
        }
        return $clientGroup;
    }

    public function optionsDataQuoteNumber(QR $qR): array
    {
        $optionsDataQuoteNumbers = [];
        $quotes = $qR->findAllPreloaded();
        /**
         * @var \App\Infrastructure\Persistence\Quote\Quote $quote
         */
        foreach ($quotes as $quote) {
            $quoteNumber = $quote->getNumber();
            if (null !== $quoteNumber && !in_array($quoteNumber, $optionsDataQuoteNumbers)) {
                $optionsDataQuoteNumbers[$quoteNumber] = $quoteNumber;
            }
        }
        return $optionsDataQuoteNumbers;
    }

    /** @return array<int, string> */
    public function optionsDataStatuses(QR $qR): array
    {
        $optionsDataStatus = [];
        $statuses = $qR->getStatuses($this->translator);

        /**
         * @var array<int, array<string, string>> $statuses
         */
        foreach (array_keys($statuses) as $statusId) {
            $label = $qR->getSpecificStatusArrayLabel((string) $statusId);
            $optionsDataStatus[$statusId] = $label;
        }

        return $optionsDataStatus;
    }
}
