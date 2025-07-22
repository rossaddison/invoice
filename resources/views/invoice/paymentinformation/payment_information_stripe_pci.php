<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/*
 * @see PaymentInformationController function stripeInForm
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
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var bool $disable_form
 * @var bool $is_overdue
 * @var float $balance
 * @var float $total
 * @var string $alert
 * @var string $client_secret
 * @var string $companyLogo
 * @var string $json_encoded_items
 * @var string $inv_url_key
 * @var string $partial_client_address
 * @var string $payment_method
 * @var string $pci_client_publishable_key
 * @var string $title
 */
?>

<?php if (false === $disable_form) { ?>
<div class="container py-5 h-100">
<div class="row d-flex justify-content-center align-items-center h-100">
<div class="col-12 col-md-8 col-lg-6 col-xl-8">
<div class="card border border-dark shadow-2-strong rounded-3">
    <div class="card-header bg-dark text-white">
        <h2 class="fw-normal h3 text-center">
            <div class="row gy-4">
                <div class="col-4">
                    <?php echo Html::tag('br'); ?>
                    <?php echo $companyLogo; ?>
                </div>
                <div class="col-8">
                    <?php echo $translator->translate('online.payment.for.invoice'); ?> #
                    <?php echo Html::encode($invoice->getNumber() ?? '').' => '.
                     Html::encode($invoice->getClient()?->getClient_name() ?? '').' '.
                     Html::encode($invoice->getClient()?->getClient_surname() ?? '').' '.
                     $numberHelper->format_currency($balance); ?>
                </div>    
            </div>     
        </h2>
        <a href="<?php echo $urlGenerator->generate('inv/pdf_download_include_cf', ['url_key' => $inv_url_key]); ?>" class="btn btn-sm btn-primary fw-normal h3 text-center" style="text-decoration:none">
            <i class="fa fa-file-pdf-o"></i> <?php echo $translator->translate('download.pdf').'=>'.$translator->translate('yes').' '.$translator->translate('custom.fields'); ?>
        </a>
        <a href="<?php echo $urlGenerator->generate('inv/pdf_download_exclude_cf', ['url_key' => $inv_url_key]); ?>" class="btn btn-sm btn-danger fw-normal h3 text-center" style="text-decoration:none">
            <i class="fa fa-file-pdf-o"></i> <?php echo $translator->translate('download.pdf').'=>'.$translator->translate('no').' '.$translator->translate('custom.fields'); ?>
        </a>
    </div> 
    <br><?php echo Html::tag('Div', Html::tag('H4', $title)); ?><br>
<div class="card-body p-5 text-center">    
    <?php echo Html::openTag('form', ['method' => 'post', 'enctype' => 'multipart/form-data', 'id' => 'payment-form']); ?>                   
    
    <?php echo $alert; ?>
    <?php echo // Stripe injects the payment element here
       Html::tag('Div', '', ['id' => 'payment-element']);
    ?>
    <?php echo // Stripe payment message
       Html::tag('Div', '', ['id' => 'payment-message', 'class' => 'hidden']);
    ?>
    <button type="submit" id="submit" class="btn btn-lg btn-success fa fa-credit-card fa-margin">
        <div class="spinner hidden" id="spinner"></div>
        <span id="button-text">
            <?php echo ' '.$translator->translate('pay.now').': '.$numberHelper->format_currency($balance); ?>
        </span>
    </button>
<?php echo Html::encode($clientHelper->format_client($client_on_invoice)); ?>
<?php echo $partial_client_address; ?>
<br>
<div class="table-responsive">
    <table class="table table-bordered table-condensed no-margin">
    <tbody>
    <tr>
        <td><?php echo $translator->translate('date'); ?></td>
        <td class="text-right"><?php echo Html::encode($invoice->getDate_created()->format('Y-m-d')); ?></td>
    </tr>
    <tr class="<?php echo $is_overdue ? 'overdue' : ''; ?>">
        <td><?php echo $translator->translate('due.date'); ?></td>
        <td class="text-right">
            <?php echo Html::encode($invoice->getDate_due()->format('Y-m-d')); ?>
        </td>
    </tr>
    <tr class="<?php echo $is_overdue ? 'overdue' : ''; ?>">
        <td><?php echo $translator->translate('total'); ?></td>
        <td class="text-right"><?php echo Html::encode($numberHelper->format_currency($total)); ?></td>
    </tr>
    <tr class="<?php echo $is_overdue ? 'overdue' : ''; ?>">
        <td><?php echo $translator->translate('balance'); ?></td>
        <td class="text-right"><?php echo Html::encode($numberHelper->format_currency($balance)); ?></td>
    </tr>
    <?php if ($payment_method) { ?>
        <tr>
            <td><?php echo $translator->translate('payment.method').': '; ?></td>
            <td class="text-right"><?php echo $payment_method; ?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
</div>
<?php if (!empty($invoice->getTerms())) { ?>
    <div class="col-xs-12 text-muted">
        <br>
        <h4><?php echo $translator->translate('terms'); ?></h4>
        <?php $paymentTermArray = $s->get_payment_term_array($translator); ?>
        <div><?php echo nl2br(Html::encode($paymentTermArray[$invoice->getTerms()] ?? '')); ?></div>
    </div>
<?php } ?>
<?php echo Html::closeTag('form'); ?>
</div>
</div>
</div>
</div>
</div>                  
<?php }
// https://stripe.com/docs/payments/quickstart
?>
<?php // This is your test publishable API key.
$js18 = 'const stripe = Stripe("'.$pci_client_publishable_key.'");'
.'let elements;'
.'const items = ['.$json_encoded_items.'];'
.'initialize();'
.'checkStatus();'
.'document.querySelector("#payment-form").addEventListener("submit", handleSubmit);'
.'async function initialize() {'
    // To avoid Error 422 Unprocessible entity
    // const { clientSecret } = await fetch("/create.php", {
    // method: "POST",
    // headers: { "Content-Type": "application/json" },
    // body: JSON.stringify({ items }),
    // }).then((r) => r.json());
    .'const { clientSecret } = {"clientSecret": "'.$client_secret.'"};'
    .'elements = stripe.elements({ clientSecret });'
    .'const paymentElementOptions = {'
        .'layout: "tabs"'
    .'};'
    .'const paymentElement = elements.create("payment", paymentElementOptions);'
    .'paymentElement.mount("#payment-element");'
.'}'
.'async function handleSubmit(e) {'
    .'e.preventDefault();'
    .'setLoading(true);'
    .'const { error } = await stripe.confirmPayment({'
        .'elements,'
        .'confirmParams: {'
            .'return_url: "'.$urlGenerator->generateAbsolute('paymentinformation/stripe_complete', ['url_key' => $inv_url_key]).'"'
        .'},'
    .'});'
    .'if (error.type === "card_error" || error.type === "validation_error") {'
        .'showMessage(error.message);'
    .'} else {'
        .'showMessage("An unexpected error occurred.");'
    .'}'
    .'setLoading(false);'
.'}'
.'async function checkStatus() {'
.'const clientSecret = new URLSearchParams(window.location.search).get("payment_intent_client_secret");'
.'if (!clientSecret) {'
    .'return;'
.'}'
.'const { paymentIntent } = await stripe.retrievePaymentIntent(clientSecret);'
.'switch (paymentIntent.status) {'
    .'  case "succeeded":'
    .'    showMessage("Payment succeeded!");'
    .'    break;'
    .'  case "processing":'
    .'    showMessage("Your payment is processing.");'
    .'    break;'
    .'  case "requires_payment_method":'
    .'    showMessage("Your payment was not successful, please try again.");'
    .'    break;'
    .'  default:'
    .'    showMessage("Something went wrong.");'
    .'    break;'
.'}'
.'}'
.'function showMessage(messageText) {'
.'const messageContainer = document.querySelector("#payment-message");'
.'messageContainer.classList.remove("hidden");'
.'messageContainer.textContent = messageText;'
.'setTimeout(function () {'
.'messageContainer.classList.add("hidden");'
.'messageText.textContent = "";'
.'}, 4000);'
.'}'
.'function setLoading(isLoading) {'
.'if (isLoading) {'
.'document.querySelector("#submit").disabled = true;'
.'document.querySelector("#spinner").classList.remove("hidden");'
.'document.querySelector("#button-text").classList.add("hidden");'
.'} else {'
.'document.querySelector("#submit").disabled = false;'
.'document.querySelector("#spinner").classList.add("hidden");'
.'document.querySelector("#button-text").classList.remove("hidden");'
.'}'
.'};';
echo Html::script($js18)->type('module');
?>



