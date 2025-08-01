<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class PartyLegalEntity implements XmlSerializable
{
    public function __construct(private string $registrationName, private string $companyId, private array $companyIdAttributes, private readonly string $companyLegalForm) {}

    /**
     * @return string
     */
    public function getRegistrationName(): string
    {
        return $this->registrationName;
    }

    /**
     * @param string $registrationName
     * @return PartyLegalEntity
     */
    public function setRegistrationName(string $registrationName): self
    {
        $this->registrationName = $registrationName;
        return $this;
    }

    public function getCompanyIdAttributeSchemeId(): string
    {
        $companyIdAttributes = $this->companyIdAttributes;
        /**
         * @var string $companyIdAttributes['schemeID']
         */
        return $companyIdAttributes['schemeID'] ?? '';
    }

    /**
     * @return string
     */
    public function getCompanyId(): string
    {
        return $this->companyId;
    }

    /**
     * @param string $companyId
     * @param array $companyIdAttributes
     * @return PartyLegalEntity
     */
    public function setCompanyId(string $companyId, array $companyIdAttributes = null): self
    {
        $this->companyId = $companyId;
        if (null !== $companyIdAttributes) {
            $this->companyIdAttributes = $companyIdAttributes;
        }
        return $this;
    }

    /**
     * @param Writer $writer
     */
    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([
            Schema::CBC . 'RegistrationName' => $this->registrationName,
        ]);
        if ($this->companyId !== '') {
            $writer->write([
                [
                    'name' => Schema::CBC . 'CompanyID',
                    'value' => $this->companyId,
                    /** Related logic: see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?q=CompanyId */
                    'attributes' => $this->companyIdAttributes,
                ],
            ]);
        }
    }
}
