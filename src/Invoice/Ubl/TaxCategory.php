<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class TaxCategory implements XmlSerializable
{
    private ?string $id         = '';
    private array $idAttributes = [
        'schemeID'   => self::UNCL5305,
        'schemeName' => 'Duty or tax or fee category',
    ];
    private string $name                   = '';
    private float $percent                 = 0.00;
    private string $taxExemptionReason     = '';
    private string $taxExemptionReasonCode = '';
    public const string UNCL5305           = 'UNCL5305';

    public function __construct(array $array, private readonly TaxScheme $taxScheme)
    {
        /*
         * @var string $array['TaxCategory']
         */
        $this->id = $array['TaxCategory'];
        /*
         * @var float $array['TaxCategoryPercent']
         */
        $this->percent = $array['TaxCategoryPercent'];
    }

    public function getId(): ?string
    {
        if (null !== $this->id) {
            return $this->id;
        }
        if ($this->percent >= 21) {
            return 'S';
        }
        if ($this->percent <= 21 && $this->percent >= 6) {
            return 'AA';
        }

        return 'Z';

        return null;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function validate(): void
    {
        if (null === $this->getId()) {
            throw new \InvalidArgumentException('Missing taxcategory id');
        }

        if (empty($this->percent)) {
            throw new \InvalidArgumentException('Missing taxcategory percent');
        }
    }

    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $this->validate();

        $writer->write([
            [
                'name'       => Schema::CBC.'ID',
                'value'      => $this->getId(),
                'attributes' => $this->idAttributes,
            ],
        ]);
        $writer->write([Schema::CBC.'Name' => $this->name]);
        $writer->write([Schema::CBC.'Percent' => number_format($this->percent, 2, '.', '')]);
        $writer->write([Schema::CBC.'TaxExemptionReasonCode' => $this->taxExemptionReasonCode]);
        $writer->write([Schema::CBC.'TaxExemptionReason' => $this->taxExemptionReason]);
        $writer->write([Schema::CAC.'TaxScheme' => $this->taxScheme]);
    }
}
