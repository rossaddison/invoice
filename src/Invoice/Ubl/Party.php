<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;
use InvalidArgumentException;
use Yiisoft\Translator\TranslatorInterface as Translator;

class Party implements XmlSerializable
{
    public function __construct(private readonly Translator $translator, private readonly ?string $name, private readonly ?string $partyIdentificationId, private readonly ?string $partyIdentificationSchemeId, private readonly ?Address $postalAddress, private readonly ?Address $physicalLocation, private readonly ?Contact $contact, private readonly ?PartyTaxScheme $partyTaxScheme, private readonly ?PartyLegalEntity $partyLegalEntity, private readonly ?string $endpointID, private readonly mixed $endpointID_schemeID)
    {
    }

    public function getPartyName(): ?string
    {
        return $this->name;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validate(): void
    {
        if (null == $this->endpointID) {
            /**
             * Error
             * Location: invoice_8x8vShcxINV111_peppol
             * Element/context: /:Invoice[1]/cac:AccountingCustomerParty[1]/cac:Party[1]
             * XPath test: cbc:EndpointID
             * Error message: Buyer electronic address MUST be provided
             */
            throw new InvalidArgumentException($this->translator->translate('invoice.peppol.validator.Invoice.cac.Party.cbc.EndPointID'));
        }
    }

    /**
     * @param Writer $writer
     */
    public function xmlSerialize(Writer $writer): void
    {
        $this->validate();
        if (null !== $this->endpointID && null !== $this->endpointID_schemeID) {
            $writer->write([
                [
                    'name' => Schema::CBC . 'EndpointID',
                    'value' => $this->endpointID,
                    'attributes' => [
                        'schemeID' => is_numeric($this->endpointID_schemeID) ? sprintf('%04d', +$this->endpointID_schemeID) : $this->endpointID_schemeID,
                    ],
                ],
            ]);
        }

        if ($this->partyIdentificationId !== null) {
            $partyIdentificationAttributes = [];

            /**
             * For Danish Suppliers it is mandatory to use schemeID when PartyIdentification/ID is used for AccountingCustomerParty or AccountingSupplierParty
             * @see https://github.com/search?q=org%3AOpenPEPPOL+PartyIdentification&type=code
             */
            if (null !== $this->partyIdentificationSchemeId) {
                $partyIdentificationAttributes['schemeID'] = $this->partyIdentificationSchemeId;
            }

            $writer->write([
                Schema::CAC . 'PartyIdentification' => [
                    [
                        'name' => Schema::CBC . 'ID',
                        'value' => $this->partyIdentificationId,
                        'attributes' => $partyIdentificationAttributes,
                    ],
                ],
            ]);
        }

        if ($this->name !== null) {
            $writer->write([
                Schema::CAC . 'PartyName' => [
                    Schema::CBC . 'Name' => $this->name,
                ],
            ]);
        }

        $writer->write([
            Schema::CAC . 'PostalAddress' => $this->postalAddress,
        ]);

        if ($this->physicalLocation !== null) {
            $writer->write([
                Schema::CAC . 'PhysicalLocation' => [Schema::CAC . 'Address' => $this->physicalLocation],
            ]);
        }

        if ($this->partyTaxScheme !== null) {
            $writer->write([
                Schema::CAC . 'PartyTaxScheme' => $this->partyTaxScheme,
            ]);
        }

        if ($this->partyLegalEntity !== null) {
            $writer->write([
                Schema::CAC . 'PartyLegalEntity' => $this->partyLegalEntity,
            ]);
        }

        if ($this->contact !== null) {
            $writer->write([
                Schema::CAC . 'Contact' => $this->contact,
            ]);
        }
    }
}
