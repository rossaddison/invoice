<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class PaymentMeans implements XmlSerializable
{
    private ?int $paymentMeansCode = 30;
    private array $paymentMeansCodeAttributes = [
        'name' => 'Credit Transfer'
    ];  
    private ?string $paymentId;
    private ?PayeeFinancialAccount $payeeFinancialAccount;
    
    public function __construct(
        ?PayeeFinancialAccount $payeeFinancialAccount,
        string $paymentId = '') {
        $this->payeeFinancialAccount = $payeeFinancialAccount;
        $this->paymentId = $paymentId;
    }
    
    /**
     * @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?p=3&q=PaymentMeans
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            'name' => Schema::CBC . 'PaymentMeansCode',
            'value' => $this->paymentMeansCode,
            'attributes' => $this->paymentMeansCodeAttributes
        ]);

        if ($this->paymentId !== null) {
            $writer->write([
                Schema::CBC . 'PaymentID' => $this->paymentId
            ]);
        }

        if ($this->payeeFinancialAccount !== null) {
            $writer->write([
                Schema::CAC . 'PayeeFinancialAccount' => $this->payeeFinancialAccount
            ]);
        }
    }
}
