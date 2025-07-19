<?php

declare(strict_types=1);

/**
 * @see src\Widget\Bootstrap5ModalPdf
 * @see inv\modal_layout_modal_pdf.php
 * @see 'src' property below, currently empty, fills with var url = $(location).attr('origin') + "/invoice/inv/pdf/1"; in inv.js'
 *      e.g. function $(document).on('click', '#inv_to_modal_pdf_confirm_with_custom_fields'
 */

use Yiisoft\Html\Html;

?>

<?= Html::openTag('iframe',
    [
        'id' => 'modal-view-inv-pdf',
        'src' => '',
        'style' => 'width: 100%; height: 500px;',
        'frameborder' => '0',
    ]); ?>
<?= Html::closeTag('iframe'); ?>