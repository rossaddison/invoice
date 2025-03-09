<?php

declare(strict_types=1);

/**
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $alert
 * @var string $csrf
 * @var string $partial_inv_archive
 */

echo $alert;
?> 

<div id="headerbar">
    <h1 class="headerbar-title"><?= $translator->translate('i.invoice_archive'); ?></h1>
    <div class="headerbar-item pull-right">
       <!-- No Url Generator here. Just post -->
       <form method="post">
            <input type="hidden" name="_csrf" value="<?= $csrf; ?>">
            <div class="input-group" hidden>
                <label for="invoice_number"><?= $translator->translate('i.invoice_number'); ?></label>
                <input name="invoice_number" id="invoice_number" type="text" class="form-control" value="<?php $body['invoice_number'] ?? '#'; ?>">
                <span class="input-group-btn">
                    <button class="btn btn-primary btn-sm" type="submit"><?= $translator->translate('i.filter_invoices'); ?></button>
                </span>
            </div>
        </form>
    </div>
</div>
<div id="content" class="table-content">
    <div id="filter_results">
        <?= $partial_inv_archive; ?>
    </div>
</div>
