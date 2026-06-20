<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Trait;

use App\Infrastructure\Persistence\CompanyPrivate\CompanyPrivate;
use Yiisoft\Config\ConfigInterface;
use Yiisoft\Yii\Runner\Http\HttpApplicationRunner;

trait SettingConfigTrait
{

    /**
     * Related logic:
       C:\wamp64\www\invoice\src\Invoice\Helpers\PdfHelper.php generate_inv_html
     * @return array
     */
    public function getPrivateCompanyDetails(): array
    {
        $companyLogoFileNameWithSuffix = '';
        $company = $this->getActiveCompany();
        $config = $this->getConfigParams();
        $params = $config->get('params');
        if (null !== $company) {
    /**
     * @var array $params['company']
     * @var string $params['company']['vat_id']
     * @var string $params['company']['tax_code']
     * @var string $params['company']['tax_currency'],
     * @var string $params['company']['iso_3166_country_identification_code']
     * @var string $params['company']['iso_3166_country_identification_list_id']
     */
            $company_array = [
                // Normally non-changing parameters
                'vat_id' => $params['company']['vat_id'],
                'tax_code' => $params['company']['tax_code'],
                'tax_currency' => $params['company']['tax_currency'],
                'iso_3166_country_identification_code' =>
                    $params['company']['iso_3166_country_identification_code'],
                'iso_3166_country_identification_list_id' =>
                    $params['company']['iso_3166_country_identification_list_id'],
                // Changeable paramters
                'name' => $company->getName(),
                'address_1' => $company->getAddress1(),
                'address_2' => $company->getAddress2(),
                'zip' => $company->getZip(),
                'city' => $company->getCity(),
                'state' => $company->getState(),
                'country' => $company->getCountry(),
                'phone' => $company->getPhone(),
                'fax' => $company->getFax(),
            ];
            /**
             * @var CompanyPrivate $private
             */
            foreach ($this->compPR->findAllPreloaded() as $private) {
                if ($private->reqCompanyId() === $company->reqId()
                    // site's logo: take the first logo where the current date
                    //  falls within the logo's start and end dates
                    && $private->getStartDate()?->format('Y-m-d')
                            < (new \DateTimeImmutable('now'))->format('Y-m-d')
                    && ($private->getEndDate()?->format('Y-m-d')
                            > (new \DateTimeImmutable('now'))->format('Y-m-d'))) {
                    $companyLogoFileNameWithSuffix =
                            (string) $private->getLogoFilename();
                }
            }
            $company_array['logofilenamewithsuffix'] =
                (!empty($companyLogoFileNameWithSuffix) ?
                        $companyLogoFileNameWithSuffix : 'logo.png');

            $company_array['logopublicsource'] =
                (!empty($companyLogoFileNameWithSuffix) ?
                        'destination.public.logo' : 'default.public.site');
            return $company_array;
        }
        return [];
    }

    /**
     * @return array
     */
    public function getConfigCompanyDetails(): array
    {
        $config = $this->getConfigParams();
        $params = $config->get('params');
/**
 * @var array $params['company']
 * @var string $params['company']['name']
 * @var string $params['company']['address_1']
 * @var string $params['company']['address_2']
 * @var string $params['company']['zip']
 * @var string $params['company']['city']
 * @var string $params['company']['state']
 * @var string $params['company']['country']
 * @var string $params['company']['vat_id']
 * @var string $params['company']['tax_code']
 * @var string $params['company']['tax_currency'],
 * @var string $params['company']['document_currency'],
 * @var string $params['company']['phone']
 * @var string $params['company']['fax']
 * @var string $params['company']['iso_3166_country_identification_code']
 * @var string $params['company']['iso_3166_country_identification_list_id']
 */
        return [
            'logo_path' => '/site/' . $this->publicLogo() . '.png',
            'name' => $params['company']['name'],
            'address_1' => $params['company']['address_1'],
            'address_2' => $params['company']['address_2'],
            'zip' => $params['company']['zip'],
            'city' => $params['company']['city'],
            'state' => $params['company']['state'],
            'country' => $params['company']['country'],
            'vat_id' => $params['company']['vat_id'],
            'tax_code' => $params['company']['tax_code'],
            'tax_currency' => $params['company']['tax_currency'],
            'document_currency' => $params['company']['document_currency'],
            'phone' => $params['company']['phone'],
            'fax' => $params['company']['fax'],
            'iso_3166_country_identification_code' =>
                $params['company']['iso_3166_country_identification_code'],
            'iso_3166_country_identification_list_id' =>
                $params['company']['iso_3166_country_identification_list_id'],
        ];
    }

