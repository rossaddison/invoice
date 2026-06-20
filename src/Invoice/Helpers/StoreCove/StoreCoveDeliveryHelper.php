<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\StoreCove;

use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation as DL;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Delivery\DeliveryRepository as DelRepo;
use App\Invoice\DeliveryParty\DeliveryPartyRepository as DelPartyRepo;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Ubl\Address;
use App\Invoice\Ubl\Country;
use App\Invoice\Ubl\Party;
use App\Invoice\Helpers\Peppol\Exception\{
    PeppolDeliveryLocationCountryNameNotFoundException,
    PeppolDeliveryLocationIDNotFoundException,
};
use DateTime;
use Yiisoft\Translator\TranslatorInterface as Translator;

final readonly class StoreCoveDeliveryHelper
{
    private const string ISO3166_1_ALPHA2 = 'ISO3166-1:Alpha2';

    public function __construct(
        private DL $deliveryLocation,
        private Translator $t,
    ) {}

    /**
     * @throws PeppolDeliveryLocationCountryNameNotFoundException
     * @return Address
     */
    public function buildDeliveryLocationAddress(): Address
    {
        $street_name = $this->deliveryLocation->getAddress1();
        $additional_street_name = $this->deliveryLocation->getAddress2();
        $building_number = $this->deliveryLocation->getBuildingNumber();
        $cityName = $this->deliveryLocation->getCity();
        $postalZone = $this->deliveryLocation->getZip();
        $countrySubEntity = $this->deliveryLocation->getState();
        $country_name = $this->deliveryLocation->getCountry();
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
        throw new PeppolDeliveryLocationCountryNameNotFoundException($this->t);
    }

    /**
     * @throws PeppolDeliveryLocationIDNotFoundException
     * @return array
     */
    public function buildDeliveryLocationIDScheme(): array
    {
        $id = $this->deliveryLocation->getGlobalLocationNumber();
        if (null == $id) {
            throw new PeppolDeliveryLocationIDNotFoundException($this->t);
        }
        return [
            'ID' => $id,
            'attributes' => [
                'schemeID' =>
                        $this->deliveryLocation->getElectronicAddressScheme(),
            ],
        ];
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
        $country = new Country($cic, self::ISO3166_1_ALPHA2);
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

    /**
     * @param Inv $invoice
     * @param DelRepo $delRepo
     * @param DelPartyRepo $delpartyRepo
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
            $partyName = (null !== $delparty ? $delparty->getPartyName() :
                null);
            return null !== $partyName ? new Party($this->t, $partyName,
                null, null, null, null, null, null, null, null, null) : null;
        }
        return null;
    }

    /**
     * @param Inv $invoice
     * @param DelRepo $delRepo
     * @return DateTime|null
     */
    public function actualDeliveryDate(Inv $invoice, DelRepo $delRepo): ?DateTime
    {
        $invoice_id = $invoice->reqId();
        $dateSupplied = DateTime::createFromImmutable($invoice->getDateSupplied());
        $delivery = $delRepo->repoInvoicequery($invoice_id);
        if (null !== $delivery) {
            $actual_delivery_date = $delivery->getActualDeliveryDate();
            if (null !== $actual_delivery_date) {
                return DateTime::createFromImmutable($actual_delivery_date);
            }
        }
        return $dateSupplied;
    }
}
