<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\ClientPeppol\ClientPeppolRepository as cpR;
use App\Invoice\Ubl\{Address, Contact, Country, Party, PartyLegalEntity, PartyTaxScheme, TaxScheme};
use App\Invoice\Helpers\Peppol\Exception\{
    PeppolClientNotFoundException as ClientNf,
    PeppolClientIdNotFoundException as ClientIdNf,
    PeppolSupplierAssignedAccountIdNotFoundException as SAAINf,
};

trait PeppolHelperSupplierTrait
{
    private function buildSupplierParty(): Party
    {
        $config_company_details = $this->s->getConfigCompanyDetails();
        /**
        * @var string $config_company_details['name']
        */
        $supplier_name = $config_company_details['name'];
        $config_peppol = $this->s->getConfigPeppol();
        /**
        * @var string $config_peppol['SupplierPartyIdentificationId']
        * @var string $config_peppol['SupplierPartyIdentificationSchemeId']
        */
        $supplier_partyIdentificationId =
            $config_peppol['SupplierPartyIdentificationId'];
        $supplier_partyIdentificationSchemeId =
                $config_peppol['SupplierPartyIdentificationSchemeId'];
        $supplier_postalAddress = $this->SupplierPostalAddress();
        $supplier_contact = $this->SupplierContact();
        $supplier_partyTaxScheme = $this->SupplierPartyTaxScheme();
        $supplier_partyLegalEntity = $this->SupplierPartyLegalEntity();
        $supplier_endpointID = $this->SupplierEndpointID();
        $supplier_endpointID_schemeID = $this->SupplierEndpointIDSchemeID();
        return new Party(
            $this->t,
            $supplier_name,
            $supplier_partyIdentificationId,
            $supplier_partyIdentificationSchemeId,
            $supplier_postalAddress,
            null,
            $supplier_contact,
            $supplier_partyTaxScheme,
            $supplier_partyLegalEntity,
            $supplier_endpointID,
            $supplier_endpointID_schemeID,
        );
    }

    public function supplierContact(): Contact
    {
        $config = $this->s->getConfigPeppol();
        /**
         * @var array $config
         * @var array $config['Contact']
         */
        // Same '' vs null normalization as the customer/delivery Contact
        // and Address builders (Contact::xmlSerialize() only omits a field
        // when it's exactly null) -- a blank config value here would
        // otherwise slip through as an empty element too.
        return new Contact(
            (string) $config['Contact']['Name'] ?: null,
            (string) $config['Contact']['FirstName'] ?: null,
            (string) $config['Contact']['LastName'] ?: null,
            (string) $config['Contact']['Telephone'] ?: null,
            null,
            (string) $config['Contact']['ElectronicMail'] ?: null,
        );
    }

    /**
     * @return string
     */
    public function supplierEndpointID(): string
    {
        $config = $this->s->getConfigPeppol();
        /**
         * @var array $config
         * @var array $config['EndPointID']
         */
        return (string) $config['EndPointID']['value'];
    }

    /**
     * @return string
     */
    public function supplierEndPointIDSchemeID(): string
    {
        $config = $this->s->getConfigPeppol();
        /**
         * @var array $config
         * @var array $config['EndPointID']
         */
        return (string) $config['EndPointID']['schemeID'];
    }

    /**
     * @return PartyLegalEntity
     */
    public function supplierPartyLegalEntity(): PartyLegalEntity
    {
        $config = $this->s->getConfigPeppol();
        /**
         * @var array $config
         * @var array $config['PartyLegalEntity']
         */
        return new PartyLegalEntity(
            (string) $config['PartyLegalEntity']['RegistrationName'],
            (string) $config['PartyLegalEntity']['CompanyID'],
            (array) $config['PartyLegalEntity']['Attributes'],
            (string) $config['PartyLegalEntity']['CompanyLegalForm'],
        );
    }

    /**
     * @return PartyTaxScheme
     */
    public function supplierPartyTaxScheme(): PartyTaxScheme
    {
        $config = $this->s->getConfigPeppol();
        /**
         * @var array $config['PartyTaxScheme']
         * @var array $config['PartyTaxScheme']['TaxScheme']
         */
        $tax_scheme = $config['PartyTaxScheme']['TaxScheme'];
        /**
         * @var string $tax_scheme['ID']
         */
        $id = $tax_scheme['ID'] ?? '';

        $taxScheme = new TaxScheme(
            $id,
        );
        /**
         * @var array $config
         * @var array $config['PartyTaxScheme']
         */
        return new PartyTaxScheme(
            (string) $config['PartyTaxScheme']['CompanyID'],
            $taxScheme,
        );
    }

    /**
     * @return Address
     */
    public function supplierPostalAddress(): Address
    {
        $config = $this->s->getConfigPeppol();
        $address = 'SupplierPartyIdentificationPostalAddress';
        $configAddress = (array) $config[$address];
        $configAddressCountry = (array) $configAddress['Country'];
        $configAddressLine = (array) $configAddress['AddressLine'];
        // Same '' vs null normalization as the customer/delivery Address
        // builders (Address::xmlSerialize() only omits a field when it's
        // exactly null) -- a blank config value here would otherwise slip
        // through as an empty element too.
        return new Address(
            (string) $configAddress['StreetName'] ?: null,
            (string) $configAddress['AdditionalStreetName'] ?: null,
            (string) $configAddressLine['Line'] ?: null,
            (string) $configAddress['CityName'] ?: null,
            (string) $configAddress['PostalZone'] ?: null,
            (string) $configAddress['CountrySubentity'] ?: null,
            new Country(
                (string) $configAddressCountry['IdentificationCode'],
                (string) $configAddressCountry['ListId'],
            ),
            true,
            false,
            false,
        );
    }

    /**
     * Retrieve Client's Account Id given by Supplier
     * @param Inv $invoice
     * @param cpR $cpR
     * @return string
     */
    private function supplierAssignedAccountId(Inv $invoice, cpR $cpR): string
    {
        $client = $invoice->getClient();
        if (null !== $client) {
            $client_peppol = $cpR->repoClientPeppolLoadedquery($client->reqId());
            $supplier_assigned_account_id = null !== $client_peppol ?
                    $client_peppol->getSupplierAssignedAccountId()
              : throw new ClientIdNf($this->t);
        } else {
            throw new ClientNf($this->t);
        }
        if (empty($supplier_assigned_account_id)) {
            throw new SAAINf($this->t);
        }
        return $supplier_assigned_account_id;
    }
}
