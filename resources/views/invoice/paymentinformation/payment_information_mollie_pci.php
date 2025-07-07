<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;

/**
 * @see PaymentInformationController function mollieInForm
 * @var App\Invoice\Entity\Client $client_on_invoice
 * @var App\Invoice\Entity\Inv $invoice
 *
 * @see config\common\params 'yiisoft/view' => ['parameters' => ['clientHelper' => Reference::to(ClientHelper::class)]]
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 *
 * @see config\common\params 'yiisoft/view' => ['parameters' => ['dateHelper' => Reference::to(DateHelper::class)]]
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 *
 * @see config\common\params 'yiisoft/view' => ['parameters' => ['numberHelper' => Reference::to(NumberHelper::class)]]
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 *
 * @see config\common\params 'yiisoft/view' => ['parameters' => ['s' => Reference::to(SettingRepository::class)]]
 * @var App\Invoice\Setting\SettingRepository $s
 *
 * @var Mollie\Api\Resources\Payment $payment
 *
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var bool $disable_form
 * @var bool $is_overdue
 * @var float $balance
 * @var float $total
 * @var string $alert
 * @var string $companyLogo
 * @var string $inv_url_key
 * @var string $partial_client_address
 * @var string $invoice_payment_method
 * @var string $title
 */
?>

<?php if ($disable_form === false) { ?>
<div class="container py-5 h-100">
<div class="row d-flex justify-content-center align-items-center h-100">
<div class="col-12 col-md-8 col-lg-6 col-xl-8">
<div class="card border border-dark shadow-2-strong rounded-3">
    <div class="card-header bg-dark text-white">
        <h2 class="fw-normal h3 text-center">
            <?php echo Html::tag('br');
    echo $companyLogo; ?><?= $translator->translate('online.payment.for.invoice'); ?> #
                <?= Html::encode($invoice->getNumber() ?? ''). ' => '.
             Html::encode($invoice->getClient()?->getClient_name() ?? ''). ' '.
             Html::encode($invoice->getClient()?->getClient_surname() ?? ''). ' '.
             $numberHelper->format_currency($balance); ?>
            
        </h2>
        <a href="<?= $urlGenerator->generate('inv/pdf_download_include_cf', ['url_key' => $inv_url_key]); ?>" class="btn btn-sm btn-primary fw-normal h3 text-center" style="text-decoration:none">
            <i class="fa fa-file-pdf-o"></i> <?= $translator->translate('download.pdf').'=>'.$translator->translate('yes').' '.$translator->translate('custom.fields'); ?>
        </a>
        <a href="<?= $urlGenerator->generate('inv/pdf_download_exclude_cf', ['url_key' => $inv_url_key]); ?>" class="btn btn-sm btn-danger fw-normal h3 text-center" style="text-decoration:none">
            <i class="fa fa-file-pdf-o"></i> <?= $translator->translate('download.pdf').'=>'.$translator->translate('no').' '.$translator->translate('custom.fields'); ?>
        </a>
    </div> 
    <br><?= Html::tag('Div', Html::tag('H4', $title)); ?><br>
<div class="card-body p-5 text-center">    
    <?= $alert; ?>
    <?= A::tag()
        ->href('https://www.mollie.com/gb/security')
        // open in a separate window
        ->target('_blank')
        ->addClass('btn btn-lg btn-primary bi bi-info-circle')
        ->content(' '.$translator->translate('read.this.please'))
        ->render();
    ?>        
    <?php
        /**
         * @var string|null $paymentCheckoutUrl
         */
        $paymentCheckoutUrl = $payment->getCheckOutUrl();
    if (!empty($paymentCheckOutUrl)) {
        A::tag()
        ->href($paymentCheckoutUrl)
        ->target('_blank')
        ->addClass('btn btn-lg btn-success fa fa-credit-card fa-margin')
        ->content(' '. $translator->translate('pay.now') . ': ' . $numberHelper->format_currency($balance))
        ->render();
    }
    ?>
    <br>

<?= Html::openTag('div', ['class' => 'card-header']); ?>    
    <?= Html::encode($clientHelper->format_client($client_on_invoice)); ?>
    <?= Html::tag('br'); ?>
    <?= $partial_client_address; ?>
<?= Html::closeTag('div'); ?>    
<br>
<div class="table-responsive">
    <table class="table table-bordered table-condensed no-margin">
    <tbody>
    <tr>
        <td><?= $translator->translate('invoice.date'); ?></td>
        <td class="text-right"><?= Html::encode($invoice->getDate_created()->format('Y-m-d')); ?></td>
    </tr>
    <tr class="<?= ($is_overdue ? 'overdue' : '') ?>">
        <td><?= $translator->translate('due.date'); ?></td>
        <td class="text-right">
            <?= Html::encode($invoice->getDate_due()->format('Y-m-d')); ?>
        </td>
    </tr>
    <tr class="<?php echo($is_overdue ? 'overdue' : '') ?>">
        <td><?= $translator->translate('total'); ?></td>
        <td class="text-right"><?= Html::encode($numberHelper->format_currency($total)); ?></td>
    </tr>
    <tr class="<?= ($is_overdue ? 'overdue' : '') ?>">
        <td><?= $translator->translate('balance'); ?></td>
        <td class="text-right"><?= Html::encode($numberHelper->format_currency($balance)); ?></td>
    </tr>
    <?php if ($invoice_payment_method): ?>
        <tr>
            <td><?= $translator->translate('payment.method') . ': '; ?></td>
            <td class="text-right"><?= $invoice_payment_method; ?></td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
<?php if (!empty($invoice->getTerms())) : ?>
    <div class="col-xs-12 text-muted">
    <?php $paymentTermArray = $s->get_payment_term_array($translator); ?>    
        <br>
        <h4><?= $translator->translate('terms'); ?></h4>
        <div><?= nl2br(Html::encode($paymentTermArray[$invoice->getTerms()] ?? '')); ?></div>
    </div>
<?php endif; ?>
</div>
</div>
</div>
</div>
</div>                  
<?php } ?>
