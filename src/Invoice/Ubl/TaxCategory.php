<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;
use InvalidArgumentException;

class TaxCategory implements XmlSerializable
{
    private ?string $id = '';
    private array $idAttributes = [
        'schemeID' => self::UNCL5305,
        'schemeName' => 'Duty or tax or fee category',
    ];
    private string $name = '';
    private float $percent = 0.00;
    private string $taxExemptionReason = '';
    private string $taxExemptionReasonCode = '';
    public const string UNCL5305 = 'UNCL5305';

    public function __construct(
        array $array,
        private readonly TaxScheme $taxScheme
    )
    {
        /**
         * @var string $array['TaxCategory']
         */
        $this->id = $array['TaxCategory'];
        /**
         * @var float $array['TaxCategoryPercent']
         */
        $this->percent = $array['TaxCategoryPercent'];
    }

    /**
     * @return string|null
     */
    public function reqId(): ?string
    {
        if (null !== $this->id) {
            return $this->id;
        }
        return match (true) {
            $this->percent >= 21 => 'S',
            $this->percent >= 6  => 'AA',
            $this->percent == 0  => 'Z',
            default              => null,
        };
    }

    /**
     * @throws InvalidArgumentException
     */
    public function validate(): void
    {
        if ($this->reqId() === null) {
            throw new InvalidArgumentException('Missing taxcategory id');
        }

        if (empty($this->percent)) {
            throw new InvalidArgumentException('Missing taxcategory percent');
        }
    }

    /**
     * @param Writer $writer
     */
    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $this->validate();

        $writer->write([
            [
                'name' => Schema::CBC . 'ID',
                'value' => $this->reqId(),
                'attributes' => $this->idAttributes,
            ],
        ]);
        $writer->write([Schema::CBC
                . 'Name' => $this->name]);
        $writer->write([Schema::CBC
                . 'Percent' => number_format($this->percent, 2, '.', ''),]);
        $writer->write([Schema::CBC
                . 'TaxExemptionReasonCode' => $this->taxExemptionReasonCode]);
        $writer->write([Schema::CBC
                . 'TaxExemptionReason' => $this->taxExemptionReason]);
        $writer->write([Schema::CAC
                . 'TaxScheme' => $this->taxScheme]);
    }
}
