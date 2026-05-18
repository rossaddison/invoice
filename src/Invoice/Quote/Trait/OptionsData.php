<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Infrastructure\Persistence\Quote\Quote;
use App\Invoice\Quote\QuoteRepository as QR;

trait OptionsData
{
    /** @return array<string, string> */
    public function optionsDataClients(QR $qR): array
    {
        $optionsDataClients = [];
        // Get all the invoices that have been made out to clients with
        // user accounts
        $quotes = $qR->findAllPreloaded();
        /**
         * @var \App\Infrastructure\Persistence\Quote\Quote $quote
         */
        foreach ($quotes as $quote) {
            $client = $quote->getClient();
            if (null !== $client) {
                if (strlen($client->getClientFullName()) > 0) {
                    $fullName = $client->getClientFullName();
                    $optionsDataClients[$fullName] =
                        !empty($fullName) ? $fullName : '';
                }
            }
        }
        return $optionsDataClients;
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
            if (null !== $quoteNumber) {
                if (!in_array($quoteNumber, $optionsDataQuoteNumbers)) {
                    $optionsDataQuoteNumbers[$quoteNumber] = $quoteNumber;
                }
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