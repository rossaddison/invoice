<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\ClientPeppol\ClientPeppolRepository as cpR;
use App\Invoice\PostalAddress\PostalAddressRepository as paR;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Ubl\{Address, Contact, Country, Party, PartyLegalEntity, PartyTaxScheme, TaxScheme};
use App\Invoice\Helpers\Peppol\Exception\{
    PeppolBuyerPostalAddressNotFoundException as BuyerPostAddNf,
    PeppolClientNotFoundException as ClientNf,
    PeppolClientsAccountingCostNotFoundException as ClientsAccCostNf,
};

trait PeppolHelperCustomerTrait
{
    private function buildCustomerParty(Inv $invoice, paR $paR, cpR $cpR): Party
    {
        $customer_name = $invoice->getClient()?->getClientFullName();
        $party =
    $this->buildPeppolAccountingCustomerPartyArray($invoice, $paR, $cpR);
        /**
         * @var array $party['Party']
         * @var array $party['Party']['PartyIdentification']
         * @var array $party['Party']['PartyIdentification']['ID']
         * @var string $party['Party']['PartyIdentification']['ID']['value']
         */
        $customer_partyIdentificationId =
            $party['Party']['PartyIdentification']['ID']['value'] ?? null;
        /**
         * @var string $party['Party']['PartyIdentification']['ID']['schemeID']
         */
        $customer_partyIdentificationSchemeId =
            $party['Party']['PartyIdentification']['ID']['schemeID'] ?? null;
        $customer_postalAddress =
                                $this->buildCustomerPostalAddress($party);
        $customer_contact =
                                        $this->buildCustomerContact($party);
        $customer_partyTaxScheme =
                            $this->buildCustomerPartyTaxScheme($party);
        $customer_partyLegalEntity =
                                $this->buildCustomerLegalEntity($party);
        /**
         * @var array $party['Party]
         * @var array $party['Party']['EndPointID']
         * @var string $party['Party']['EndPointID']['value']
         */
        $customer_endpointID = $party['Party']['EndPointID']['value'] ?? '';
        /**
         * @var string $party['Party']['EndPointID']['schemeID']
         */
        $customer_endpointID_schemeID = $party
                                ['Party']['EndPointID']['schemeID'] ?? '';
        return new Party(
            $this->t,
            $customer_name,
            $customer_partyIdentificationId,
            $customer_partyIdentificationSchemeId,
            $customer_postalAddress,
            null,
            $customer_contact,
            $customer_partyTaxScheme,
            $customer_partyLegalEntity,
            $customer_endpointID,
            $customer_endpointID_schemeID,
        );
    }

