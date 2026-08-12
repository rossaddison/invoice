<?php

declare(strict_types=1);

namespace App\Invoice\Dwelling;

use App\Infrastructure\Persistence\Dwelling\Dwelling;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class DwellingForm extends FormModel
{
    #[Required]
    private ?int $family_id = null;

    #[Required]
    private ?int $house_number_numeric = null;

    private ?string $house_number_suffix = '';

    private ?string $flat_unit = '';

    #[Required]
    private ?string $postcode = '';

    private ?float $latitude = null;

    private ?float $longitude = null;

    private ?string $source = '';

    public static function show(Dwelling $dwelling): self
    {
        $form = new self();
        $form->family_id = $dwelling->getFamilyId();
        $form->house_number_numeric = $dwelling->getHouseNumberNumeric();
        $form->house_number_suffix = $dwelling->getHouseNumberSuffix();
        $form->flat_unit = $dwelling->getFlatUnit();
        $form->postcode = $dwelling->getPostcode();
        $form->latitude = $dwelling->getLatitude();
        $form->longitude = $dwelling->getLongitude();
        $form->source = $dwelling->getSource();
        return $form;
    }

    public function getFamilyId(): ?int
    {
        return $this->family_id;
    }

    public function getHouseNumberNumeric(): ?int
    {
        return $this->house_number_numeric;
    }

    public function getHouseNumberSuffix(): ?string
    {
        return $this->house_number_suffix;
    }

    public function getFlatUnit(): ?string
    {
        return $this->flat_unit;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
