<?php

declare(strict_types=1);

/**
 * Related logic: see main menu Settings...Invoice Archive  inv/archive
 * Related logic: see views\layout\invoice.php
 * Related logic: see resources\views\invoice\inv\archive.php
 * Related logic: see App\Invoice\Setting\SettingRepository function get_invoice_archived_files_with_filter($inv_number)
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $invoices_archive
 */

?>

<div class="table-responsive">
    <table class="table table-hover table-striped">
        <thead>
        <tr>
            <th><?= $translator->translate('invoice'); ?></th>
            <th><?= $translator->translate('created'); ?></th>
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
                    <a href="<?= $urlGenerator->generate('inv/download', ['invoice' => basename($invoice)]); ?>"
                       title="<?= $translator->translate('invoice'); ?>" style="text-decoration: none">
                        <?= basename($invoice); ?>
                    </a>
                </td>
                <td>
                    <?= date("F d Y H:i:s.", ((($fileInvoice = filemtime($invoice)) <> false ? $fileInvoice : null))); ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
