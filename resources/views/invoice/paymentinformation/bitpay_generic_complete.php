<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Related logic: see BitPayPaymentController's own class docblock for why
 * this page — unlike every other gateway's own Complete() page — is
 * deliberately NOT invoice-specific: BitPay's redirect-URL allow-list
 * requires a fixed URL with nothing appended by anyone, so there is no
 * way to know which invoice a given visit here is for. The invoice is
 * only ever actually marked paid by BitPayWebhookHandler, never by this
 * page.
 *
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>
        <?php echo Html::encode($translator->translate('payment')); ?>
        <?= $translator->translate('invoice'); ?>
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>

        * {
            line-height: 1.2;
            margin: 0;
        }

        html {
            color: #777;
            display: table;
            font-family: sans-serif;
            height: 100%;
            text-align: center;
            width: 100%;
        }

        body {
            display: table-cell;
            margin: 2em auto;
            vertical-align: top;
        }

        h1 {
            color: #333;
            font-size: 2em;
            font-weight: 400;
        }

        p {
            margin: 0 auto;
            width: 280px;
        }

        @media only screen and (max-width: 280px) {

            body, p {
                width: 95%;
            }

            h1 {
                font-size: 1.5em;
                margin: 0 0 0.3em;
            }

        }

    </style>
</head>
<body>
<h1><?php echo Html::encode($translator->translate('online.payment.payment.processing.generic')); ?></h1>
<?php echo Html::encode($translator->translate('online.payment.bitpay.generic.complete.message')); ?>
</body>
</html>
