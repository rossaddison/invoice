<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;
use Yiisoft\Translator\TranslatorInterface as Translator;

class AdditionalDocumentReference implements XmlSerializable
{
    public function __construct(private readonly Translator $translator, private readonly string $id, private readonly ?string $documentType, private readonly ?string $documentDescription, private readonly array $attachments, private readonly bool $ubl_cr_114 = false)
    {
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function validate(): void
    {
        if (null === $this->documentDescription) {
            throw new \InvalidArgumentException($this->translator->translate('peppol.validator.Invoice.cac.AdditionalDocumentReference.cbc.DocumentDescription'));
        }
    }

    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $this->validate();
        $writer->write([Schema::CBC.'ID' => $this->id]);
        if (null !== $this->documentType && false === $this->ubl_cr_114) {
            $writer->write([
                Schema::CBC.'DocumentType' => $this->documentType,
            ]);
        }
        if (null !== $this->documentDescription) {
            $writer->write([
                Schema::CBC.'DocumentDescription' => $this->documentDescription,
            ]);
        }
        /**
         * @var Attachment $attachment
         */
        foreach ($this->attachments as $attachment) {
            $writer->write([Schema::CAC.'Attachment' => $attachment]);
        }
    }
}
