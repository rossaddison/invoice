<?php

declare(strict_types=1);
    
/**
 * @see This will appear at the bottom of pdf\quote.php
 * @see App\Invoice\Helpers\PdfHelper function generate_quote_html 
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\CustomValue\CustomValueRepository $cvR
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $custom_fields
 * @var array $quote_custom_values
 */    

?>
<div col="row">
    <?php
    /**
     * @var App\Invoice\Entity\CustomField $custom_field
     */
    foreach ($custom_fields as $custom_field): ?>
    <?php if ($custom_field->getLocation() == 1) {continue;} ?>
    <div>
          <?php $cvH->print_field_for_pdf($translator, $quote_custom_values, $custom_field, $cvR); ?>
    </div>    
    <?php endforeach; ?>        
</div> 