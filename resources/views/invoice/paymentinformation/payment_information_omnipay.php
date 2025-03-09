<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 *
 * @var App\Invoice\Entity\Client $client_on_invoice
 * @var App\Invoice\Entity\Inv $invoice
 * @var App\Invoice\PaymentInformation\PaymentInformationForm $form
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
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var bool $disable_form
 * @var bool $is_overdue
 * @var float $balance
 * @var float $total
 * @var string $actionName
 * @var string $alert
 * @var string $csrf
 * @var string $client_chosen_gateway
 * @var string $companyLogo
 * @var string $inv_url_key
 * @var string $partial_client_address
 * @var string $payment_method
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 *
 */
?>
<?php if ($disable_form === false) { ?>
<div class="container py-5 h-100">
<div class="row d-flex justify-content-center align-items-center h-100">
<div class="col-12 col-md-8 col-lg-6 col-xl-8">
<div class="card border border-dark shadow-2-strong rounded-3">
    <div class="card-header bg-dark text-white">
        <h2 class="fw-normal h3 text-center"><?= $translator->translate('g.online_payment_for_invoice'); ?> #
            <?php echo Html::tag('br');
    echo $companyLogo; ?><?= $translator->translate('g.online_payment_for_invoice'); ?> #
            <?= ($invoice->getNumber() ?? ''). ' => '.
        ($invoice->getClient()?->getClient_name() ?? ''). ' '.
        ($invoice->getClient()?->getClient_surname() ?? ''). ' '.
         $numberHelper->format_currency($balance); ?>
        </h2>
        <a href="<?= $urlGenerator->generate('inv/pdf_download_include_cf', ['url_key' => $inv_url_key]); ?>" class="btn btn-sm btn-primary fw-normal h3 text-center" style="text-decoration:none">
            <i class="fa fa-file-pdf-o"></i> <?= $translator->translate('i.download_pdf').'=>'.$translator->translate('i.yes').' '.$translator->translate('i.custom_fields'); ?>
        </a>
        <a href="<?= $urlGenerator->generate('inv/pdf_download_exclude_cf', ['url_key' => $inv_url_key]); ?>" class="btn btn-sm btn-danger fw-normal h3 text-center" style="text-decoration:none">
            <i class="fa fa-file-pdf-o"></i> <?= $translator->translate('i.download_pdf').'=>'.$translator->translate('i.no').' '.$translator->translate('i.custom_fields'); ?>
        </a>
    </div>    
    <?= Html::tag('Div', Html::tag('H4', $title)); ?>
<div class="card-body p-5 text-center">    
    <?=
    Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('PaymentInformationForm')
    ->open();
    ?>
    <?= $alert; ?>
    <?= Html::input('hidden', 'invoice_url_key', Html::encode($inv_url_key)); ?>
    <?= Html::label($translator->translate('g.online_payment_method'), 'gateway-select'); ?>
    <?= Field::text($form, 'gateway_driver')
        ->addInputAttributes(['class' => 'input-sm form-control'])
        ->addInputAttributes(['value' => $body['gateway_driver'] ?? $client_chosen_gateway ])
        ->addInputAttributes(['readonly' => true])
        ->hideLabel()
    ?>
    <?= $translator->translate('g.creditcard_details'); ?>
    <?= $translator->translate('g.online_payment_creditcard_hint'); ?>
    <?= $translator->translate('g.creditcard_number'); ?>
    <?= Field::text($form, 'creditcard_number')
    ->addInputAttributes(['class' => 'input-sm form-control'])
    ->addInputAttributes(['value' => $body['creditcard_number'] ?? '4242424242424242' ])
    ->hideLabel()
    ?>
    <?= $translator->translate('g.creditcard_expiry_month'); ?>
    <?= Field::text($form, 'creditcard_expiry_month')
    ->addInputAttributes(['class' => 'input-sm form-control'])
    ->addInputAttributes(['min' => '1','max' => '12'])
    ->addInputAttributes(['value' => $body['creditcard_expiry_month'] ?? '06' ])
    ->hideLabel()
    ?>
    <?= $translator->translate('g.creditcard_expiry_year'); ?>
    <?= Field::text($form, 'creditcard_expiry_year')
    ->addInputAttributes(['class' => 'input-sm form-control'])
    ->addInputAttributes(['min' => date('Y'),'max' => (int)date('Y') + 20])
    ->addInputAttributes(['value' => $body['creditcard_expiry_year'] ?? '2030' ])
    ->hideLabel()
    ?>
    <?= $translator->translate('g.creditcard_cvv'); ?>
    <?= Field::text($form, 'creditcard_cvv')
    ->addInputAttributes(['class' => 'input-sm form-control'])
    ->addInputAttributes(['type' => 'number'])
    ->addInputAttributes(['value' => $body['creditcard_cvv'] ?? '567' ])
    ->hideLabel()
    ?>
    <?= Field::buttonGroup()
    ->addContainerClass('btn-group btn-toolbar float-end')
    ->buttonsData([
    [
        ' '.$translator->translate('i.pay_now') . ': ' . $numberHelper->format_currency($balance),
        'type' => 'submit',
        'class' => 'btn btn-lg btn-success fa fa-credit-card fa-margin',
        'name' => 'btn_send'
    ],
    ]) ?>
<?= Html::encode($clientHelper->format_client($client_on_invoice)) ?>
<?= $partial_client_address; ?>
<br>
<br>
<br>
<div class="table-responsive">
    <table class="table table-bordered table-condensed no-margin">
    <tbody>
    <tr>
        <td><?= $translator->translate('i.invoice_date'); ?></td>
        <td class="text-right"><?= Html::encode($invoice->getDate_created()->format('Y-m-d')); ?></td>
    </tr>
    <tr class="<?= ($is_overdue ? 'overdue' : '') ?>">
        <td><?= $translator->translate('i.due_date'); ?></td>
        <td class="text-right">
            <?= Html::encode($invoice->getDate_due()->format('Y-m-d')); ?>
        </td>
    </tr>
    <tr class="<?php echo($is_overdue ? 'overdue' : '') ?>">
        <td><?= $translator->translate('i.total'); ?></td>
        <td class="text-right"><?= Html::encode($numberHelper->format_currency($total)); ?></td>
    </tr>
    <tr class="<?= ($is_overdue ? 'overdue' : '') ?>">
        <td><?= $translator->translate('i.balance'); ?></td>
        <td class="text-right"><?= Html::encode($numberHelper->format_currency($balance)); ?></td>
    </tr>
    <?php if ($payment_method): ?>
        <tr>
            <td><?= $translator->translate('i.payment_method') . ': '; ?></td>
            <td class="text-right"><?= Html::encode($payment_method); ?></td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
<?php if (!empty($invoice->getTerms())) : ?>
    <div class="col-xs-12 text-muted">
        <br>
        <h4><?= $translator->translate('i.terms'); ?></h4>
        <div><?= nl2br(Html::encode($invoice->getTerms())); ?></div>
    </div>
<?php endif; ?>
<?= Form::tag()->close(); ?>
</div>
</div>
</div>
</div>
</div>                  
<?php } ?>


