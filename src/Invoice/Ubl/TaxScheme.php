<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class TaxScheme implements XmlSerializable {

  private string $id = '';

  public function __construct(string $id) {
    $this->id = $id;
  }

  /**
   * @return string
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * @param Writer $writer
   * @return void
   */
  public function xmlSerialize(Writer $writer): void {
    
    $writer->write([
      'name' => Schema::CAC . 'TaxScheme',
      'value' => [
        'name' => Schema::CBC . 'ID',
        'value' => $this->getId()
      ]]);    
  }
}
