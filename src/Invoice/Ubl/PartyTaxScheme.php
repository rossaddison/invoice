<?php
declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class PartyTaxScheme implements XmlSerializable {

  private string $companyId;
  private TaxScheme $taxScheme;

  public function __construct(string $companyId, TaxScheme $taxScheme) {
    $this->companyId = $companyId;
    $this->taxScheme = $taxScheme;
  }
  
  public function getCompanyId() : string
  {
    return $this->companyId;
  }
  
  public function getTaxScheme() : TaxScheme
  {
    return $this->taxScheme;
  }
  
  /**
   * @see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/search?p=3&q=PartyTaxScheme
   * @param Writer $writer
   * @return void
   */
  public function xmlSerialize(Writer $writer): void {
    $writer->write([
      'name' => Schema::CBC . 'CompanyID',
      'value' => $this->companyId,
    ]);
    $this->taxScheme->xmlSerialize($writer);
  }

}
