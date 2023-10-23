<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use App\Invoice\Ubl\Invoice;
use App\Invoice\Ubl\CreditNote;
use Sabre\Xml\Service;

class Generator {

  public static string $currencyID;

  /**
   * @psalm-suppress MissingReturnType
   */
  public static function invoice(Invoice $invoice, string $currencyId = 'EUR'): string {
    self::$currencyID = $currencyId;

    $xmlService = new Service();

    $xmlService->namespaceMap = [
      'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2' => '',
      'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2' => 'cbc',
      'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2' => 'cac'
    ];
    return $xmlService->write('Invoice', $invoice);
  }

  /** @psalm-suppress MissingReturnType */
  public static function creditNote(CreditNote $creditNote, string $currencyId = 'EUR') {
    return self::invoice($creditNote, $currencyId);
  }

}
