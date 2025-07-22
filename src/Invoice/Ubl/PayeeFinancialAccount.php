<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class PayeeFinancialAccount implements XmlSerializable
{
    public function __construct(private readonly ?FinancialInstitutionBranch $financialInstitutionBranch, private readonly ?string $id, private readonly ?string $name)
    {
    }

    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            'name'       => Schema::CBC.'ID',
            'value'      => $this->id,
            'attributes' => [
                // 'schemeID' => 'IBAN'
            ],
        ]);

        if (null !== $this->name) {
            $writer->write([
                Schema::CBC.'Name' => $this->name,
            ]);
        }

        if (null !== $this->financialInstitutionBranch) {
            $writer->write([
                Schema::CAC.'FinancialInstitutionBranch' => $this->financialInstitutionBranch,
            ]);
        }
    }
}
