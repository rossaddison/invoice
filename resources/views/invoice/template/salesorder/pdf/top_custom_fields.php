<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * Related logic: see This will appear at the top of pdf\salesorder.php
 * Related logic: see App\Invoice\Helpers\PdfHelper function generateSalesorderPdf
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\CustomValue\CustomValueRepository $cvR
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $custom_fields
 * @var array $salesorder_custom_values
 */
$location = 1;
/**
 * @var App\Infrastructure\Persistence\CustomField\CustomField $custom_field
 */
foreach ($custom_fields as $custom_field) {
    if ($custom_field->getLocation() !== $location) {
        continue;
    }
    echo H::openTag('td'); //0
    $cvH->printFieldForPdf($translator, $salesorder_custom_values, $custom_field);
    echo H::closeTag('td'); //0
}
