<?php declare(strict_types=1);
 
 use Yiisoft\Html\Html;
 /**
 * @var \Yiisoft\View\WebView $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title 
 * @var \Yiisoft\Session\Flash\FlashInterface $flash
 */
 ?>   
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $heading; ?>Yii - Invoice</title>
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
            vertical-align: middle;
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
<body>
    
<h1><?php echo $heading. '. '.$message; ?></h1>
<?= Html::button($translator->translate('invoice.back'),['onclick'=>'history.back()']); ?>
</body>
</html>

