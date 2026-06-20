<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\StoreCove;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\ClientPeppol\ClientPeppolRepository as cpR;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\PostalAddress\PostalAddressRepository as paR;
use App\Invoice\Ubl\Address;
use App\Invoice\Ubl\Contact;
use App\Invoice\Ubl\Country;
use App\Invoice\Ubl\PartyLegalEntity;
use App\Invoice\Ubl\PartyTaxScheme;
use App\Invoice\Ubl\TaxScheme;
use App\Invoice\Helpers\Peppol\Exception\{
    PeppolBuyerPostalAddressNotFoundException,
    PeppolClientNotFoundException,
};
use Yiisoft\Translator\TranslatorInterface as Translator;

final readonly class StoreCoveCustomerPartyParser
{
    private const string ISO3166_1_ALPHA2 = 'ISO3166-1:Alpha2';
    private const string KEY_PARTY = 'Party';
    private const string KEY_COMPANY_ID = 'CompanyID';
    private const string KEY_STREET_NAME = 'StreetName';
    private const string KEY_ADDITIONAL_STREET_NAME = 'AdditionalStreetName';
    private const string KEY_ADDRESS_LINE = 'AddressLine';
    private const string KEY_CITY_NAME = 'CityName';
    private const string KEY_POSTAL_ZONE = 'PostalZone';
    private const string KEY_COUNTRY_SUBENTITY = 'CountrySubentity';
    private const string KEY_IDENTIFICATION_CODE = 'IdentificationCode';
    private const string KEY_LIST_ID = 'ListId';

    public function __construct(private Translator $t) {}

    /**
     * @throws PeppolBuyerPostalAddressNotFoundException
     * @throws PeppolClientNotFoundException
     * @return array
     */
    public function buildPeppolAccountingCustomerPartyArray(
                                        Inv $invoice, paR $paR, cpR $cpR): array
    {
        $client = $invoice->getClient();
        if ($client) {
            $postaladdress_id = $client->getPostaladdressId();
            $client_peppol = $cpR->repoClientPeppolLoadedquery($client->reqId());
            if (null == $postaladdress_id) {
                throw new PeppolBuyerPostalAddressNotFoundException();
            }
            if ($postaladdress_id) {
                $postaladdress = $paR->repoClient($postaladdress_id);
                $accounting_customer_party = [];
                $country_helper = new CountryHelper();
                if ($postaladdress && $client_peppol) {
                    $accounting_customer_party = [
                        self::KEY_PARTY => [
                            'EndPointID' => [
                                'value' => $client_peppol->getEndpointid(),
                                'schemeID' =>
                                        $client_peppol->getEndpointidSchemeid(),
                            ],
                            'PartyIdentification' => [
                                'ID' => [
                                    'value' =>
                                           $client_peppol->getIdentificationid(),
                                    'schemeID' =>
                                  $client_peppol->getIdentificationidSchemeid(),
                                ],
                            ],
                            'PostalAddress' => [
                                self::KEY_STREET_NAME => $postaladdress->getStreetName(),
                                self::KEY_ADDITIONAL_STREET_NAME =>
                                     $postaladdress->getAdditionalStreetName(),
                                self::KEY_ADDRESS_LINE => [
                                    'Line' =>
                                            $postaladdress->getBuildingNumber(),
                                ],
                                self::KEY_CITY_NAME => $postaladdress->getCityName(),
                                self::KEY_POSTAL_ZONE => $postaladdress->getPostalZone(),
                                self::KEY_COUNTRY_SUBENTITY =>
                                           $postaladdress->getCountrysubentity(),
                                'Country' => [
                                    self::KEY_IDENTIFICATION_CODE =>
                                                    $postaladdress->getCountry(),
                                    self::KEY_LIST_ID => self::ISO3166_1_ALPHA2,
                                ],
                            ],
                            'PhysicalLocation' => [
                                self::KEY_STREET_NAME => (string)
                                                 $client->getClientAddress1(),
                                self::KEY_ADDITIONAL_STREET_NAME =>
                                        (string) $client->getClientAddress2(),
                                self::KEY_ADDRESS_LINE => [
                                    'Line' => (string)
                                            $client->getClientBuildingNumber(),
                                ],
                                self::KEY_CITY_NAME => (string)
                                                       $client->getClientCity(),
                                self::KEY_POSTAL_ZONE => (string)
                                                        $client->getClientZip(),
                                self::KEY_COUNTRY_SUBENTITY => (string)
                                                      $client->getClientState(),
                                'Country' => [
                                    self::KEY_IDENTIFICATION_CODE =>
    $country_helper->getCountryIdentificationCodeWithLeague((string)
                                                   $client->getClientCountry()),
                                    self::KEY_LIST_ID => self::ISO3166_1_ALPHA2,
                                ],
                            ],
                            'Contact' => [
                                'Name' => $client->getClientName(),
                                'Telephone' =>
                                            (string) $client->getClientPhone(),
                                'ElectronicMail' => $client->getClientEmail(),
                            ],
                            'PartyTaxScheme' => [
                                self::KEY_COMPANY_ID =>
                                        $client_peppol->getTaxschemecompanyid(),
                                'TaxScheme' => [
                                    'ID' => $client_peppol->getTaxSchemeid(),
                                ],
                            ],
                            'PartyLegalEntity' => [
                                'RegistrationName' =>
                             $client_peppol->getLegalEntityRegistrationName(),
                                'CompanyIdAttributes' => [
                                    'value' =>
                                     $client_peppol->getLegalEntityCompanyid(),
                                    'schemeID' =>
                            $client_peppol->getLegalEntityCompanyidSchemeid(),
                                ],
                                'CompanyLegalform' =>
                            $client_peppol->getLegalEntityCompanyLegalForm(),
                            ],
                        ],
                    ];
                }
                return $accounting_customer_party;
            }
            return [];
        }
        throw new PeppolClientNotFoundException($this->t);
    }

    /**
     * @param array $party
     * @return PartyLegalEntity
     */
    public function buildCustomerLegalEntity(array $party): PartyLegalEntity
    {
        /**
         * @var array $party['Party']
         * @var array $party['Party']['PartyLegalEntity']
         */
        $party_legal_entity = $party[self::KEY_PARTY]['PartyLegalEntity'] ?? [];
        /**
         * @var string $party_legal_entity['RegistrationName']
         */
        $registration_name = $party_legal_entity['RegistrationName'] ?? '';
        /**
         * @var string $party_legal_entity['CompanyID']
         */
        $company_id = $party_legal_entity[self::KEY_COMPANY_ID] ?? '';
        /**
         * @var array $party_legal_entity['Attributes']
         */
        $attributes = $party_legal_entity['Attributes'] ?? [];
        /**
         * @var string $party_legal_entity['CompanyLegalForm']
         */
        $company_legal_form = $party_legal_entity['CompanyLegalForm'] ?? '';
        return new PartyLegalEntity(
            $registration_name,
            $company_id,
            $attributes,
            $company_legal_form,
        );
    }

    /**
     * @param array $party
     * @return PartyTaxScheme
     */
    public function buildCustomerPartyTaxScheme(array $party): PartyTaxScheme
    {
        /**
         * @var array $party['Party']
         * @var array $party['Party']['PartyTaxScheme']
         */
        $party_tax_scheme = $party[self::KEY_PARTY]['PartyTaxScheme'] ?? [];
        /**
         * @var array $party_tax_scheme['TaxScheme']
         */
        $party_tax_scheme_scheme = $party_tax_scheme['TaxScheme'] ?? [];
        /**
         * @var string $party_tax_scheme_scheme['ID']
         */
        $party_tax_scheme_ID = $party_tax_scheme_scheme['ID'] ?? '';
        /**
         * @var string $party_tax_scheme['CompanyID']
         */
        $party_tax_scheme_companyID = $party_tax_scheme[self::KEY_COMPANY_ID];

        return new PartyTaxScheme(
            $party_tax_scheme_companyID,
            new TaxScheme($party_tax_scheme_ID),
        );
    }

    /**
     * @param array $party
     * @return Address
     */
    public function buildCustomerPhysicalLocation(array $party): Address
    {
        /**
         * @var array $party['Party']
         * @var array $party['Party']['PhysicalLocation']
         */
        $party_physical_location = $party[self::KEY_PARTY]['PhysicalLocation'] ?? [];
        /**
         * @var array $party_physical_location['Country']
         */
        $party_physical_location_country =
            $party_physical_location['Country'] ?? [];
        /**
         * @var string $party_physical_location['StreetName']
         */
        $street_name = $party_physical_location[self::KEY_STREET_NAME] ?? '';
        /**
         * @var string $party_physical_location['AdditionalStreetName']
         */
        $additional_street_name =
            $party_physical_location[self::KEY_ADDITIONAL_STREET_NAME] ?? '';
        /**
         * @var array $party_physical_location['AddressLine']
         */
        $address_line = $party_physical_location[self::KEY_ADDRESS_LINE] ?? [];
        /**
         * @var string $address_line['Line']
         */
        $line = $address_line['Line'] ?? '';
        /**
         * @var string $party_physical_location['CityName']
         */
        $city_name = $party_physical_location[self::KEY_CITY_NAME] ?? '';
        /**
         * @var string $party_physical_location['PostalZone']
         */
        $postal_zone = $party_physical_location[self::KEY_POSTAL_ZONE] ?? '';
        /**
         * @var string $party_physical_location['CountrySubentity']
         */
        $country_sub_entity =
            $party_physical_location[self::KEY_COUNTRY_SUBENTITY] ?? '';
        /**
         * @var string $party_physical_location_country['IdentificationCode']
         */
        $identification_code =
            $party_physical_location_country[self::KEY_IDENTIFICATION_CODE] ?? '';
        /**
         * @var string $party_physical_location_country['ListId']
         */
        $listId = $party_physical_location_country[self::KEY_LIST_ID] ?? '';
        return new Address(
            $street_name,
            $additional_street_name,
            $line,
            $city_name,
            $postal_zone,
            $country_sub_entity,
            new Country(
                $identification_code,
                $listId,
            ),
        );
    }

    /**
     * @param array $party
     * @return Address
     */
    public function buildCustomerPostalAddress(array $party): Address
    {
        /**
         * @var array $party['Party']
         * @var array $party['Party']['PostalAddress']
         */
        $postal_address = $party[self::KEY_PARTY]['PostalAddress'] ?? [];
        /**
         * @var string $postal_address['StreetName']
         */
        $street_name = $postal_address[self::KEY_STREET_NAME] ?? '';
        /**
         * @var string $postal_address['AdditionalStreetName']
         */
        $additional_street_name = $postal_address[self::KEY_ADDITIONAL_STREET_NAME] ?? '';
        /**
         * @var array $postal_address['AddressLine']
         */
        $address_line = $postal_address[self::KEY_ADDRESS_LINE] ?? [];
        /**
         * @var string $address_line['Line']
         */
        $line = $address_line['Line'] ?? '';
        /**
         * @var string $postal_address['CityName']
         */
        $city_name = $postal_address[self::KEY_CITY_NAME] ?? '';
        /**
         * @var string $postal_address['PostalZone']
         */
        $postal_zone = $postal_address[self::KEY_POSTAL_ZONE] ?? '';
        /**
         * @var string $postal_address['CountrySubentity']
         */
        $country_sub_entity = $postal_address[self::KEY_COUNTRY_SUBENTITY] ?? '';
        /**
         * @var array $postal_address['Country']
         */
        $country = $postal_address['Country'] ?? [];
        /**
         * @var string $country['IdentificationCode']
         */
        $identification_code = $country[self::KEY_IDENTIFICATION_CODE] ?? '';
        /**
         * @var string $country['ListId']
         */
        $listId = $country[self::KEY_LIST_ID] ?? '';
        return new Address(
            $street_name,
            $additional_street_name,
            $line,
            $city_name,
            $postal_zone,
            $country_sub_entity,
            new Country(
                $identification_code,
                $listId,
            ),
        );
    }

    /**
     * Introduce Storecove's firstname and lastname field
     * @param array $party
     * @return Contact
     */
    public function buildCustomerContact(array $party): Contact
    {
        /** @var array<string, mixed> $party_data */
        $party_data = $party[self::KEY_PARTY] ?? [];
        /** @var array $contact */
        $contact = $party_data['Contact'] ?? [];

        /**
         * @var string $contact['Name']
         */
        $name = $contact['Name'] ?? '';
        /**
         * @var string $contact['FirstName']
         */
        $firstName = $contact['FirstName'] ?? '';
        /**
         * @var string $contact['LastName']
         */
        $lastName = $contact['LastName'] ?? '';
        /**
         * @var string $contact['Telephone']
         */
        $telephone = $contact['Telephone'] ?? '';
        /**
         * @var string $contact['ElectronicMail']
         */
        $electronicMail = $contact['ElectronicMail'] ?? '';
        return new Contact(
            $name,
            $firstName,
            $lastName,
            $telephone,
            // Telefax
            '',
            $electronicMail,
        );
    }
}
