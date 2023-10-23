<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class PartyLegalEntity implements XmlSerializable {

    private string $registrationName;
    private array $companyIdAttributes;
    private string $companyLegalForm;
    private string $companyId;

    public function __construct(string $registrationName, string $companyId, array $companyIdAttributes, string $companyLegalForm) {
        $this->registrationName = $registrationName;
        $this->companyId = $companyId;
        $this->companyIdAttributes = $companyIdAttributes;
        $this->companyLegalForm = $companyLegalForm;
    }

    /**
     *
     * @return string
     */
    public function getRegistrationName(): string
    {
        return $this->registrationName;
    }

    /**
     * 
     * @param string $registrationName
     * @return PartyLegalEntity
     */
    public function setRegistrationName(string $registrationName): PartyLegalEntity
    {
        $this->registrationName = $registrationName;
        return $this;
    }
    
    public function getCompanyIdAttributeSchemeId(): string {
        $companyIdAttributes = $this->companyIdAttributes;
        /**
         * @var string $companyIdAttributes['schemeID']
         */
        return $companyIdAttributes['schemeID'] ?? '';
    }

    /**
     * 
     * @return string
     */
    public function getCompanyId(): string
    {
        return $this->companyId;
    }

    /**
     * 
     * @param string $companyId
     * @param array $companyIdAttributes
     * @return PartyLegalEntity
     */
    public function setCompanyId(string $companyId, array $companyIdAttributes = null): PartyLegalEntity
    {
        $this->companyId = $companyId;
        if (!empty($companyIdAttributes)) {
          $this->companyIdAttributes = $companyIdAttributes;
        }
        return $this;
    }

    /**
     * 
     * @param Writer $writer
     * @return void
     */
    public function xmlSerialize(Writer $writer): void {
        $writer->write([
            Schema::CBC . 'RegistrationName' => $this->registrationName,
        ]);
        if ($this->companyId !== '') {
            $writer->write([
                [
                    'name' => Schema::CBC . 'CompanyID',
                    'value' => $this->companyId,
                    /** @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?q=CompanyId */
                    'attributes' => $this->companyIdAttributes,
                ],
            ]);
        }
    }
}
