<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;
use InvalidArgumentException;
use Yiisoft\Translator\TranslatorInterface as Translator;

class Party implements XmlSerializable {

    private ?string $name;
    private ?string $partyIdentificationId;
    private ?string $partyIdentificationSchemeId;
    private ?Address $postalAddress;
    private ?Address $physicalLocation;
    private ?Contact $contact;
    private ?PartyTaxScheme $partyTaxScheme;
    private ?PartyLegalEntity $partyLegalEntity;
    private ?string $endpointID;
    private Translator $translator;
    
    /** @var null|string|int $endpointId_schemeID */
    private mixed $endpointID_schemeID;

    public function __construct(Translator $translator, ?string $name, ?string $partyIdentificationId, ?string $partyIdentificationSchemeId, ?Address $postalAddress, ?Address $physicalLocation, ?Contact $contact, ?PartyTaxScheme $partyTaxScheme, ?PartyLegalEntity $partyLegalEntity, ?string $endpointID, mixed $endpointID_schemeID) {
        $this->translator = $translator;
        $this->name = $name;
        $this->partyIdentificationId = $partyIdentificationId;
        $this->partyIdentificationSchemeId = $partyIdentificationSchemeId;
        $this->postalAddress = $postalAddress;
        $this->physicalLocation = $physicalLocation;
        $this->contact = $contact;
        $this->partyTaxScheme = $partyTaxScheme;
        $this->partyLegalEntity = $partyLegalEntity;
        $this->endpointID = $endpointID;
        /** @var string $this->endpointID_schemeID */
        $this->endpointID_schemeID = $endpointID_schemeID;
    }

    public function getPartyName(): ?string {
        return $this->name;
    }
    
    /**
     * 
     * @return void
     * @throws InvalidArgumentException
     */
    private function validate() : void {
      if (empty($this->endpointID)) {
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
     * @return void
     */
    public function xmlSerialize(Writer $writer) : void {
        $this->validate();
        if (!empty($this->endpointID) && !empty($this->endpointID_schemeID)) {
            $writer->write([
                [
                    'name' => Schema::CBC . 'EndpointID',
                    'value' => $this->endpointID,
                    'attributes' => [
                        'schemeID' => is_numeric($this->endpointID_schemeID) ? sprintf('%04d', +$this->endpointID_schemeID) : $this->endpointID_schemeID
                    ]
                ]
            ]);
        }

        if ($this->partyIdentificationId !== null) {
            $partyIdentificationAttributes = [];

            /**
             * For Danish Suppliers it is mandatory to use schemeID when PartyIdentification/ID is used for AccountingCustomerParty or AccountingSupplierParty
             * @see https://github.com/search?q=org%3AOpenPEPPOL+PartyIdentification&type=code
             */
            if (!empty($this->partyIdentificationSchemeId)) {
                $partyIdentificationAttributes['schemeID'] = $this->partyIdentificationSchemeId;
            }

            $writer->write([
                Schema::CAC . 'PartyIdentification' => [
                    [
                        'name' => Schema::CBC . 'ID',
                        'value' => $this->partyIdentificationId,
                        'attributes' => $partyIdentificationAttributes
                    ]
                ],
            ]);
        }

        if ($this->name !== null) {
          $writer->write([
              Schema::CAC . 'PartyName' => [
                  Schema::CBC . 'Name' => $this->name
              ]
          ]);
        }
        
        $writer->write([
            Schema::CAC . 'PostalAddress' => $this->postalAddress
        ]);

        if ($this->physicalLocation !== null) {
            $writer->write([
                Schema::CAC . 'PhysicalLocation' => [Schema::CAC . 'Address' => $this->physicalLocation]
            ]);
        }

        if ($this->partyTaxScheme !== null) {
            $writer->write([
                Schema::CAC . 'PartyTaxScheme' => $this->partyTaxScheme
            ]);
        }

        if ($this->partyLegalEntity !== null) {
            $writer->write([
                Schema::CAC . 'PartyLegalEntity' => $this->partyLegalEntity
            ]);
        }

        if ($this->contact !== null) {
            $writer->write([
                Schema::CAC . 'Contact' => $this->contact
            ]);
        }
    }
}
