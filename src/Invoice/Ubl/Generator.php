<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Service;
use Sabre\Xml\XmlSerializable;

class Generator
{
    public static string $currencyID;

    public static function invoice(
        Invoice $invoice,
        string $currencyId = 'EUR'
    ): string {
        return self::write($invoice, 'Invoice', Schema::INVOICE_NS, $currencyId);
    }

    public static function creditNote(
        CreditNote $creditNote,
        string $currencyId = 'EUR'
    ): string {
        return self::write($creditNote, 'CreditNote', Schema::CREDIT_NOTE_NS, $currencyId);
    }

    // OrderResponseAdvanced — the only Ordering document this app ever sends (see
    // App\Invoice\As4\As4OrderImportService's own docblock: this app always plays Seller for
    // Ordering). Unlike Invoice/CreditNote, OrderResponse doesn't share Invoice's shape (no
    // lines/legal monetary total/etc.), so it deliberately isn't made to extend Invoice just to
    // satisfy write()'s type hint -- write() below takes XmlSerializable instead.
    public static function orderResponse(
        OrderResponse $orderResponse,
        string $currencyId = 'EUR'
    ): string {
        return self::write($orderResponse, 'OrderResponse', Schema::ORDER_RESPONSE_NS, $currencyId);
    }

    private static function write(
        XmlSerializable $document,
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
