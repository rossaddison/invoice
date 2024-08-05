<?php

declare(strict_types=1);

/**
 * @see main menu Settings...Invoice Archive  inv/archive
 * @see views\layout\invoice.php
 * @see resources\views\invoice\inv\archive.php
 * @see App\Invoice\Setting\SettingRepository function get_invoice_archived_files_with_filter($inv_number)
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
            <th><?= $translator->translate('i.invoice'); ?></th>
            <th><?= $translator->translate('i.created'); ?></th>
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
                       title="<?= $translator->translate('i.invoice'); ?>" style="text-decoration: none">
                        <?= basename($invoice); ?>
                    </a>
                </td>
                <td>
                    <?= date("F d Y H:i:s.", filemtime($invoice)); ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
