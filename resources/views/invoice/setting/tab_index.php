<?php
declare(strict_types=1);

/**
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $alert
 * @var string $actionName
 * @var string $csrf
 * @var string $general
 * @var string $invoices
 * @var string $quotes
 * @var string $salesorders
 * @var string $taxes
 * @var string $email
 * @var string $online_payment
 * @var string $projects_tasks
 * @var string $google_translate
 * @var string $vat_registered
 * @var string $mpdf
 * @var string $peppol_electronic_invoicing
 * @var string $storecove
 * @var string $invoiceplane
 * @var string $qrcode
 * @var string $telegram
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * 
 */

echo $alert;
?>

<div id="headerbar">
    <h1 class="headerbar-title"><?= $translator->translate('i.settings'); ?></h1>
    <?php
        echo $button::backSave();
    ?>
</div>

<ul id="settings-tabs" class="nav nav-tabs nav-tabs-noborder">
    <!-- https://getbootstrap.com/docs/5.0/components/navs-tabs/#using-data-attributes -->
    <li class="nav-item active" role="presentation" >
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#settings-general" style="text-decoration: none"><?= $translator->translate('i.general'); ?> </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-invoices" style="text-decoration: none"><?= $translator->translate('i.invoices'); ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-quotes" style="text-decoration: none"><?= $translator->translate('i.quotes'); ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-client-purchase-orders" style="text-decoration: none"><?= $translator->translate('invoice.salesorders'); ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-taxes" style="text-decoration: none"><?= $translator->translate('i.taxes'); ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-email" style="text-decoration: none"><?= $translator->translate('i.email'); ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-online-payment" style="text-decoration: none"><?= $translator->translate('g.online_payment'); ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-projects-tasks" style="text-decoration: none"><?= $translator->translate('i.projects'); ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-google-translate" style="text-decoration: none"><?= 'Google Translate' ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-vat-registered" style="text-decoration: none"><?= $translator->translate('invoice.invoice.vat'); ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-mpdf" style="text-decoration: none"><?= $translator->translate('invoice.invoice.mpdf'); ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-peppol" style="text-decoration: none"><?= $translator->translate('invoice.invoice.peppol'); ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-storecove" style="text-decoration: none"><?= $translator->translate('invoice.invoice.storecove'); ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-invoiceplane" style="text-decoration: none"><?= $translator->translate('invoice.invoice.invoiceplane'); ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-qrcode" style="text-decoration: none"><?= $translator->translate('invoice.invoice.qr.code'); ?></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-telegram" style="text-decoration: none"><i class="bi bi-telegram"><?= $translator->translate('invoice.invoice.telegram'); ?></i></button>
    </li>
</ul>

<form method="post" id="form-settings" action="<?= $urlGenerator->generate($actionName, $actionArguments) ?>"  enctype="multipart/form-data">

    <input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>">   

    <div class="tabbable tabs-below">

        <div class="tab-content">

            <div id="settings-general" class="tab-pane active">
                <?= $general; ?>
            </div>

            <div id="settings-invoices" class="tab-pane" role="tabpanel" aria-labelledby="settings-invoices">
                <?= $invoices; ?>
            </div>

            <div id="settings-quotes" class="tab-pane" role="tabpanel" aria-labelledby="settings-quotes">
                <?= $quotes; ?>
            </div>
            
            <div id="settings-client-purchase-orders" class="tab-pane" role="tabpanel" aria-labelledby="settings-client-purchase-orders">
                <?= $salesorders; ?>
            </div>

            <div id="settings-taxes" class="tab-pane" role="tabpanel" aria-labelledby="settings-taxes">
                <?= $taxes; ?>
            </div>

            <div id="settings-email" class="tab-pane" role="tabpanel" aria-labelledby="settings-email">
                <?= $email; ?>
            </div>

            <div id="settings-online-payment" class="tab-pane" role="tabpanel" aria-labelledby="settings-online-payment">
                <?= $online_payment; ?>
            </div>

            <div id="settings-projects-tasks" class="tab-pane" role="tabpanel" aria-labelledby="settings-project-tasks">
                <?= $projects_tasks; ?>
            </div>
            
            <div id="settings-google-translate" class="tab-pane" role="tabpanel" aria-labelledby="settings-google-translate">
                <?= $google_translate; ?>
            </div>
            
            <div id="settings-vat-registered" class="tab-pane" role="tabpanel" aria-labelledby="settings-vat-registered">
                <?= $vat_registered; ?>
            </div>
            
            <div id="settings-mpdf" class="tab-pane" role="tabpanel" aria-labelledby="settings-mpdf">
                <?= $mpdf; ?>
            </div>
            
            <div id="settings-peppol" class="tab-pane" role="tabpanel" aria-labelledby="settings-peppol">
                <?= $peppol_electronic_invoicing; ?>
            </div>
            
            <div id="settings-storecove" class="tab-pane" role="tabpanel" aria-labelledby="settings-storecove">
                <?= $storecove; ?>
            </div>
            
            <div id="settings-invoiceplane" class="tab-pane" role="tabpanel" aria-labelledby="settings-invoiceplane">
                <?= $invoiceplane; ?>
            </div>
            
            <div id="settings-qrcode" class="tab-pane" role="tabpanel" aria-labelledby="settings-qrcode">
                <?= $qrcode; ?>
            </div>
            
            <div id="settings-telegram" class="tab-pane" role="tabpanel" aria-labelledby="settings-telegram">
                <?= $telegram; ?>
            </div>

        </div>

    </div>

</form>


