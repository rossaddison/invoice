<?php
declare(strict_types=1);

use App\Invoice\Asset\ReportAsset;
use Yiisoft\Html\Html;


/**
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Assets\AssetManager $assetManager 
 * @var string $from_date
 * @var string $to_date 
 * @var array $results 
 */

$assetManager->register(ReportAsset::class);

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= $translator->translate('i.cldr'); ?>">
<head>
    <title><?= $translator->translate('invoice.report.sales.by.product'); ?></title>
</head>
<body>
<?php $this->beginBody() ?>
<h3 class="report_title">
    <?= $translator->translate('invoice.report.sales.by.product'); ?><br/>
    <small><?= $from_date . ' - ' . $to_date ?></small>
</h3>
<table>
    <tr>
        <th><?= $translator->translate('i.product'); ?></th>
        <th class="amount"><?= $translator->translate('i.invoice_count'); ?></th>
        <th class="amount"><?= $translator->translate('i.sales'); ?></th>
        <th class="amount"><?= $translator->translate('i.item_tax'); ?></th>
    </tr>
    <?php
        /**
         * @var array $result
         */
        foreach ($results as $result) { ?>
        <tr>
            <td><?= Html::encode(($result['product_name'])); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?= Html::encode($result['inv_count']); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?= Html::encode($numberHelper->format_currency($result['sales_no_tax'])); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?= Html::encode($numberHelper->format_currency($result['item_tax_total'])); ?></td>
        </tr>
    <?php } ?>
</table>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage(true); ?> 
