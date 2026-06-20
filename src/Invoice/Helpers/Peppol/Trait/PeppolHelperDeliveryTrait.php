<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Delivery\DeliveryRepository as DelRepo;
use App\Invoice\DeliveryParty\DeliveryPartyRepository as DelPartyRepo;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Ubl\{Address, Country, Party};
use App\Invoice\Helpers\Peppol\Exception\{
    PeppolDeliveryLocationIDNotFoundException as DelLocIdNf,
    PeppolDeliveryLocationCountryNameNotFoundException as DelLocCounNameNf,
};

trait PeppolHelperDeliveryTrait
{
    public function buildDeliveryLocationIDScheme(): array
    {
        $id = $this->delivery_location->getGlobalLocationNumber();
        if (null == $id) {
            throw new DelLocIdNf($this->t);
        }
        return [
            'ID' => $id,
            'attributes' => [
                'schemeID' =>
                    $this->delivery_location->getElectronicAddressScheme(),
            ],
        ];
    }

    /**
     * @return Address
     */
    public function buildDeliveryLocationAddress(): Address
    {
        $street_name = $this->delivery_location->getAddress1();
        $additional_street_name = $this->delivery_location->getAddress2();
        $building_number = $this->delivery_location->getBuildingNumber();
        $cityName = $this->delivery_location->getCity();
        $postalZone = $this->delivery_location->getZip();
        $countrySubEntity = $this->delivery_location->getState();
        $country_name = $this->delivery_location->getCountry();
        if (null !== $country_name) {
            return $this->ublDeliveryLocation(
                $street_name,
                $additional_street_name,
                $building_number,
                $cityName,
                $postalZone,
                $countrySubEntity,
                $country_name,
            );
        }
        throw new DelLocCounNameNf($this->t);
    }

    /**
     * @param Inv $invoice
     * @param DelRepo $delRepo
     * @return Party|null
     */
    public function deliveryParty(Inv $invoice, DelRepo $delRepo,
                                             DelPartyRepo $delpartyRepo): ?Party
    {
        $invoice_id = $invoice->reqId();
        $inv = $delRepo->repoPartyquery($invoice_id);
        if ($inv) {
            $delivery_party_id = $inv->hasDeliveryPartyId() ? $inv->reqDeliveryPartyId() : null;
            $delparty = $delpartyRepo->repoDeliveryPartyquery((int) $delivery_party_id);
            $partyName = (null !== $delparty ? $delparty->getPartyName()
                                                                    : null);
            return null !== $partyName ? new Party($this->t, $partyName,
               null, null, null, null, null, null, null, null, null) : null;
        }
        return null;
    }

    /**
     * Build \Invoice\Ubl\Country.php with CountryHelper and country_name
     * @param string|null $streetName
     * @param string|null $additionalStreetName
     * @param string|null $buildingNumber
     * @param string|null $cityName
     * @param string|null $postalZone
     * @param string|null $countrySubEntity
     * @param string $country_name
     * @return Address
     */
    public function ublDeliveryLocation(?string $streetName,
            ?string $additionalStreetName, ?string $buildingNumber,
            ?string $cityName, ?string $postalZone, ?string $countrySubEntity,
            string $country_name): Address
    {
        $country_helper = new CountryHelper();
        $cic = $country_helper->getCountryIdentificationCodeWithLeague(
                $country_name);
        $country = new Country($cic, 'ISO3166-1:Alpha2');
        return new Address(
            $streetName,
            $additionalStreetName,
            $buildingNumber,
            $cityName,
            $postalZone,
            $countrySubEntity,
            $country,
            false,
            false,
            true,
        );
    }
}
