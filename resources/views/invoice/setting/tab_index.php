<?php
declare(strict_types=1);


use Yiisoft\Html\Html;

 /**
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 */

echo $alert;
?>

<div id="headerbar">
    <h1 class="headerbar-title"><?= $translator->translate('i.settings'); ?></h1>
    <?php
        echo $button::back_save();
    ?>
</div>

<ul id="settings-tabs" class="nav nav-tabs nav-tabs-noborder">
    <li class="active">
        <a data-toggle="tab" href="#settings-general" style="text-decoration: none"><?= $translator->translate('i.general'); ?> </a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-invoices" style="text-decoration: none"><?= $translator->translate('i.invoices'); ?></a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-quotes" style="text-decoration: none"><?= $translator->translate('i.quotes'); ?></a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-client-purchase-orders" style="text-decoration: none"><?= $translator->translate('invoice.salesorders'); ?></a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-taxes" style="text-decoration: none"><?= $translator->translate('i.taxes'); ?></a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-email" style="text-decoration: none"><?= $translator->translate('i.email'); ?></a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-online-payment" style="text-decoration: none"><?= $translator->translate('g.online_payment'); ?></a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-projects-tasks" style="text-decoration: none"><?= $translator->translate('i.projects'); ?></a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-google-translate" style="text-decoration: none"><?= 'Google Translate' ?></a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-vat-registered" style="text-decoration: none"><?= $translator->translate('invoice.invoice.vat'); ?></a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-mpdf" style="text-decoration: none"><?= $translator->translate('invoice.invoice.mpdf'); ?></a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-peppol" style="text-decoration: none"><?= $translator->translate('invoice.invoice.peppol'); ?></a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-storecove" style="text-decoration: none"><?= $translator->translate('invoice.invoice.storecove'); ?></a>
    </li>
    <li>
        <a data-toggle="tab" href="#settings-invoiceplane" style="text-decoration: none"><?= $translator->translate('invoice.invoice.invoiceplane'); ?></a>
    </li>
</ul>

<form method="post" id="form-settings" action="<?= $urlGenerator->generate(...$action) ?>"  enctype="multipart/form-data">

    <input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>">   

    <div class="tabbable tabs-below">

        <div class="tab-content">

            <div id="settings-general" class="tab-pane active">
                <?= $general; ?>
            </div>

            <div id="settings-invoices" class="tab-pane">
                <?= $invoices; ?>
            </div>

            <div id="settings-quotes" class="tab-pane">
                <?= $quotes; ?>
            </div>
            
            <div id="settings-client-purchase-orders" class="tab-pane">
                <?= $salesorders; ?>
            </div>

            <div id="settings-taxes" class="tab-pane">
                <?= $taxes; ?>
            </div>

            <div id="settings-email" class="tab-pane">
                <?= $email; ?>
            </div>

            <div id="settings-online-payment" class="tab-pane">
                <?= $online_payment; ?>
            </div>

            <div id="settings-projects-tasks" class="tab-pane">
                <?= $projects_tasks; ?>
            </div>
            
            <div id="settings-google-translate" class="tab-pane">
                <?= $google_translate; ?>
            </div>
            
            <div id="settings-vat-registered" class="tab-pane">
                <?= $vat_registered; ?>
            </div>
            
            <div id="settings-mpdf" class="tab-pane">
                <?= $mpdf; ?>
            </div>
            
            <div id="settings-peppol" class="tab-pane">
                <?= $peppol_electronic_invoicing; ?>
            </div>
            
            <div id="settings-storecove" class="tab-pane">
                <?= $storecove; ?>
            </div>
            
            <div id="settings-invoiceplane" class="tab-pane">
                <?= $invoiceplane; ?>
            </div>

        </div>

    </div>

</form>


