<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;
use InvalidArgumentException;

class TaxTotal implements XmlSerializable
{
    public function __construct(
        private readonly array $doc_and_or_supp_currency_tax
    )
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function validate(): void
    {
        if (empty($this->doc_and_or_supp_currency_tax)) {
            throw new InvalidArgumentException('Missing taxtotal taxamount');
        }
    }

    /**
     * Related logic: see PeppolHelper/TaxAmounts function
     *
     * @param Writer $writer
     */
    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $this->validate();
        /** @var array<string, mixed> $tst */
        $tst = $this->doc_and_or_supp_currency_tax;
        $supp_tax_cc_tax_amount = (float) ($tst['supp_tax_cc_tax_amount'] ?? 0.00);
        $doc_cc_tax_amount      = (float) ($tst['doc_cc_tax_amount']      ?? 0.00);
        $supp_cc                = (string) ($tst['supp_tax_cc'] ?? '');
        $doc_cc                 = (string) ($tst['doc_cc']      ?? '');

        // One Instance of Tax Total provided because
        // Document has same currency code as Supplier
        if ($doc_cc === $supp_cc) {
            $writer->write(
                [
                    'name' => Schema::CBC . 'TaxAmount',
                    'value' => number_format(
                                    $supp_tax_cc_tax_amount ?: 0.00, 2, '.', ''),
                    'attributes' => [
                        'currencyID' => $supp_cc,
                    ],
                ],
            );

            // The suppliers currency is different to the document's currency.
            // BT-110: document currency TaxAmount MUST come first (R051).
            // BT-111: accounting currency TaxAmount follows (exempt from R051).
        } else {
            // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-TaxTotal/
            $writer->write([
                'name' => Schema::CBC . 'TaxAmount',
                'value' => number_format($doc_cc_tax_amount ?: 0.00, 2, '.', ''),
                'attributes' => [
                    'currencyID' => $doc_cc,
                ],
            ]);
            $writer->write([[
                'name' => Schema::CBC . 'TaxAmount',
                'value' => number_format($supp_tax_cc_tax_amount ?: 0.00, 2, '.', ''),
                'attributes' => [
                    'currencyID' => $supp_cc,
                ],
            ]]);
        } // elseif
    } //xmlserialize
}
