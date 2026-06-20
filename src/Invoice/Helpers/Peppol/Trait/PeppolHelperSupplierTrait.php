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
        return new Contact(
            (string) $config['Contact']['Name'],
            (string) $config['Contact']['FirstName'],
            (string) $config['Contact']['LastName'],
            (string) $config['Contact']['Telephone'],
            null,
            (string) $config['Contact']['ElectronicMail'],
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
        return new Address(
            (string) $configAddress['StreetName'],
            (string) $configAddress['AdditionalStreetName'],
            (string) $configAddressLine['Line'],
            (string) $configAddress['CityName'],
            (string) $configAddress['PostalZone'],
            (string) $configAddress['CountrySubentity'],
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
