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
 */

$this->beginPage();

$assetManager->register(ReportAsset::class);
?>
<!DOCTYPE html>
<html lang="<?php echo $translator->translate('cldr'); ?>">
<head>
    <title><?php echo $translator->translate('report.sales.by.task'); ?></title>
</head>
<body>
<?php $this->beginBody(); ?>
<h3 class="report_title">
    <?php echo $translator->translate('report.sales.by.task'); ?><br/>
    <small><?php echo $from_date.' - '.$to_date; ?></small>
</h3>
<table>
    <tr>
        <th><?php echo $translator->translate('task'); ?></th>
        <th class="amount"><?php echo $translator->translate('count'); ?></th>
        <th class="amount"><?php echo $translator->translate('sales'); ?></th>
        <th class="amount"><?php echo $translator->translate('item.tax'); ?></th>
    </tr>
    <?php
        /**
         * @var array $result
         */
        foreach ($results as $result) { ?>
        <tr>
            <td><?php echo Html::encode($result['task_name']); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?php echo Html::encode($result['inv_count']); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?php echo Html::encode($numberHelper->format_currency($result['sales_no_tax'])); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?php echo Html::encode($numberHelper->format_currency($result['item_tax_total'])); ?></td>
        </tr>
    <?php } ?>
</table>
<?php $this->endBody(); ?>
</body>
</html>
<?php $this->endPage(true); ?> 
