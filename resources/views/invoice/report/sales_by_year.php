<?php
declare(strict_types=1);

use App\Invoice\Asset\ReportAsset;
use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Helpers\NumberHelper $n
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 * @var string $from_date
 * @var string $to_date
 * @var array $results
 * @var array $result['quarters']
 * @var array $result['quarters']['first']
 * @var array $result['quarters']['second']
 * @var array $result['quarters']['third']
 * @var array $result['quarters']['fourth']
 */

$assetManager->register(ReportAsset::class);

$this->beginPage();
?>

<!DOCTYPE html>
<html lang="<?= $translator->translate('cldr'); ?>">

<body>
<?php $this->beginBody() ?>   
<h3 class="report_title">
    <?= Html::encode($translator->translate('sales.by.date')); ?>
    <br/>
    <small><?= Html::encode($from_date  . ' - ' . $to_date); ?></small>
</h3>

<table>
    <tr>
        <th style="width:15%;text-align:center;border-bottom: 1px solid black;"> <?= Html::encode($translator->translate('vat.id')); ?></th>
        <th style="width:50%;text-align:center;border-bottom: 1px solid black;"> <?= Html::encode($translator->translate('name')); ?></th>
        <th style="width:15%;text-align:center;border-bottom: 1px solid black;"> <?= Html::encode($translator->translate('sales')); ?></th>
        <th style="width:20%;text-align:center;border-bottom: 1px solid black;"> <?= Html::encode($translator->translate('item.tax')); ?></th>
        <th style="width:20%;text-align:center;border-bottom: 1px solid black;"> <?= Html::encode($translator->translate('tax')); ?></th>
        <th style="width:20%;text-align:center;border-bottom: 1px solid black;"> <?= Html::encode($translator->translate('sales.with.tax')); ?></th>
        <th style="width:20%;text-align:center;border-bottom: 1px solid black;"> <?= Html::encode($translator->translate('paid')); ?></th>
    </tr>
    <?php
        /**
         * @var array $result
         */
        foreach ($results as $result) { ?>
    <tr>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;"><b>
            <?= Html::encode($result['VAT_ID'] ?? ''); ?></b>
        </td>
        <td style="width:50%;text-align:right;border-bottom: 1px solid black;"><b>
            <?= Html::encode($result['Name'] ?? ''); ?></b>
        </td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;"><b>
            <?= Html::encode($n->format_currency($result['period_sales_no_tax'] ?? 0.00)); ?></b>
        </td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;"><b>
            <?= Html::encode($n->format_currency($result['period_item_tax_total'] ?? 0.00)); ?></b>
        </td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;"><b>
            <?= Html::encode($n->format_currency($result['period_tax_total'] ?? 0.00)); ?></b>
        </td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;"><b>
            <?= Html::encode($n->format_currency($result['period_sales_with_tax'] ?? 0.00)); ?></b>
        </td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;"><b>
            <?= Html::encode($n->format_currency($result['period_total_paid'] ?? 0.00)); ?></b>
        </td>       
    </tr>
    <tr>
        <td style="width:20%;text-align:left;border-bottom: 1px solid black;">
            <?= Html::encode($translator->translate('Q1'). '/'.(string)$result['year']); ?></td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;"></td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['first']['sales_no_tax'] ?? 0.00)); ?></td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['first']['item_tax_total'] ?? 0.00)); ?></td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['first']['tax_total'] ?? 0.00)); ?></td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['first']['sales_with_tax'] ?? 0.00)); ?></td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['first']['paid'] ?? 0.00)); ?></td>       
    </tr>
    <tr>
        <td style="width:20%;text-align:left;border-bottom: 1px solid black;">
            <?= Html::encode($translator->translate('Q2').'/'.(string)$result['year']); ?>
        </td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;"></td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['second']['sales_no_tax'] ?? 0.00)); ?></td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['second']['item_tax_total'] ?? 0.00)); ?></td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['second']['tax_total'] ?? 0.00)); ?></td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['second']['sales_with_tax'] ?? 0.00)); ?></td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['second']['paid'] ?? 0.00)); ?></td>       
    </tr>
    <tr>
        <td style="width:20%;text-align:left;border-bottom: 1px solid black;">
            <?= Html::encode($translator->translate('Q3').'/'.(string)$result['year']); ?>
        </td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;"></td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['third']['sales_no_tax'] ?? 0.00)); ?></td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['third']['item_tax_total'] ?? 0.00)); ?></td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['third']['tax_total'] ?? 0.00)); ?></td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['third']['sales_with_tax'] ?? 0.00)); ?></td>
        <td style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['third']['paid'] ?? 0.00)); ?></td>       
    </tr>
    <tr>
        <td  style="width:20%;text-align:left;border-bottom: 1px solid black;">
            <?= Html::encode($translator->translate('Q4').'/'.(string)$result['year']); ?>
        </td>
        <td  style="width:20%;text-align:right;border-bottom: 1px solid black;"></td>
        <td  style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['fourth']['sales_no_tax']) ?: 0.00); ?></td>
        <td  style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['fourth']['item_tax_total'] ?? 0.00)); ?></td>
        <td  style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['fourth']['tax_total'] ?? 0.00)); ?></td>
        <td  style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['fourth']['sales_with_tax'] ?? 0.00)); ?></td>
        <td  style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['fourth']['paid'] ?? 0.00)); ?></td>       
    </tr>
    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    <?php } ?>
</table>
<?php $this->endBody(); ?>
</body>
</html>
<?php $this->endPage(true); ?>