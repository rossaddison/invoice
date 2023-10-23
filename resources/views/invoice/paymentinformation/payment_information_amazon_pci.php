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

// see https://developer.amazon.com/docs/amazon-pay-checkout/add-the-amazon-pay-button.html#4-render-the-button

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
    <?php
        include 'vendor/autoload.php';
        $version = "using https://github.com/amzn/amazon-pay-api-sdk-php version: " . \Amazon\Pay\API\Client::SDK_VERSION . "\n";
    ?>
    <br><?= Html::tag('Div',Html::tag('H4', $title.'  '. $version)); ?><br>
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
    <br>
    <?=
       // Amazon pay button
       // https://developer.amazon.com/docs/amazon-pay-checkout/add-the-amazon-pay-button.html#4-render-the-button
       Html::tag('Div','',['id'=>'AmazonPayButton']); 
    ?>
    <br>
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
<?php } ?>
<?php 
    $js20 =
    "const amazonPayButton = amazon.Pay.renderButton('#AmazonPayButton', {"         
    // set checkout environment
    . 'merchantId: "'. $amazonPayButton['merchantId']. '",'
    // SANDBOX-xxxxxxxxxx
    . 'publicKeyId: "'.$amazonPayButton['publicKeyId'].'",'
    // eg. Currency shortcode eg. GBP        
    . 'ledgerCurrency: "'.$amazonPayButton['ledgerCurrency'].'",'           
    // customize the buyer experience eg. en_GB
    . 'checkoutLanguage: "'.$amazonPayButton['checkoutLanguage']. '",'
    // 'PayAndShip' - Offer checkout using buyer's Amazon wallet and address book. 
    //              Select this product type if you need the buyer's shipping details
    // 'PayOnly' - Offer checkout using only the buyer's Amazon wallet. 
    //              Select this product type if you do not need the buyer's shipping details
    // 'SignIn' - Offer Amazon Sign-in. Select this product type if you need buyer details 
    //              before the buyer starts Amazon Pay checkout. See Amazon Sign-in 
    //              for more information.       
    . 'productType: "'.$amazonPayButton['productType'].'",'
    //'Home' - Initial or main page
    //'Product' - Product details page
    //'Cart' - Cart review page before buyer starts checkout
    //'Checkout' - Any page after buyer starts checkout
    //'Other' - Any page that doesn't fit the previous descriptions        
    . 'placement: "Other",'
    . 'buttonColor: "Gold",'
    // Currency shortcode eg. GBP
    . 'estimatedOrderAmount: { "amount": "'.$amazonPayButton['amount'].'", "currencyCode": "'.$amazonPayButton['ledgerCurrency'].'"},'
    // configure Create Checkout Session request
    . 'createCheckoutSessionConfig: {'
    // json encoded string generated in step 2
    . "           payloadJSON: '". $amazonPayButton['payloadJSON']."'," 
    // signature generated in step 3
    . "signature: '". $amazonPayButton['signature']."'"  
    . '}'    
    . '});';               
    echo Html::script($js20)->type('module')
                            ->charset('utf-8');
?>




