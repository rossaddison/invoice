<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

use InvalidArgumentException;

class ClassifiedTaxCategory implements XmlSerializable
{
    private ?string $id;
    private ?string $name;
    private ?float $percent;
    private ?TaxScheme $taxScheme;
    private string $taxExemptionReason;
    private ?string $taxExemptionReasonCode;
    private ?string $schemeID;
    private ?string $schemeName;
    
    public function __construct(?string $id, ?string $name, ?float $percent, ?TaxScheme $taxScheme, string $taxExemptionReason, ?string $taxExemptionReasonCode, ?string $schemeID, ?string $schemeName) {
        $this->id = $id;
        $this->name = $name;
        $this->percent = $percent;
        $this->taxScheme = $taxScheme;
        $this->taxExemptionReason = $taxExemptionReason;
        $this->taxExemptionReasonCode = $taxExemptionReasonCode;
        $this->schemeID = $schemeID;
        $this->schemeName = $schemeName;
    }

    public const UNCL5305 = 'UNCL5305';

    /**
     * 
     * @return string|null
     */
    public function getId(): ?string
    {
        if (!empty($this->id)) {
            return $this->id;
        }

        if ($this->percent !== null) {
            if ($this->percent >= 21) {
                return 'S';
            } elseif ($this->percent <= 21 && $this->percent >= 6) {
                return 'AA';
            } else {
                return 'Z';
            }
        }
        return null;
    }

    /**
     * 
     * @throws InvalidArgumentException
     */
    public function validate() : void
    {
        if ($this->id === null) {
            throw new InvalidArgumentException('Missing taxcategory id');
        }

        if ($this->percent === null) {
            throw new InvalidArgumentException('Missing taxcategory percent');
        }
    }

    /**
     * 
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void
    {
        $this->validate();

        $schemeAttributes = [];
        if ($this->schemeID !== null) {
            $schemeAttributes['schemeID'] = $this->schemeID;
        }
        if ($this->schemeName !== null) {
            $schemeAttributes['schemeName'] = $this->schemeName;
        }

        $writer->write([
            'name' => Schema::CBC . 'ID',
            'value' => $this->id,
            'attributes' => $schemeAttributes
        ]);

        if ($this->name !== null) {
            $writer->write([
                Schema::CBC . 'Name' => $this->name,
            ]);
        }
        
        // Exempt Tax category => 0% => 0 tax charged.
        $writer->write([
            Schema::CBC . 'Percent' => number_format($this->percent ?: 0.00 , 2, '.', ''),
        ]);

        if ($this->taxExemptionReasonCode !== null) {
            $writer->write([
                Schema::CBC . 'TaxExemptionReasonCode' => $this->taxExemptionReasonCode,
                Schema::CBC . 'TaxExemptionReason' => $this->taxExemptionReason,
            ]);
        }

        if ($this->taxScheme !== null) {
            $writer->write([Schema::CAC . 'TaxScheme' => $this->taxScheme]);
        } else {
            $writer->write([
                Schema::CAC . 'TaxScheme' => null,
            ]);
        }
    }
}
