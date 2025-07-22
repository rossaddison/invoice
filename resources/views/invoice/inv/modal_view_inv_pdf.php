<?php

declare(strict_types=1);

/**
 * Related logic: see src\Widget\Bootstrap5ModalPdf
 * Related logic: see inv\modal_layout_modal_pdf.php
 * Related logic: see 'src' property below, currently empty, fills with var url = $(location).attr('origin') + "/invoice/inv/pdf/1"; in inv.js'
 *      e.g. function $(document).on('click', '#inv_to_modal_pdf_confirm_with_custom_fields'.
 */

use Yiisoft\Html\Html;

?>

<?php echo Html::openTag('iframe',
    [
        'id'          => 'modal-view-inv-pdf',
        'src'         => '',
        'style'       => 'width: 100%; height: 500px;',
        'frameborder' => '0',
    ]); ?>
<?php echo Html::closeTag('iframe'); ?>