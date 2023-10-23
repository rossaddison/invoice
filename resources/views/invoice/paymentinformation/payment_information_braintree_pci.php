<?php
declare(strict_types=1);

use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Helpers\ClientHelper;

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
    <br><?= Html::tag('Div',Html::tag('H4', $title,['data-toggle'=>'tooltip','title'=>'Test card: 4111 1111 1111 1111 Expiry-date: 06/34'])); ?><br>
<div class="card-body p-5 text-center">    
    <?=                    
    Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('payment-form')
    ->open();
    ?>
    <?= $alert; ?>
    <div id="dropin-container"></div>
    <div id="dropin-container"></div>
    <input type="submit" />
    <input type="hidden" id="nonce" name="payment_method_nonce"/>
<?php            
    if ($logo) {
        echo $logo;
    }
?> 
<br>    
<?= Html::encode($clienthelper->format_client($client_on_invoice)) ?>
<?= $partial_client_address; ?>
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
            <td class="text-right"><?= $payment_method; ?></td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
<?php if (!empty($invoice->getTerms())) : ?>
    <div class="col-xs-12 text-muted">
        <br>
        <h4><?= $s->trans('terms'); ?></h4>
        <div><?= nl2br(Html::encode($invoice->getTerms())); ?></div>
    </div>
<?php endif; ?>
<?= Form::tag()->close(); ?>
</div>
</div>
</div>
</div>
</div>                  
<?php } 
?>
<?php 
    $js22 = 'const form = document.getElementById("payment-form");'
            . 'braintree.dropin.create('
            . '{'
            .       'authorization: "' .$client_token. '",'
            .       'container: "#dropin-container"'
            . '}, '
            . '(error, dropinInstance) => {'
            .  '    if (error) console.error(error);'
            .  '    form.addEventListener("submit", event => {'
            .  '       event.preventDefault();' 
            .  '       dropinInstance.requestPaymentMethod((error, payload) => {'
            .  '          if (error) console.error(error);'
            .  '          document.getElementById("nonce").value = payload.nonce;'
            .  '          form.submit();'
            .  '       });'
            .  '    });'
            .  '}'
            .  ');';          
    echo Html::script($js22)->type('module')->charset('utf-8');
?>

