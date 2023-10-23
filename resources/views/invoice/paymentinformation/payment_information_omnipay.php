<?php

declare(strict_types=1);

use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Helpers\ClientHelper;

use Yiisoft\Form\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $action
 */

$numberhelper = new NumberHelper($s);
$clienthelper = new ClientHelper($s);
?>
<?php if ($disable_form === false) { ?>
<div class="container py-5 h-100">
<div class="row d-flex justify-content-center align-items-center h-100">
<div class="col-12 col-md-8 col-lg-6 col-xl-8">
<div class="card border border-dark shadow-2-strong rounded-3">
    <div class="card-header bg-dark text-white">
        <h2 class="fw-normal h3 text-center"><?= $s->trans('online_payment_for_invoice'); ?> #
                                             <?= $invoice->getNumber(). ' => '.
                                                 $invoice->getClient()->getClient_name() . ' '.
                                                 $invoice->getClient()->getClient_surname() . ' '.
                                                 $numberhelper->format_currency($balance); ?>
        </h2>
        <a href="<?= $urlGenerator->generate('inv/pdf_download_include_cf', ['url_key' => $inv_url_key]); ?>" class="btn btn-sm btn-primary fw-normal h3 text-center" style="text-decoration:none">
            <i class="fa fa-file-pdf-o"></i> <?= $s->trans('download_pdf').'=>'.$s->trans('yes').' '.$s->trans('custom_fields'); ?>
        </a>
        <a href="<?= $urlGenerator->generate('inv/pdf_download_exclude_cf', ['url_key' => $inv_url_key]); ?>" class="btn btn-sm btn-danger fw-normal h3 text-center" style="text-decoration:none">
            <i class="fa fa-file-pdf-o"></i> <?= $s->trans('download_pdf').'=>'.$s->trans('no').' '.$s->trans('custom_fields'); ?>
        </a>
    </div>    
    <?= Html::tag('Div',Html::tag('H4', $title)); ?>
<div class="card-body p-5 text-center">    
    <?=                    
    Form::tag()
    // $action => 'action' => ['paymentinformation/make_payment', ['url_key' => $url_key]],
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('PaymentInformationForm')
    ->open();
    ?>
    <?= $alert; ?>
    <?= Html::input('hidden','invoice_url_key', Html::encode($inv_url_key)); ?>
    <?= Html::label($s->trans('online_payment_method'),'gateway-select'); ?>
    <?= Field::text($form, 'gateway_driver')
    ->addInputAttributes(['class'=>'input-sm form-control'])
    ->addInputAttributes(['value'=>$body['gateway_driver'] ?? $client_chosen_gateway ])
    ->addInputAttributes(['readonly'=>true])
    ->hideLabel()
    ?>
    <?= $s->trans('creditcard_details'); ?>
    <?= $s->trans('online_payment_creditcard_hint'); ?>
    <?= $s->trans('creditcard_number'); ?>
    <?= Field::text($form, 'creditcard_number')
    ->addInputAttributes(['class'=>'input-sm form-control'])
    ->addInputAttributes(['value'=>$body['creditcard_number'] ?? '4242424242424242' ])
    ->hideLabel()
    ?>
    <?= $s->trans('creditcard_expiry_month'); ?>
    <?= Field::text($form, 'creditcard_expiry_month')
    ->addInputAttributes(['class'=>'input-sm form-control'])  
    ->addInputAttributes(['min'=>'1','max'=>'12'])    
    ->addInputAttributes(['value'=>$body['creditcard_expiry_month'] ?? '06' ])
    ->hideLabel()
    ?>
    <?= $s->trans('creditcard_expiry_year'); ?>
    <?= Field::text($form, 'creditcard_expiry_year')
    ->addInputAttributes(['class'=>'input-sm form-control'])  
    ->addInputAttributes(['min'=>date('Y'),'max'=>date('Y') + 20])    
    ->addInputAttributes(['value'=>$body['creditcard_expiry_year'] ?? '2030' ])
    ->hideLabel()
    ?>
    <?= $s->trans('creditcard_cvv'); ?>
    <?= Field::text($form, 'creditcard_cvv')
    ->addInputAttributes(['class'=>'input-sm form-control'])  
    ->addInputAttributes(['type'=>'number']) 
    ->addInputAttributes(['value'=>$body['creditcard_cvv'] ?? '567' ])
    ->hideLabel()
    ?>
    <?= Field::buttonGroup()
    ->addContainerClass('btn-group btn-toolbar float-end')
    ->buttonsData([
    [
        ' '.$s->trans('pay_now') . ': ' . $numberhelper->format_currency($balance),
        'type' => 'submit',
        'class' => 'btn btn-lg btn-success fa fa-credit-card fa-margin',
        'name' => 'btn_send'
    ],
    ]) ?>
<?php            
    if ($logo) {
        echo $logo;
    }
?>    
<?= Html::encode($clienthelper->format_client($client_on_invoice)) ?>
<?= $partial_client_address; ?>
<br>
<br>
<br>
<div class="table-responsive">
    <table class="table table-bordered table-condensed no-margin">
    <tbody>
    <tr>
        <td><?= $s->trans('invoice_date'); ?></td>
        <td class="text-right"><?= Html::encode($invoice->getDate_created()->format($datehelper->style())); ?></td>
    </tr>
    <tr class="<?= ($is_overdue ? 'overdue' : '') ?>">
        <td><?= $s->trans('due_date'); ?></td>
        <td class="text-right">
            <?= Html::encode($invoice->getDate_due()->format($datehelper->style())); ?>
        </td>
    </tr>
    <tr class="<?php echo($is_overdue ? 'overdue' : '') ?>">
        <td><?= $s->trans('total'); ?></td>
        <td class="text-right"><?= Html::encode($numberhelper->format_currency($total)); ?></td>
    </tr>
    <tr class="<?= ($is_overdue ? 'overdue' : '') ?>">
        <td><?= $s->trans('balance'); ?></td>
        <td class="text-right"><?= Html::encode($numberhelper->format_currency($balance)); ?></td>
    </tr>
    <?php if ($payment_method): ?>
        <tr>
            <td><?= $s->trans('payment_method') . ': '; ?></td>
            <td class="text-right"><?= Html::encode($payment_method); ?></td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
<?php if (!empty($invoice->getTerms())) : ?>
    <div class="col-xs-12 text-muted">
        <br>
        <h4><?= $s->trans('terms'); ?></h4>
        <div><?= nl2br(Html::encode($invoice->getTerms)); ?></div>
    </div>
<?php endif; ?>
<?= Form::tag()->close(); ?>
</div>
</div>
</div>
</div>
</div>                  
<?php } ?>


