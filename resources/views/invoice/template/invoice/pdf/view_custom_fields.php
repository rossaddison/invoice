<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * Related logic: see App\Invoice\Helpers\PdfHelper function generateInvHtml
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\CustomValue\CustomValueRepository $cvR
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $custom_fields
 * @var array $inv_custom_values
 */
$location = 1;
/**
 * @var App\Infrastructure\Persistence\CustomField\CustomField $custom_field
 */
foreach ($custom_fields as $custom_field) {
 if ($custom_field->getLocation() == $location) {
  continue;
 }
 echo H::openTag('div'); //0
  $cvH->printFieldForPdf($translator, $inv_custom_values, $custom_field);
 echo H::closeTag('div'); //0
}
