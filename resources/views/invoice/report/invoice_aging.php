<?php
declare(strict_types=1);

use App\Asset\ReportAsset;
use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\Assets\AssetManager $assetManager 
 * @var array $results
 * @var array $due_invoices 
 */

$assetManager->register(ReportAsset::class);

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= $s->trans('cldr'); ?>">
<head>
    <title><?= Html::encode($s->trans('invoice_aging')); ?></title>
</head>
<body>
<?php $this->beginBody() ?>
<h3 class="report_title"><?= Html::encode($s->trans('invoice_aging')); ?></h3>
<table>
    <tr>
        <th><?= Html::encode($s->trans('client')); ?></th>
        <th class="amount"><?= Html::encode($s->trans('invoice_aging_1_15')); ?></th>
        <th class="amount"><?= Html::encode($s->trans('invoice_aging_16_30')); ?></th>
        <th class="amount"><?= Html::encode($s->trans('invoice_aging_above_30')); ?></th>
        <th class="amount"><?= Html::encode($s->trans('total')); ?></th>
    </tr>
    <?php foreach ($results as $result) { ?>
    <tr>
        <td><?= Html::encode($result['client']); ?></td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?= $result['range_1'] > 0 ? '<strong>' : ''; ?>
            <?= Html::encode($numberhelper->format_currency($result['range_1'])); ?>
        </td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?= $result['range_2'] > 0 ? '<strong>' : ''; ?>
            <?= Html::encode($numberhelper->format_currency($result['range_2'])); ?>
        </td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?= $result['range_3'] > 0 ? '<strong>' : ''; ?>
            <?= Html::encode($numberhelper->format_currency($result['range_3'])); ?>
        </td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?= $result['total_balance'] > 0 ? '<strong>' : ''; ?>
            <?= Html::encode($numberhelper->format_currency($result['total_balance'])); ?>
        </td>
    </tr>
    <?php } ?>
    <tr>
        <td></td>
        <td style="width:15%;text-align:right;border-bottom: 0px solid black;"></td>
        <td style="width:15%;text-align:right;border-bottom: 0px solid black;"></td>
        <td style="width:15%;text-align:right;border-bottom: 0px solid black;"></td>
        <td style="width:15%;text-align:right;border-bottom: 0px solid black;"></td>
    </tr>
    <?php foreach ($due_invoices as $due_invoice) { ?>
    <tr>
        <td><?= Html::encode($due_invoice['invoice_number']); ?></td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?= $due_invoice['invoice_balance'] > 0 ? '<strong>' : ''; ?>
            <?= Html::encode($due_invoice['range_index'] == 1 ? $numberhelper->format_currency($due_invoice['invoice_balance']) : ''); ?>
        </td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?= $due_invoice['invoice_balance'] > 0 ? '<strong>' : ''; ?>
            <?= Html::encode($due_invoice['range_index'] == 2 ? $numberhelper->format_currency($due_invoice['invoice_balance']) : ''); ?>
        </td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?= $due_invoice['invoice_balance'] > 0 ? '<strong>' : ''; ?>
            <?= Html::encode($due_invoice['range_index'] == 3 ? $numberhelper->format_currency($due_invoice['invoice_balance']) : ''); ?>
        </td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;"></td>
    </tr>
    <?php } ?>    
</table>
<?php $this->endBody(); ?>
</body>
</html>
<?php $this->endPage(true);