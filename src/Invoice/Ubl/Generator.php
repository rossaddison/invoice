<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Service;

class Generator
{
    public static string $currencyID;

    public static function invoice(
        Invoice $invoice,
        string $currencyId = 'EUR'
    ): string
    {
        return self::write($invoice, 'Invoice', Schema::INVOICE_NS, $currencyId);
    }

    public static function creditNote(
        CreditNote $creditNote,
        string $currencyId = 'EUR'
    ): string
    {
        return self::write($creditNote, 'CreditNote', Schema::CREDIT_NOTE_NS, $currencyId);
    }

    private static function write(
        Invoice $document,
        string $rootElement,
        string $rootNamespace,
        string $currencyId
    ): string {
        self::$currencyID = $currencyId;

        $xmlService = new Service();

        $xmlService->namespaceMap = [
            $rootNamespace => '',
            Schema::CBC_NS => 'cbc',
            Schema::CAC_NS => 'cac',
        ];
        return $xmlService->write($rootElement, $document);
    }
}