    /**
     * @param Inv $invoice
     * @param paR $paR
     * @param cpR $cpR
     * @throws BuyerPostAddNf
     * @throws ClientNf
     * @return array
     */
    private function buildPeppolAccountingCustomerPartyArray(Inv $invoice,
        paR $paR, cpR $cpR): array
    {
        $client = $invoice->getClient();
        if ($client) {
            $postaladdress_id = $client->getPostaladdressId();
            $client_peppol = $cpR->repoClientPeppolLoadedquery($client->reqId());
            if (null == $postaladdress_id) {
                throw new BuyerPostAddNf();
            }
            if ($postaladdress_id) {
                $postaladdress = $paR->repoClient($postaladdress_id);
                $accounting_customer_party = [];
                $country_helper = new CountryHelper();
                if ($postaladdress && $client_peppol) {
                    $accounting_customer_party = [
                        'Party' => [
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
                                'StreetName' => $postaladdress->getStreetName(),
                                'AdditionalStreetName' =>
                                    $postaladdress->getAdditionalStreetName(),
                                'AddressLine' => [
                                    'Line' => $postaladdress->getBuildingNumber(),
                                ],
                                'CityName' => $postaladdress->getCityName(),
                                'PostalZone' => $postaladdress->getPostalZone(),
                                'CountrySubentity' =>
                                            $postaladdress->getCountrysubentity(),
                                'Country' => [
                                    'IdentificationCode' =>
$country_helper->getCountryIdentificationCodeWithLeague(
                                                    $postaladdress->getCountry()),
                                    'ListId' => 'ISO3166-1:Alpha2',
                                ],
                            ],
                            'PhysicalLocation' => [
                                'StreetName' =>
                                        (string) $client->getClientAddress1(),
                                'AdditionalStreetName' =>
                                        (string) $client->getClientAddress2(),
                                'AddressLine' => [
                                    'Line' =>
                                    (string) $client->getClientBuildingNumber(),
                                ],
                                'CityName' => (string) $client->getClientCity(),
                                'PostalZone' => (string) $client->getClientZip(),
                                'CountrySubentity' =>
                                            (string) $client->getClientState(),
                                'Country' => [
                                    'IdentificationCode' =>
$country_helper->getCountryIdentificationCodeWithLeague(
                                        (string) $client->getClientCountry()),
                                    'ListId' => 'ISO3166-1:Alpha2',
                                ],
                            ],
                            'Contact' => [
                                'Name' => $client->getClientName(),
                                'Telephone' =>
                                            (string) $client->getClientPhone(),
                                'ElectronicMail' => $client->getClientEmail(),
                            ],
                            'PartyTaxScheme' => [
                                'CompanyID' =>
                                        $client_peppol->getTaxschemecompanyid(),
                                'CompanyID_attributes' => [
                                    'schemeID' => '',
                                    'schemeAgencyID' => '',
                                ],
                                'TaxScheme' => [
                                    'ID' => $client_peppol->getTaxSchemeid(),
                                    'Attributes' => [
                                        'schemeID' => '',
                                        'schemeAgencyID' => '',
                                    ],
                                ],
                            ],
                            'PartyLegalEntity' => [
                                'RegistrationName' =>
                            $client_peppol->getLegalEntityRegistrationName(),
                                'CompanyID' =>
                                    $client_peppol->getLegalEntityCompanyid(),
                                'Attributes' => [
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
        throw new ClientNf($this->t);
    }

    /**
     * @param Inv $invoice
     * @param cpR $cpR
     * @throws ClientNf
     * @throws ClientsAccCostNf
     * @return string
     */
    private function accountingCost(Inv $invoice, cpR $cpR): string
    {
        $client = $invoice->getClient();
        if (null !== $client) {
            $client_peppol = $cpR->repoClientPeppolLoadedquery($client->reqId());
            if (null === $client_peppol) {
                throw new ClientNf($this->t);
            }
            if ($client_peppol->getAccountingCost()) {
                return $client_peppol->getAccountingCost();
            }
            if (empty($client_peppol->getAccountingCost())) {
                throw new ClientsAccCostNf($this->t);
            }
            return '';
        }
        throw new ClientNf($this->t);
    }

    /**
     * @param array $party
     * @return Contact
     */
    public function buildCustomerContact(array $party): Contact
    {
        /**
         * @var array $party['Party']
         * @var array $party['Party']['Contact']
         */
        $contact = $party['Party']['Contact'];

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
            null,
            $electronicMail,
        );
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
        $party_legal_entity = $party['Party']['PartyLegalEntity'] ?? [];
        /**
         * @var string $party_legal_entity['RegistrationName']
         */
        $registration_name = $party_legal_entity['RegistrationName'] ?? '';
        /**
         * @var string $party_legal_entity['CompanyID']
         */
        $company_id = $party_legal_entity['CompanyID'] ?? '';
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
        $party_tax_scheme = $party['Party']['PartyTaxScheme'] ?? [];
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
        $party_tax_scheme_companyID = $party_tax_scheme['CompanyID'];

        return new PartyTaxScheme(
            $party_tax_scheme_companyID,
            new TaxScheme($party_tax_scheme_ID),
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
        $postal_address = $party['Party']['PostalAddress'] ?? [];
        /**
         * @var string $postal_address['StreetName']
         */
        $street_name = $postal_address['StreetName'] ?? '';
        /**
         * @var string $postal_address['AdditionalStreetName']
         */
        $additional_street_name = $postal_address['AdditionalStreetName'] ?? '';
        /**
         * @var array $postal_address['AddressLine']
         */
        $address_line = $postal_address['AddressLine'] ?? [];
        /**
         * @var string $address_line['Line']
         */
        $line = $address_line['Line'] ?? '';
        /**
         * @var string $postal_address['CityName']
         */
        $city_name = $postal_address['CityName'] ?? '';
        /**
         * @var string $postal_address['PostalZone']
         */
        $postal_zone = $postal_address['PostalZone'] ?? '';
        /**
         * @var string $postal_address['CountrySubentity']
         */
        $country_sub_entity = $postal_address['CountrySubentity'] ?? '';
        /**
         * @var array $postal_address['Country']
         */
        $country = $postal_address['Country'] ?? [];
        /**
         * @var string $country['IdentificationCode']
         */
        $identification_code = $country['IdentificationCode'] ?? '';
        /**
         * @var string $country['ListId']
         */
        $listId = $country['ListId'] ?? '';
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
            false,
            true,
            false,
        );
    }
}
