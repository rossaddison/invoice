<?php

declare(strict_types=1);

/**
 * @see This will appear at the top of pdf\salesorder.php
 * @see App\Invoice\Helpers\PdfHelper function generate_salesorder_pdf
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\CustomValue\CustomValueRepository $cvR
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $custom_fields
 * @var array $salesorder_custom_values
 */

?>
<?php
/**
 * @var App\Invoice\Entity\CustomField $custom_field
 */ foreach ($custom_fields as $custom_field): ?>
    <?php if ($custom_field->getLocation() !== 1) {
        continue;
    } ?>
    <?php echo '<td>'; ?>
    <?php  $cvH->print_field_for_pdf($translator, $salesorder_custom_values, $custom_field, $cvR); ?>                                   
    <?php echo '</td>'; ?>
<?php endforeach;
