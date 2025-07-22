<?php
declare(strict_types=1);

use App\Invoice\Asset\ReportAsset;
use Yiisoft\Html\Html;

/*
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 * @var string $from_date
 * @var string $to_date
 * @var array $results
 * @var array $result
 * @var string $result['sales_no_tax']
 * @var string $result['item_tax_total']
 * @var string $result['tax_total']
 * @var string $result['sales_with_tax']
 *
 */

$assetManager->register(ReportAsset::class);

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?php echo $translator->translate('cldr'); ?>">
<head>
    <title><?php echo $translator->translate('sales.by.client'); ?></title>
</head>
<body>
<?php $this->beginBody(); ?>
<h3 class="report_title">
    <?php echo $translator->translate('sales.by.client'); ?><br/>
    <small><?php echo $from_date.' - '.$to_date; ?></small>
</h3>
<table>
    <tr>
        <th><?php echo $translator->translate('client'); ?></th>
        <th class="amount"><?php echo $translator->translate('count'); ?></th>
        <th class="amount"><?php echo $translator->translate('sales'); ?></th>
        <th class="amount"><?php echo $translator->translate('item.tax'); ?></th>
        <th class="amount"><?php echo $translator->translate('tax'); ?></th>
        <th class="amount"><?php echo $translator->translate('sales.with.tax'); ?></th>
    </tr>
    <?php
        /**
         * @var array $result
         */
        foreach ($results as $result) { ?>
        <tr>
            <td><?php echo Html::encode($result['client_name_surname']); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?php echo Html::encode($result['inv_count']); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?php echo Html::encode($numberHelper->format_currency($result['sales_no_tax'])); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?php echo Html::encode($numberHelper->format_currency($result['item_tax_total'])); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?php echo Html::encode($numberHelper->format_currency($result['tax_total'])); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?php echo Html::encode($numberHelper->format_currency($result['sales_with_tax'])); ?></td>
        </tr>
    <?php } ?>
</table>
<?php $this->endBody(); ?>
</body>
</html>
<?php $this->endPage(true); ?> 
