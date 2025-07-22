<?php

declare(strict_types=1);

/**
 * @see App\Invoice\Helpers\PdfHelper function generate_quote_pdf
 *
 * @var App\Invoice\Helpers\CustomValuesHelper        $cvH
 * @var App\Invoice\CustomValue\CustomValueRepository $cvR
 * @var Yiisoft\Translator\TranslatorInterface        $translator
 * @var array                                         $custom_fields
 * @var array                                         $quote_custom_values
 */
?>
<?php
/**
 * @var App\Invoice\Entity\CustomField $custom_field
 */ foreach ($custom_fields as $custom_field) { ?>
    <?php if (1 !== $custom_field->getLocation()) {
        continue;
    } ?>
    <?php echo '<td>'; ?>
    <?php $cvH->print_field_for_pdf($translator, $quote_custom_values, $custom_field, $cvR); ?>                                   
    <?php echo '</td>'; ?>
<?php }
