<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * Related logic:
 * https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
 *                                          cac-AdditionalDocumentReference/
 *
 * $documentDescription (BT-123, DocumentDescription) is genuinely
 * optional (UBL cardinality 0..1) -- previously this class also carried
 * a validate() that unconditionally threw when it was null, contradicting
 * xmlSerialize()'s own null-means-omit treatment of the same field a few
 * lines below. Removed 2026-08-31: it wasn't gated on whether the
 * reference actually carries an attachment (its only single caller,
 * PeppolHelper::additionalDocumentReference(), uses this same class both
 * for a plain self-reference to the invoice and for real file
 * attachments), so it rejected the vast majority of ordinary invoices
 * that simply never had a description text, found via the real HTTP
 * Peppol route's EN16931 validator.
 */
class AdditionalDocumentReference implements XmlSerializable
{
    public function __construct(
        private readonly string $id,
        private readonly ?string $documentType,
        private readonly ?string $documentDescription,
        private readonly array $attachments,
        private readonly bool $ubl_cr_114 = false
    ) {
    }

    /**
     * @param Writer $writer
     */
    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $writer->write([Schema::CBC . 'ID' => $this->id]);
        if ($this->documentType !== null && $this->ubl_cr_114 === false) {
            $writer->write([
                Schema::CBC . 'DocumentType' => $this->documentType,
            ]);
        }
        if ($this->documentDescription !== null) {
            $writer->write([
                Schema::CBC . 'DocumentDescription' => $this->documentDescription,
            ]);
        }
        /**
         * @var Attachment $attachment
         */
        foreach ($this->attachments as $attachment) {
            $writer->write([Schema::CAC . 'Attachment' => $attachment]);
        }
    }
}
