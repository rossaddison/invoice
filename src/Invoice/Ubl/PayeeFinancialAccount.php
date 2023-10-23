<?php
declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class PayeeFinancialAccount implements XmlSerializable
{
    private ?string $id;
    private ?string $name;
    private ?FinancialInstitutionBranch $financialInstitutionBranch;

    public function __construct(
            ?FinancialInstitutionBranch $financialInstitutionBranch,            
            ?string $id,
            ?string $name
    ) {
            $this->financialInstitutionBranch = $financialInstitutionBranch;            
            $this->id = $id;
            $this->name = $name;
    }
    
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            'name' => Schema::CBC . 'ID',
            'value' => $this->id,
            'attributes' => [
                //'schemeID' => 'IBAN'
            ]
        ]);

        if ($this->name !== null) {
            $writer->write([
                Schema::CBC . 'Name' => $this->name
            ]);
        }

        if ($this->financialInstitutionBranch !== null) {
            $writer->write([
                Schema::CAC . 'FinancialInstitutionBranch' => $this->financialInstitutionBranch
            ]);
        }
    }
}
