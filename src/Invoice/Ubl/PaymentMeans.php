<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class PaymentMeans implements XmlSerializable
{
    private ?int $paymentMeansCode            = 30;
    private array $paymentMeansCodeAttributes = [
        'name' => 'Credit Transfer',
    ];

    public function __construct(private readonly ?PayeeFinancialAccount $payeeFinancialAccount, private readonly ?string $paymentId = '')
    {
    }

    /**
     * @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?p=3&q=PaymentMeans
     */
    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            'name'       => Schema::CBC.'PaymentMeansCode',
            'value'      => $this->paymentMeansCode,
            'attributes' => $this->paymentMeansCodeAttributes,
        ]);

        if (null !== $this->paymentId) {
            $writer->write([
                Schema::CBC.'PaymentID' => $this->paymentId,
            ]);
        }

        if (null !== $this->payeeFinancialAccount) {
            $writer->write([
                Schema::CAC.'PayeeFinancialAccount' => $this->payeeFinancialAccount,
            ]);
        }
    }
}
