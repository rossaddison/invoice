<?php
declare(strict_types=1);

use App\Invoice\Asset\ReportAsset;
use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 * @var array $results
 * @var array $dueInvoices
 */

$assetManager->register(ReportAsset::class);

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= $translator->translate('i.cldr'); ?>">
<head>
    <title><?= Html::encode($translator->translate('i.invoice_aging')); ?></title>
</head>
<body>
<?php $this->beginBody() ?>
<h3 class="report_title"><?= Html::encode($translator->translate('i.invoice_aging')); ?></h3>
<table>
    <tr>
        <th><?= Html::encode($translator->translate('i.client')); ?></th>
        <th class="amount"><?= Html::encode($translator->translate('i.invoice_aging_1_15')); ?></th>
        <th class="amount"><?= Html::encode($translator->translate('i.invoice_aging_16_30')); ?></th>
        <th class="amount"><?= Html::encode($translator->translate('i.invoice_aging_above_30')); ?></th>
        <th class="amount"><?= Html::encode($translator->translate('i.total')); ?></th>
    </tr>
    <?php
        /**
         * @var array $result
         * @var int $result['range_1']
         * @var int $result['range_2']
         * @var int $result['range_3']
         * @var int $result['total_balance']
         */
        foreach ($results as $result) { ?>
    <tr>
        <td><?= Html::encode($result['client']); ?></td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?= $result['range_1'] > 0 ? '<strong>' : ''; ?>
            <?= Html::encode($numberHelper->format_currency($result['range_1'])); ?>
        </td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?= $result['range_2'] > 0 ? '<strong>' : ''; ?>
            <?= Html::encode($numberHelper->format_currency($result['range_2'])); ?>
        </td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?= $result['range_3'] > 0 ? '<strong>' : ''; ?>
            <?= Html::encode($numberHelper->format_currency($result['range_3'])); ?>
        </td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?= $result['total_balance'] > 0 ? '<strong>' : ''; ?>
            <?= Html::encode($numberHelper->format_currency($result['total_balance'])); ?>
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
    <?php
        /**
         * @var array $dueInvoices
         * @var array $dueInvoice
         * @var int $dueInvoice['range_index']
         * @var int $dueInvoice['invoice_balance']
         */
        foreach ($dueInvoices as $dueInvoice) { ?>
    <tr>
        <td><?= Html::encode($dueInvoice['invoice_number']); ?></td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?= $dueInvoice['invoice_balance'] > 0 ? '<strong>' : ''; ?>
            <?= Html::encode($dueInvoice['range_index'] == 1 ? $numberHelper->format_currency($dueInvoice['invoice_balance']) : ''); ?>
        </td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?= $dueInvoice['invoice_balance'] > 0 ? '<strong>' : ''; ?>
            <?= Html::encode($dueInvoice['range_index'] == 2 ? $numberHelper->format_currency($dueInvoice['invoice_balance']) : ''); ?>
        </td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?= $dueInvoice['invoice_balance'] > 0 ? '<strong>' : ''; ?>
            <?= Html::encode($dueInvoice['range_index'] == 3 ? $numberHelper->format_currency($dueInvoice['invoice_balance']) : ''); ?>
        </td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;"></td>
    </tr>
    <?php } ?>    
</table>
<?php $this->endBody(); ?>
</body>
</html>
<?php $this->endPage(true);