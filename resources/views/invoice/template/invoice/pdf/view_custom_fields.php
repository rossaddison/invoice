<?php

declare(strict_types=1);

/**
 * Related logic: see App\Invoice\Helpers\PdfHelper function generate_inv_html
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\CustomValue\CustomValueRepository $cvR
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $custom_fields
 * @var array $inv_custom_values
 */

?>
<?php
/**
 * @var App\Invoice\Entity\CustomField $custom_field
 */
foreach ($custom_fields as $custom_field) : ?>
<?php if ($custom_field->getLocation() == 1) {
    continue;
} ?>
<div>
      <?php $cvH->print_field_for_pdf($translator, $inv_custom_values, $custom_field, $cvR); ?>
</div>    
<?php endforeach;
