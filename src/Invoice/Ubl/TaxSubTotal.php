<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

class TaxSubtotal
{
    private float $taxableAmounts = 0.00;
    private float $taxAmount = 0.00;
    private string $taxCategory = '';
    private float $taxCategoryPercent = 0.00;
    private array $taxSubtotal = [];
    private string $documentCurrency = '';
    
    // Used in src\Invoice\Ubl\Invoice.php function build_tax_sub_totals_array()
    // The array passed here is a sub-array ie. one of many subtotals 
    // - a subtotal is generated for each tax category.
    public function __construct(array $taxSubtotal) {
        $this->taxSubtotal = $taxSubtotal;
    }    
    
    public function load_values_from_array() : void {
       $array = $this->taxSubtotal;             
      /**
       * @var float $array['TaxableAmounts']
       */   
      $this->taxableAmounts = $array['TaxableAmounts'] ?: 0.00;
      /**
       * @var float $array['TaxAmount']
       */
      $this->taxAmount = $array['TaxAmount'] ?: 0.00;
      /**
       * @var string $array['TaxCategory']
       */
      $this->taxCategory = $array['TaxCategory'] ?: '';
      /**
       * @var float $array['TaxCategoryPercent']
       */
      $this->taxCategoryPercent = $array['TaxCategoryPercent'] ?: 0.00;        
      /**
       * @var string $array['DocumentCurrency']
       */
      $this->documentCurrency = $array['DocumentCurrency'] ?: '';
    }
        
    public function build_pre_serialized_array() : array 
    {
      $this->load_values_from_array();
      $return_array = [
        'name' =>Schema::CAC . 'TaxSubtotal',
        'value' => [
              [ 
                'name' => Schema::CBC . 'TaxableAmount',
                'value' => number_format($this->taxableAmounts ?: 0.00, 2, '.', ''),
                'attributes' => [
                    'currencyID' => $this->documentCurrency
                ]
               ],
               [
                 'name' => Schema::CBC . 'TaxAmount',
                 'value' => number_format($this->taxAmount ?: 0.00, 2, '.', ''),
                 'attributes' => [
                      'currencyID' => $this->documentCurrency
                 ]
                ],
                [
                  'name' => Schema::CAC . 'TaxCategory',
                  'value' => [
                    [
                       'name' => Schema::CBC . 'ID',
                       'value' => $this->taxCategory
                    ],
                    [
                       'name' => Schema::CBC . 'Percent',
                       'value' => $this->taxCategoryPercent
                    ],
                    // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-TaxTotal/cac-TaxSubtotal/cac-TaxCategory/cbc-TaxExemptionReasonCode/
                    $this->cbcTaxExemptionReasonCode($this->taxCategory),
                    $this->cbcTaxExemptionReason($this->taxCategory),
                    [
                       'name' => Schema::CAC .  'TaxScheme',
                       'value' => [
                          [
                            'name' => Schema::CBC . 'ID',
                            'value' => 'VAT' 
                          ]    
                       ]
                    ]
                  ]
                ],
              ]
            ];
        return $return_array;    
    }

    private function cbcTaxExemptionReasonCode(string $status_code) : array {
        $array = match($status_code) {
            // Exempt from tax
            'E' => [
               'name' => Schema::CBC . 'TaxExemptionReasonCode',
               'value' => 'E',
               'attributes' => []
            ],
            default => [],
        };
        return $array;
    }
    
     private function cbcTaxExemptionReason(string $status_code) : array {
        $array = match($status_code) {
            // Exempt from tax
            'E' => [
               'name' => Schema::CBC . 'TaxExemptionReason',
               'value' => 'Exempt',
               'attributes' => []
            ],
            default => [],
        };
        return $array;
    }
}