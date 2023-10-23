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
    <br><?= Html::tag('Div',Html::tag('H4', $title)); ?><br>
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
    <?=
       // Stripe injects the payment element here
       Html::tag('Div','',['id'=>'payment-element']); 
    ?>
    <?=
       // Stripe payment message
       Html::tag('Div','',['id'=>'payment-message', 'class'=>'hidden']); 
    ?>
    <button type="submit" id="submit" class="btn btn-lg btn-success fa fa-credit-card fa-margin">
        <div class="spinner hidden" id="spinner"></div>
        <span id="button-text">
            <?= ' '.$s->trans('pay_now') . ': ' . $numberhelper->format_currency($balance) ?>
        </span>
    </button>
<?php            
    if ($logo) {
        echo $logo;
    }
?>    
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
// https://stripe.com/docs/payments/quickstart
?>
<?php // This is your test publishable API key.
    $js18 = 
    'const stripe = Stripe("' .$pci_client_publishable_key.'");' 
    . 'let elements;'
    . 'const items = ['. $json_encoded_items.'];'
    . 'initialize();'
    . 'checkStatus();'
    . 'document.querySelector("#payment-form").addEventListener("submit", handleSubmit);'
    . 'async function initialize() {'
        // To avoid Error 422 Unprocessible entity 
        // const { clientSecret } = await fetch("/create.php", {
        // method: "POST",
        // headers: { "Content-Type": "application/json" },
        // body: JSON.stringify({ items }),
        // }).then((r) => r.json());    
        . 'const { clientSecret } = {"clientSecret": "'. $client_secret .'"};'
        . 'elements = stripe.elements({ clientSecret });'
        . 'const paymentElementOptions = {'
            . 'layout: "tabs"'
        . '};'
        . 'const paymentElement = elements.create("payment", paymentElementOptions);'
        . 'paymentElement.mount("#payment-element");'
    . '}'
    . 'async function handleSubmit(e) {'
        . 'e.preventDefault();'
        . 'setLoading(true);'
        . 'const { error } = await stripe.confirmPayment({'
            . 'elements,'
            . 'confirmParams: {'
                . 'return_url: "'.$urlGenerator->generateAbsolute('paymentinformation/stripe_complete',['url_key'=>$inv_url_key]).'"'
            . '},'
        . '});'
        . 'if (error.type === "card_error" || error.type === "validation_error") {'
            . 'showMessage(error.message);'
        . '} else {'
            . 'showMessage("An unexpected error occurred.");'
        . '}'
        . 'setLoading(false);'
    . '}' 
    . 'async function checkStatus() {'
    .   'const clientSecret = new URLSearchParams(window.location.search).get("payment_intent_client_secret");'
    .   'if (!clientSecret) {'
        .   'return;'
    .   '}'
    .   'const { paymentIntent } = await stripe.retrievePaymentIntent(clientSecret);'
    .   'switch (paymentIntent.status) {'
        .   '  case "succeeded":'
        .   '    showMessage("Payment succeeded!");'
        .   '    break;'
        .   '  case "processing":'
        .   '    showMessage("Your payment is processing.");'
        .   '    break;'
        .   '  case "requires_payment_method":'
        .   '    showMessage("Your payment was not successful, please try again.");'
        .   '    break;'
        .   '  default:'
        .   '    showMessage("Something went wrong.");'
        .   '    break;'
    .  '}'
.    '}'
.   'function showMessage(messageText) {'
.     'const messageContainer = document.querySelector("#payment-message");'
.     'messageContainer.classList.remove("hidden");'
.     'messageContainer.textContent = messageText;'
.     'setTimeout(function () {'
.       'messageContainer.classList.add("hidden");'
.       'messageText.textContent = "";'
.     '}, 4000);'
.   '}' 
.   'function setLoading(isLoading) {'
.     'if (isLoading) {'
.       'document.querySelector("#submit").disabled = true;'
.       'document.querySelector("#spinner").classList.remove("hidden");'
.       'document.querySelector("#button-text").classList.add("hidden");'
.     '} else {'
.       'document.querySelector("#submit").disabled = false;'
.       'document.querySelector("#spinner").classList.add("hidden");'
.       'document.querySelector("#button-text").classList.remove("hidden");'
.     '}'
.   '};';          
echo Html::script($js18)->type('module');
?>



