<?php

declare(strict_types=1);

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $gateway
 * @var string $heading
 * @var string $message
 * @var string $sandbox_url
 * @var string $url
 * @var string $url_key
 */

?> 

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $heading; ?><?= $translator->translate('invoice'); ?></title>
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
<body  
<h1><?php echo $heading; ?></h1>
<?php echo $message; ?>
<form method="POST" class="form-inline" action="<?= $urlGenerator->generate($url, ['url_key' => $url_key, 'gateway' => $gateway]); ?>">
       <input type="hidden" name="_csrf" value="<?= $csrf ?>">
       <button type="submit" class="btn btn-lg btn-link"><i class="fa fa-arrow-left"></i></button>
       <?php if ($s->getSetting('gateway_'.lcfirst($gateway).'_sandbox') === '1') { ?>
            <a href="<?= $sandbox_url; ?>"><?= $sandbox_url; ?></a>
       <?php } ?>     
</form>

