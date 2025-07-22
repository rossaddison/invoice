<?php

declare(strict_types=1);

/**
 * Related logic: see main menu Settings...Invoice Archive  inv/archive
 * Related logic: see views\layout\invoice.php
 * Related logic: see resources\views\invoice\inv\archive.php
 * Related logic: see App\Invoice\Setting\SettingRepository function get_invoice_archived_files_with_filter($inv_number).
 *
 * @var App\Invoice\Setting\SettingRepository  $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface   $urlGenerator
 * @var array                                  $invoices_archive
 */
?>

<div class="table-responsive">
    <table class="table table-hover table-striped">
        <thead>
        <tr>
            <th><?php echo $translator->translate('invoice'); ?></th>
            <th><?php echo $translator->translate('created'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
            /**
             * @var string $invoice
             */
            foreach ($invoices_archive as $invoice) {
                ?>
            <tr>
                <td>
                    <a href="<?php echo $urlGenerator->generate('inv/download', ['invoice' => basename($invoice)]); ?>"
                       title="<?php echo $translator->translate('invoice'); ?>" style="text-decoration: none">
                        <?php echo basename($invoice); ?>
                    </a>
                </td>
                <td>
                    <?php echo date('F d Y H:i:s.', ($fileInvoice = filemtime($invoice)) != false ? $fileInvoice : null); ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
