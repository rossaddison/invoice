<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Emit\Vo;

readonly class UblPartyVO
{
    public function __construct(
        public ?string $endpointId,
        public ?string $endpointSchemeId,
        public ?string $partyIdentificationId,
        public ?string $partyIdentificationSchemeId,
        public ?string $name,
        public ?string $streetName,
        public ?string $additionalStreetName,
        public ?string $cityName,
        public ?string $postalZone,
        public ?string $countrySubentity,
        public string  $countryCode,
        public ?string $vatNumber,
        public ?string $taxSchemeId,
        public ?string $registrationName,
        public ?string $companyId,
        public ?string $companyIdSchemeId,
    ) {}
}
