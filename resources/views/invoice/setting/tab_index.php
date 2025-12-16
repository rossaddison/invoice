<?php

declare(strict_types=1);

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $alert
 * @var string $actionName
 * @var string $bootstrap5
 * @var string $csrf
 * @var string $frontPage
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
 * @var string $mtd
 * @var string $oauth2
 * @var string $peppol_electronic_invoicing
 * @var string $storecove
 * @var string $invoiceplane
 * @var string $qrcode
 * @var string $active
 * @var string $telegram
 * @var string $tfa
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';
?>

<div id="headerbar">
    <h1 class="headerbar-title"><?= $translator->translate('settings'); ?></h1>
    <?php
        echo $button::backSave();
?>
</div>

<ul id="settings-tabs" class="nav nav-tabs nav-tabs-noborder">
    <!-- https://getbootstrap.com/docs/5.0/components/navs-tabs/#using-data-attributes -->
    <li class="<?= 'nav-item' . ($active == 'front-page' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'front-page' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-front-page" style="text-decoration: none"><?= $translator->translate('front.page'); ?> </button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'oauth2' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'oauth2' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-oauth2" style="text-decoration: none"><?= 'OAuth2'; ?> </button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'general' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'general' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-general" style="text-decoration: none"><?= $translator->translate('general'); ?> </button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'invoices' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'invoices' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-invoices" style="text-decoration: none"><?= $translator->translate('invoices'); ?></button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'quotes' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'quotes' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-quotes" style="text-decoration: none"><?= $translator->translate('quotes'); ?></button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'client-purchase-orders' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'client-purchase-orders' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-client-purchase-orders" style="text-decoration: none"><?= $translator->translate('salesorders'); ?></button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'taxes' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'taxes' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-taxes" style="text-decoration: none"><?= $translator->translate('taxes'); ?></button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'email' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'email' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-email" style="text-decoration: none"><?= $translator->translate('email'); ?></button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'online-payment' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'online-payment' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-online-payment" style="text-decoration: none"><?= $translator->translate('online.payment'); ?></button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'projects-tasks' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'projects-tasks' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-projects-tasks" style="text-decoration: none"><?= $translator->translate('projects'); ?></button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'google-translate' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'google-translate' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-google-translate" style="text-decoration: none"><?= 'Google Translate' ?></button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'vat-registered' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'vat-registered' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-vat-registered" style="text-decoration: none"><?= $translator->translate('vat'); ?></button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'mpdf' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'mpdf' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-mpdf" style="text-decoration: none"><?= $translator->translate('mpdf'); ?></button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'peppol' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'peppol' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-peppol" style="text-decoration: none"><?= $translator->translate('peppol.electronic.invoicing'); ?></button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'storecove' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'storecove' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-storecove" style="text-decoration: none"><?= $translator->translate('storecove'); ?></button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'invoiceplane' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'invoiceplane' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-invoiceplane" style="text-decoration: none"><?= $translator->translate('invoiceplane'); ?></button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'qrcode' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'qrcode' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-qrcode" style="text-decoration: none"><?= $translator->translate('qr.code'); ?></button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'telegram' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'telegram' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-telegram" style="text-decoration: none"><i class="bi bi-telegram"><?= ' ' . $translator->translate('telegram'); ?></i></button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'bootstrap5' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'bootstrap5' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-bootstrap5" style="text-decoration: none"><i class="bi bi-bootstrap"><?= ' ' . $translator->translate('bootstrap5'); ?></i></button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'mtd' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'mtd' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-mtd" style="text-decoration: none"><?= $translator->translate('mtd'); ?> </button>
    </li>
    <li class="<?= 'nav-item' . ($active == 'tfa' ? ' active' : ''); ?>" role="presentation">
        <button class="<?= 'nav-link' . ($active == 'tfa' ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-tfa" style="text-decoration: none"><?= $translator->translate('two.factor.authentication'); ?> </button>
    </li>
</ul>

<form method="post" id="form-settings" action="<?= $urlGenerator->generate($actionName, $actionArguments); ?>"  enctype="multipart/form-data">

    <input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>">   

    <div class="tabbable tabs-below">

        <div class="tab-content">
            <div id="settings-front-page" class="<?= 'tab-pane' . ($active == 'front-page' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="front-page" >
                <?= $frontPage; ?>
            </div>
            
            <div id="settings-oauth2" class="<?= 'tab-pane' . ($active == 'oauth2' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="oauth2" >
                <?= $oauth2; ?>
            </div>
            
            <div id="settings-general" class="<?= 'tab-pane' . ($active == 'general' ? ' active' : ''); ?>" >
                <?= $general; ?>
            </div>

            <div id="settings-invoices" class="<?= 'tab-pane' . ($active == 'invoices' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-invoices">
                <?= $invoices; ?>
            </div>

            <div id="settings-quotes" class="<?= 'tab-pane' . ($active == 'quotes' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-quotes">
                <?= $quotes; ?>
            </div>
            
            <div id="settings-client-purchase-orders" class="<?= 'tab-pane' . ($active == 'client-purchase-orders' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-client-purchase-orders">
                <?= $salesorders; ?>
            </div>
            
            <div id="settings-taxes" class="<?= 'tab-pane' . ($active == 'taxes' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-taxes">
                <?= $taxes; ?>
            </div>

            <div id="settings-email" class="<?= 'tab-pane' . ($active == 'email' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-email">
                <?= $email; ?>
            </div>

            <div id="settings-online-payment" class="<?= 'tab-pane' . ($active == 'online-payment' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-online-payment">
                <?= $online_payment; ?>
            </div>

            <div id="settings-projects-tasks" class="<?= 'tab-pane' . ($active == 'projects-tasks' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-project-tasks">
                <?= $projects_tasks; ?>
            </div>
            
            <div id="settings-google-translate" class="<?= 'tab-pane' . ($active == 'google-translate' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-google-translate">
                <?= $google_translate; ?>
            </div>
            
            <div id="settings-vat-registered" class="<?= 'tab-pane' . ($active == 'vat-registered' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-vat-registered">
                <?= $vat_registered; ?>
            </div>
            
            <div id="settings-mpdf" class="<?= 'tab-pane' . ($active == 'mpdf' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-mpdf">
                <?= $mpdf; ?>
            </div>
            
            <div id="settings-peppol" class="<?= 'tab-pane' . ($active == 'peppol' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-peppol">
                <?= $peppol_electronic_invoicing; ?>
            </div>
            
            <div id="settings-storecove" class="<?= 'tab-pane' . ($active == 'storecove' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-storecove">
                <?= $storecove; ?>
            </div>
            
            <div id="settings-invoiceplane" class="<?= 'tab-pane' . ($active == 'invoiceplane' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-invoiceplane">
                <?= $invoiceplane; ?>
            </div>
            
            <div id="settings-qrcode" class="<?= 'tab-pane' . ($active == 'qrcode' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-qrcode">
                <?= $qrcode; ?>
            </div>
            
            <div id="settings-telegram" class="<?= 'tab-pane' . ($active == 'telegram' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-telegram">
                <?= $telegram; ?>
            </div>
            
            <div id="settings-bootstrap5" class="<?= 'tab-pane' . ($active == 'bootstrap5' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-bootstrap5">
                <?= $bootstrap5; ?>
            </div>
            
            <div id="settings-mtd" class="<?= 'tab-pane' . ($active == 'mtd' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-mtd">
                <?= $mtd; ?>
            </div>
            
            <div id="settings-tfa" class="<?= 'tab-pane' . ($active == 'tfa' ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-tfa">
                <?= $tfa; ?>
            </div>
            
        </div>

    </div>

</form>


