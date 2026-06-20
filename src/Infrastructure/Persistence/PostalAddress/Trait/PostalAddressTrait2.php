<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PostalAddress\Trait;

use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait PostalAddressTrait2
{

    public function setCountrysubentity(string $countrysubentity): void
    {
        $this->countrysubentity = $countrysubentity;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    public function getFullAddress(): string
    {
        return $this->street_name
                . ' '
                . $this->building_number
                . ', '
                . $this->additional_street_name
                . ', ' . $this->postalzone;
    }
}