    /**
     * @return string
     */
    public function getDocumentCurrencyCodeFromPeppolDetails(): string
    {
        /*
         *  @var array $this->getConfigPeppol()
         */
        $peppol_details = $this->getConfigPeppol();
        /** @var string $peppol_details['DocumentCurrencyCode'] */
        return $peppol_details['DocumentCurrencyCode'];
    }

    /**
     * @return string
     */
    public function getTaxCurrencyFromConfigDetails(): string
    {
        /*
         *  @var array $this->getConfigCompanyDetails()
         */
        $company_details = $this->getConfigCompanyDetails();
        /** @var string $company_details['tax_currency'] */
        return $company_details['tax_currency'];
    }

    /**
     * Used in EmailTemplateController add and edit functions which use the form
     * to merge with 'froms' FromDropDownController emails
     *
     * @return array
     */
    public function getConfigMailerEmails(): array
    {
        $config = $this->getConfigParams();
        $params = $config->get('params');

        // Currently two adminEmail and senderEmail
        return (array) $params['mailer'];
    }

    /**
     * @return string
     */
    public function getConfigSenderEmail(): string
    {
        $mailer_emails = $this->getConfigMailerEmails();
        /**
         * @var string $mailer_emails['senderEmail']
         */
        return $mailer_emails['senderEmail'];
    }

    /**
     * @return string
     */
    public function getConfigAdminEmail(): string
    {
        $mailer_emails = $this->getConfigMailerEmails();
        /**
         * @var string $mailer_emails['adminEmail']
         */
        return $mailer_emails['adminEmail'];
    }

    /**
     * Related logic: see config/params.php
     * @return int
     */
    public function getSchemaProvidersMode(): int
    {
        $config = $this->getConfigParams();
        $params = $config->get('params');

        $yii_cycle_array = (array) $params['yiisoft/yii-cycle'];
        $schema_providers_array = (array) $yii_cycle_array['schema-providers'];
        $php_file_array = (array) $schema_providers_array[
                            \Cycle\Schema\Provider\PhpFileSchemaProvider::class];
        return (int) $php_file_array['mode'];
    }

    /**
     * @return array
     */
    public function getConfigPeppol(): array
    {
        $config = $this->getConfigParams();
        $params = $config->get('params');
        $pp = (array) $params['peppol'];
        $ppi = (array) $pp['invoice'];
        $ppp = (array) $ppi['AccountingSupplierParty'];
        $party = (array) $ppp['Party'];
        $partyIdentification = (array) $party['PartyIdentification'];
        $partyIdentificationId = (array) $partyIdentification['ID'];
        return [
            'SupplierPartyIdentificationId' =>
                $partyIdentificationId['value'],
            'SupplierPartyIdentificationSchemeId' =>
                $partyIdentificationId['schemeID'],
            'SupplierPartyIdentificationPostalAddress' =>
                $party['PostalAddress'],
            'Contact' => $party['Contact'],
            'PartyTaxScheme' => $party['PartyTaxScheme'],
            'PartyLegalEntity' => $party['PartyLegalEntity'],
            'EndPointID' => $party['EndPointID'],
            'PaymentMeans' => $ppi['PaymentMeans'],
            'TaxCurrencyCode' => $ppi['TaxCurrencyCode'],
            'DocumentCurrencyCode' => $ppi['DocumentCurrencyCode'],
        ];
    }

    /**
     * @return ConfigInterface
     */
    public function getConfigParams(): ConfigInterface
    {
        $rootPath = dirname(__DIR__, 4);
        $http_runner = new HttpApplicationRunner(
            //$rootPath
            $rootPath,
            //$debug
            false,
            //$checkEvents
            false,
            //$environment
            null,
            //$bootstrapGroup
            'bootstrap-web',
            //$eventsGroup
            'events-web',
            //$diGroup
            'di-web',
            //$diProvidersGroup
            'di-providers-web',
            //$diDelegatesGroup
            'di-delegates-web',
            //$diTagsGroup
            'di-tags-web',
            //$paramsGroup
            'params-web',
            //$nestedParamsGroups
            ['params'],
            //$nestedEventsGroups
            ['events'],
        );
        return $http_runner->getConfig();
    }
}
