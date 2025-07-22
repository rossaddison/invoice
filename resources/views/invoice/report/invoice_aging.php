<?php
declare(strict_types=1);

use App\Invoice\Asset\ReportAsset;
use Yiisoft\Html\Html;

/*
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
<html lang="<?php echo $translator->translate('cldr'); ?>">
<head>
    <title><?php echo Html::encode($translator->translate('aging')); ?></title>
</head>
<body>
<?php $this->beginBody(); ?>
<h3 class="report_title"><?php echo Html::encode($translator->translate('aging')); ?></h3>
<table>
    <tr>
        <th><?php echo Html::encode($translator->translate('client')); ?></th>
        <th class="amount"><?php echo Html::encode($translator->translate('aging.1.15')); ?></th>
        <th class="amount"><?php echo Html::encode($translator->translate('aging.16.30')); ?></th>
        <th class="amount"><?php echo Html::encode($translator->translate('aging.above.30')); ?></th>
        <th class="amount"><?php echo Html::encode($translator->translate('total')); ?></th>
    </tr>
    <?php
        /**
         * @var array $result
         * @var int   $result['range_1']
         * @var int   $result['range_2']
         * @var int   $result['range_3']
         * @var int   $result['total_balance']
         */
        foreach ($results as $result) { ?>
    <tr>
        <td><?php echo Html::encode($result['client']); ?></td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?php echo $result['range_1'] > 0 ? '<strong>' : ''; ?>
            <?php echo Html::encode($numberHelper->format_currency($result['range_1'])); ?>
        </td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?php echo $result['range_2'] > 0 ? '<strong>' : ''; ?>
            <?php echo Html::encode($numberHelper->format_currency($result['range_2'])); ?>
        </td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?php echo $result['range_3'] > 0 ? '<strong>' : ''; ?>
            <?php echo Html::encode($numberHelper->format_currency($result['range_3'])); ?>
        </td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?php echo $result['total_balance'] > 0 ? '<strong>' : ''; ?>
            <?php echo Html::encode($numberHelper->format_currency($result['total_balance'])); ?>
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
         * @var int   $dueInvoice['range_index']
         * @var int   $dueInvoice['invoice_balance']
         */
        foreach ($dueInvoices as $dueInvoice) { ?>
    <tr>
        <td><?php echo Html::encode($dueInvoice['invoice_number']); ?></td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?php echo $dueInvoice['invoice_balance'] > 0 ? '<strong>' : ''; ?>
            <?php echo Html::encode(1 == $dueInvoice['range_index'] ? $numberHelper->format_currency($dueInvoice['invoice_balance']) : ''); ?>
        </td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?php echo $dueInvoice['invoice_balance'] > 0 ? '<strong>' : ''; ?>
            <?php echo Html::encode(2 == $dueInvoice['range_index'] ? $numberHelper->format_currency($dueInvoice['invoice_balance']) : ''); ?>
        </td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;">
            <?php echo $dueInvoice['invoice_balance'] > 0 ? '<strong>' : ''; ?>
            <?php echo Html::encode(3 == $dueInvoice['range_index'] ? $numberHelper->format_currency($dueInvoice['invoice_balance']) : ''); ?>
        </td>
        <td style="width:15%;text-align:right;border-bottom: 1px solid black;"></td>
    </tr>
    <?php } ?>    
</table>
<?php $this->endBody(); ?>
</body>
</html>
<?php $this->endPage(true);
