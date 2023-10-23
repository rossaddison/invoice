<?php
declare(strict_types=1);

use App\Asset\ReportAsset;
use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\Assets\AssetManager $assetManager 
 * @var \DateTimeImmutable $from_date
 * @var \DateTimeImmutable $to_date 
 * @var array $results 
 */

$assetManager->register(ReportAsset::class);

$this->beginPage();
?>

<!DOCTYPE html>
<html lang="<?= $s->trans('cldr'); ?>">

<body>
<?php $this->beginBody() ?>   
<h3 class="report_title">
    <?= Html::encode($s->trans('sales_by_date')); ?>
    <br/>
    <small><?= Html::encode($from_date . ' - ' . $to_date); ?></small>
</h3>

<table>
    <tr>
        <th style="width:15%;text-align:center;border-bottom: 1px solid black;"> <?= Html::encode($s->trans('vat_id')); ?></th>
        <th style="width:50%;text-align:center;border-bottom: 1px solid black;"> <?= Html::encode($s->trans('name')); ?></th>
        <th style="width:15%;text-align:center;border-bottom: 1px solid black;"> <?= Html::encode($s->trans('sales')); ?></th>
        <th style="width:20%;text-align:center;border-bottom: 1px solid black;"> <?= Html::encode($s->trans('item_tax')); ?></th>
        <th style="width:20%;text-align:center;border-bottom: 1px solid black;"> <?= Html::encode($s->trans('invoice_tax')); ?></th>
        <th style="width:20%;text-align:center;border-bottom: 1px solid black;"> <?= Html::encode($s->trans('sales_with_tax')); ?></th>
        <th style="width:20%;text-align:center;border-bottom: 1px solid black;"> <?= Html::encode($s->trans('paid')); ?></th>
    </tr>
    <?php foreach ($results as $result) { ?>
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
            <?= Html::encode($s->trans('Q1'). '/'.$result['year']); ?></td>
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
            <?= Html::encode($s->trans('Q2').'/'.$result['year']); ?>
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
            <?= Html::encode($s->trans('Q3').'/'.$result['year']); ?>
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
            <?= Html::encode($s->trans('Q4').'/'.$result['year']); ?>
        </td>
        <td  style="width:20%;text-align:right;border-bottom: 1px solid black;"></td>
        <td  style="width:20%;text-align:right;border-bottom: 1px solid black;">
            <?= Html::encode($n->format_currency($result['quarters']['fourth']['sales_no_tax']) ?? 0.00); ?></td>
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