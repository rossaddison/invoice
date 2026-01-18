<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;
use App\Invoice\Setting\SettingRepository;

/** Related logic: 
 * https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
 *                                                      cac-LegalMonetaryTotal/
 */
class LegalMonetaryTotal implements XmlSerializable
{
    public function __construct(
        private readonly float $lineExtensionAmount,
        private readonly float $taxExclusiveAmount,
        private readonly float $taxInclusiveAmount,
        private readonly float $allowanceTotalAmount,
        private readonly float $payableAmount,
        private readonly string $document_currency,
        public SettingRepository $s,
    )
    {
    }

    /**
     * @param Writer $writer
     */
    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            /**
             * Related logic:
             * https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
             *                          cac-InvoiceLine/cbc-LineExtensionAmount/
             */
            [
                'name' => Schema::CBC . 'LineExtensionAmount',
                'value' => $this->s->currency_converter(number_format($this->lineExtensionAmount
                        ?: 0.00, 2, '.', '')),
                'attributes' => [
                    'currencyID' => $this->document_currency,
                ],
            ],
            [
                'name' => Schema::CBC . 'TaxExclusiveAmount',
                'value' => $this->s->currency_converter(number_format($this->taxExclusiveAmount
                        ?: 0.00, 2, '.', '')),
                'attributes' => [
                    'currencyID' => $this->document_currency,
                ],
            ],
            [
                'name' => Schema::CBC . 'TaxInclusiveAmount',
                'value' => $this->s->currency_converter(number_format($this->taxInclusiveAmount
                        ?: 0.00, 2, '.', '')),
                'attributes' => [
                    'currencyID' => $this->document_currency,
                ],
            ],
            [
                'name' => Schema::CBC . 'AllowanceTotalAmount',
                'value' => $this->s->currency_converter(number_format($this->allowanceTotalAmount
                        ?: 0.00, 2, '.', '')),
                'attributes' => [
                    'currencyID' => $this->document_currency,
                ],
            ],
            [
                'name' => Schema::CBC . 'PayableAmount',
                'value' => $this->s->currency_converter(number_format($this->payableAmount
                        ?: 0.00, 2, '.', '')),
                'attributes' => [
                    'currencyID' => $this->document_currency,
                ],
            ],
        ]);
    }
}
