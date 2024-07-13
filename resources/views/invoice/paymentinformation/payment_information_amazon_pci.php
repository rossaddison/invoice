<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @link https://developer.amazon.com/docs/amazon-pay-checkout/add-the-amazon-pay-button.html#4-render-the-button
 * @see PaymentInformationController function amazonInForm
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
 * @var array $amazonPayButton
 * @var bool $disable_form
 * @var bool $is_overdue
 * @var float $balance
 * @var float $total
 * @var string $alert
 * @var string $companyLogo
 * @var string $inv_url_key
 * @var string $partial_client_address
 * @var string $payment_method
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
            <div class="row gy-4">
                <div class="col-4">
                    <?= Html::tag('br'); ?>
                    <?= $companyLogo; ?>
                </div>    
                <div class="col-8">
                    <?= $translator->translate('g.online_payment_for_invoice'); ?> #                    
                    <?= ($invoice->getNumber() ?? ''). ' => '.
                     ($invoice->getClient()?->getClient_name() ?? '' ). ' '.
                     ($invoice->getClient()?->getClient_surname() ?? '' ). ' '.
                     $numberHelper->format_currency($balance); ?>
                </div>
            </div>
        </h2>
        <a href="<?= $urlGenerator->generate('inv/pdf_download_include_cf', ['url_key' => $inv_url_key]); ?>" class="btn btn-sm btn-primary fw-normal h3 text-center" style="text-decoration:none">
            <i class="fa fa-file-pdf-o"></i> <?= $translator->translate('i.download_pdf').'=>'.$translator->translate('i.yes').' '.$translator->translate('i.custom_fields'); ?>
        </a>
        <a href="<?= $urlGenerator->generate('inv/pdf_download_exclude_cf', ['url_key' => $inv_url_key]); ?>" class="btn btn-sm btn-danger fw-normal h3 text-center" style="text-decoration:none">
            <i class="fa fa-file-pdf-o"></i> <?= $translator->translate('i.download_pdf').'=>'.$translator->translate('i.no').' '.$translator->translate('i.custom_fields'); ?>
        </a>
    </div>
    <?php
        include 'vendor/autoload.php';
        $version = "using https://github.com/amzn/amazon-pay-api-sdk-php version: " . \Amazon\Pay\API\Client::SDK_VERSION . "\n";
    ?>
    <br><?= Html::tag('Div',Html::tag('H4', $title.'  '. $version)); ?><br>
<div class="card-body p-5 text-center">    
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
            <?= ' '.$translator->translate('i.pay_now') . ': ' . $numberHelper->format_currency($balance) ?>
        </span>
    </button>
<?= Html::encode($clientHelper->format_client($client_on_invoice)) ?>
<?= $partial_client_address; ?>
<br>
<div class="table-responsive">
    <table class="table table-bordered table-condensed no-margin">
    <tbody>
    <tr>
        <td><?= $translator->translate('i.invoice_date'); ?></td>
        <td class="text-right"><?= Html::encode($invoice->getDate_created()->format($dateHelper->style())); ?></td>
    </tr>
    <tr class="<?= ($is_overdue ? 'overdue' : '') ?>">
        <td><?= $translator->translate('i.due_date'); ?></td>
        <td class="text-right">
            <?= Html::encode($invoice->getDate_due()->format($dateHelper->style())); ?>
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
            <td class="text-right"><?= $payment_method; ?></td>
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
    . 'merchantId: "'. (string)$amazonPayButton['merchantId']. '",'
    // SANDBOX-xxxxxxxxxx
    . 'publicKeyId: "'. (string)$amazonPayButton['publicKeyId'].'",'
    // eg. Currency shortcode eg. GBP        
    . 'ledgerCurrency: "'. (string)$amazonPayButton['ledgerCurrency'].'",'           
    // customize the buyer experience eg. en_GB
    . 'checkoutLanguage: "'. (string)$amazonPayButton['checkoutLanguage']. '",'
    // 'PayAndShip' - Offer checkout using buyer's Amazon wallet and address book. 
    //              Select this product type if you need the buyer's shipping details
    // 'PayOnly' - Offer checkout using only the buyer's Amazon wallet. 
    //              Select this product type if you do not need the buyer's shipping details
    // 'SignIn' - Offer Amazon Sign-in. Select this product type if you need buyer details 
    //              before the buyer starts Amazon Pay checkout. See Amazon Sign-in 
    //              for more information.       
    . 'productType: "'. (string)$amazonPayButton['productType'].'",'
    //'Home' - Initial or main page
    //'Product' - Product details page
    //'Cart' - Cart review page before buyer starts checkout
    //'Checkout' - Any page after buyer starts checkout
    //'Other' - Any page that doesn't fit the previous descriptions        
    . 'placement: "Other",'
    . 'buttonColor: "Gold",'
    // Currency shortcode eg. GBP
    . 'estimatedOrderAmount: { "amount": "'. (string)$amazonPayButton['amount'].'", "currencyCode": "'. (string)$amazonPayButton['ledgerCurrency'].'"},'
    // configure Create Checkout Session request
    . 'createCheckoutSessionConfig: {'
    // json encoded string generated in step 2
    . "           payloadJSON: '". (string)$amazonPayButton['payloadJSON']."'," 
    // signature generated in step 3
    . "signature: '". (string)$amazonPayButton['signature']."'"  
    . '}'    
    . '});';               
    echo Html::script($js20)->type('module')
                            ->charset('utf-8');
?>




