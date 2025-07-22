<?php

declare(strict_types=1);

/**
 * @var App\Widget\Button                      $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface   $urlGenerator
 * @var string                                 $alert
 * @var string                                 $actionName
 * @var string                                 $bootstrap5
 * @var string                                 $csrf
 * @var string                                 $frontPage
 * @var string                                 $general
 * @var string                                 $invoices
 * @var string                                 $quotes
 * @var string                                 $salesorders
 * @var string                                 $taxes
 * @var string                                 $email
 * @var string                                 $online_payment
 * @var string                                 $projects_tasks
 * @var string                                 $google_translate
 * @var string                                 $vat_registered
 * @var string                                 $mpdf
 * @var string                                 $mtd
 * @var string                                 $oauth2
 * @var string                                 $peppol_electronic_invoicing
 * @var string                                 $storecove
 * @var string                                 $invoiceplane
 * @var string                                 $qrcode
 * @var string                                 $active
 * @var string                                 $telegram
 * @var string                                 $tfa
 *
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */
echo $alert;
?>

<div id="headerbar">
    <h1 class="headerbar-title"><?php echo $translator->translate('settings'); ?></h1>
    <?php
        echo $button::backSave();
?>
</div>

<ul id="settings-tabs" class="nav nav-tabs nav-tabs-noborder">
    <!-- https://getbootstrap.com/docs/5.0/components/navs-tabs/#using-data-attributes -->
    <li class="<?php echo 'nav-item'.('front-page' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('front-page' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-front-page" style="text-decoration: none"><?php echo $translator->translate('front.page'); ?> </button>
    </li>
    <li class="<?php echo 'nav-item'.('oauth2' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('oauth2' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-oauth2" style="text-decoration: none"><?php echo 'OAuth2'; ?> </button>
    </li>
    <li class="<?php echo 'nav-item'.('general' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('general' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-general" style="text-decoration: none"><?php echo $translator->translate('general'); ?> </button>
    </li>
    <li class="<?php echo 'nav-item'.('invoices' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('invoices' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-invoices" style="text-decoration: none"><?php echo $translator->translate('invoices'); ?></button>
    </li>
    <li class="<?php echo 'nav-item'.('quotes' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('quotes' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-quotes" style="text-decoration: none"><?php echo $translator->translate('quotes'); ?></button>
    </li>
    <li class="<?php echo 'nav-item'.('client-purchase-orders' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('client-purchase-orders' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-client-purchase-orders" style="text-decoration: none"><?php echo $translator->translate('salesorders'); ?></button>
    </li>
    <li class="<?php echo 'nav-item'.('taxes' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('taxes' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-taxes" style="text-decoration: none"><?php echo $translator->translate('taxes'); ?></button>
    </li>
    <li class="<?php echo 'nav-item'.('email' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('email' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-email" style="text-decoration: none"><?php echo $translator->translate('email'); ?></button>
    </li>
    <li class="<?php echo 'nav-item'.('online-payment' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('online-payment' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-online-payment" style="text-decoration: none"><?php echo $translator->translate('online.payment'); ?></button>
    </li>
    <li class="<?php echo 'nav-item'.('projects-tasks' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('projects-tasks' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-projects-tasks" style="text-decoration: none"><?php echo $translator->translate('projects'); ?></button>
    </li>
    <li class="<?php echo 'nav-item'.('google-translate' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('google-translate' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-google-translate" style="text-decoration: none"><?php echo 'Google Translate'; ?></button>
    </li>
    <li class="<?php echo 'nav-item'.('vat-registered' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('vat-registered' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-vat-registered" style="text-decoration: none"><?php echo $translator->translate('vat'); ?></button>
    </li>
    <li class="<?php echo 'nav-item'.('mpdf' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('mpdf' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-mpdf" style="text-decoration: none"><?php echo $translator->translate('mpdf'); ?></button>
    </li>
    <li class="<?php echo 'nav-item'.('peppol' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('peppol' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-peppol" style="text-decoration: none"><?php echo $translator->translate('peppol.electronic.invoicing'); ?></button>
    </li>
    <li class="<?php echo 'nav-item'.('storecove' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('storecove' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-storecove" style="text-decoration: none"><?php echo $translator->translate('storecove'); ?></button>
    </li>
    <li class="<?php echo 'nav-item'.('invoiceplane' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('invoiceplane' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-invoiceplane" style="text-decoration: none"><?php echo $translator->translate('invoiceplane'); ?></button>
    </li>
    <li class="<?php echo 'nav-item'.('qrcode' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('qrcode' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-qrcode" style="text-decoration: none"><?php echo $translator->translate('qr.code'); ?></button>
    </li>
    <li class="<?php echo 'nav-item'.('telegram' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('telegram' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-telegram" style="text-decoration: none"><i class="bi bi-telegram"><?php echo ' '.$translator->translate('telegram'); ?></i></button>
    </li>
    <li class="<?php echo 'nav-item'.('bootstrap5' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('bootstrap5' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-bootstrap5" style="text-decoration: none"><i class="bi bi-bootstrap"><?php echo ' '.$translator->translate('bootstrap5'); ?></i></button>
    </li>
    <li class="<?php echo 'nav-item'.('mtd' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('mtd' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-mtd" style="text-decoration: none"><?php echo $translator->translate('mtd'); ?> </button>
    </li>
    <li class="<?php echo 'nav-item'.('tfa' == $active ? ' active' : ''); ?>" role="presentation">
        <button class="<?php echo 'nav-link'.('tfa' == $active ? ' active' : ''); ?>" data-bs-toggle="tab" data-bs-target="#settings-tfa" style="text-decoration: none"><?php echo $translator->translate('two.factor.authentication'); ?> </button>
    </li>
