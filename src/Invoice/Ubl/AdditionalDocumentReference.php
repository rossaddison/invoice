<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;
use Yiisoft\Translator\TranslatorInterface as Translator;
use InvalidArgumentException;

class AdditionalDocumentReference implements XmlSerializable {

  private string $id;
  private ?string $documentType;
  private ?string $documentDescription;
  private array $attachments;
  private bool $ubl_cr_114; 
  private Translator $translator;
  
  public function __construct(Translator $translator, string $id, ?string $documentType, ?string $documentDescription, array $attachments, bool $ubl_cr_114 = false) {
    $this->translator = $translator;
    $this->id = $id;
    $this->documentType = $documentType;
    $this->documentDescription = $documentDescription;
    $this->attachments = $attachments;
    $this->ubl_cr_114 = $ubl_cr_114;
  }
  
  /**
   * @return void
   * @throws InvalidArgumentException
   */
  public function validate() : void {
    if (empty($this->documentDescription)) {
      throw new InvalidArgumentException($this->translator->translate('invoice.peppol.validator.Invoice.cac.AdditionalDocumentReference.cbc.DocumentDescription'));
    }
  }

  /**
   *
   * @param Writer $writer
   * @return void
   */
  public function xmlSerialize(Writer $writer): void {
    $this->validate();
    $writer->write([Schema::CBC . 'ID' => $this->id]);
    if ($this->documentType !== null && $this->ubl_cr_114 === false) {
      $writer->write([
        Schema::CBC . 'DocumentType' => $this->documentType
      ]);
    }
    if ($this->documentDescription !== null) {
      $writer->write([
        Schema::CBC . 'DocumentDescription' => $this->documentDescription
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