</ul>

<form method="post" id="form-settings" action="<?php echo $urlGenerator->generate($actionName, $actionArguments); ?>"  enctype="multipart/form-data">

    <input type="hidden" id="_csrf" name="_csrf" value="<?php echo $csrf; ?>">   

    <div class="tabbable tabs-below">

        <div class="tab-content">
            <div id="settings-front-page" class="<?php echo 'tab-pane'.('front-page' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="front-page" >
                <?php echo $frontPage; ?>
            </div>
            
            <div id="settings-oauth2" class="<?php echo 'tab-pane'.('oauth2' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="oauth2" >
                <?php echo $oauth2; ?>
            </div>
            
            <div id="settings-general" class="<?php echo 'tab-pane'.('general' == $active ? ' active' : ''); ?>" >
                <?php echo $general; ?>
            </div>

            <div id="settings-invoices" class="<?php echo 'tab-pane'.('invoices' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-invoices">
                <?php echo $invoices; ?>
            </div>

            <div id="settings-quotes" class="<?php echo 'tab-pane'.('quotes' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-quotes">
                <?php echo $quotes; ?>
            </div>
            
            <div id="settings-client-purchase-orders" class="<?php echo 'tab-pane'.('client-purchase-orders' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-client-purchase-orders">
                <?php echo $salesorders; ?>
            </div>
            
            <div id="settings-taxes" class="<?php echo 'tab-pane'.('taxes' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-taxes">
                <?php echo $taxes; ?>
            </div>

            <div id="settings-email" class="<?php echo 'tab-pane'.('email' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-email">
                <?php echo $email; ?>
            </div>

            <div id="settings-online-payment" class="<?php echo 'tab-pane'.('online-payment' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-online-payment">
                <?php echo $online_payment; ?>
            </div>

            <div id="settings-projects-tasks" class="<?php echo 'tab-pane'.('projects-tasks' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-project-tasks">
                <?php echo $projects_tasks; ?>
            </div>
            
            <div id="settings-google-translate" class="<?php echo 'tab-pane'.('google-translate' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-google-translate">
                <?php echo $google_translate; ?>
            </div>
            
            <div id="settings-vat-registered" class="<?php echo 'tab-pane'.('vat-registered' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-vat-registered">
                <?php echo $vat_registered; ?>
            </div>
            
            <div id="settings-mpdf" class="<?php echo 'tab-pane'.('mpdf' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-mpdf">
                <?php echo $mpdf; ?>
            </div>
            
            <div id="settings-peppol" class="<?php echo 'tab-pane'.('peppol' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-peppol">
                <?php echo $peppol_electronic_invoicing; ?>
            </div>
            
            <div id="settings-storecove" class="<?php echo 'tab-pane'.('storecove' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-storecove">
                <?php echo $storecove; ?>
            </div>
            
            <div id="settings-invoiceplane" class="<?php echo 'tab-pane'.('invoiceplane' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-invoiceplane">
                <?php echo $invoiceplane; ?>
            </div>
            
            <div id="settings-qrcode" class="<?php echo 'tab-pane'.('qrcode' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-qrcode">
                <?php echo $qrcode; ?>
            </div>
            
            <div id="settings-telegram" class="<?php echo 'tab-pane'.('telegram' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-telegram">
                <?php echo $telegram; ?>
            </div>
            
            <div id="settings-bootstrap5" class="<?php echo 'tab-pane'.('bootstrap5' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-bootstrap5">
                <?php echo $bootstrap5; ?>
            </div>
            
            <div id="settings-mtd" class="<?php echo 'tab-pane'.('mtd' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-mtd">
                <?php echo $mtd; ?>
            </div>
            
            <div id="settings-tfa" class="<?php echo 'tab-pane'.('tfa' == $active ? ' active' : ''); ?>" role="tabpanel" aria-labelledby="settings-tfa">
                <?php echo $tfa; ?>
            </div>
            
        </div>

    </div>

</form>


