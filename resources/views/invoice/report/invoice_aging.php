<?php
declare(strict_types=1);

use App\Invoice\Asset\ReportAsset;
use Yiisoft\Html\Html as H;

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
<html lang="<?= $translator->translate('cldr'); ?>">
<head>
    <title><?= H::encode($translator->translate('aging')); ?></title>
</head>
<body>
<?php $this->beginBody();
      $style1px = 'width:15%;text-align:right;border-bottom: 1px solid black;';
      $style0px = 'width:15%;text-align:right;border-bottom: 0px solid black;';
?>
<h3 class="report_title"><?= H::encode($translator->translate('aging')); ?></h3>
<table>
    <tr>
        <th><?= H::encode($translator->translate('client')); ?></th>
        <th class="amount"><?= H::encode($translator->translate('aging.1.15')); ?></th>
        <th class="amount"><?= H::encode($translator->translate('aging.16.30')); ?></th>
        <th class="amount"><?= H::encode($translator->translate('aging.above.30')); ?></th>
        <th class="amount"><?= H::encode($translator->translate('total')); ?></th>
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
        <td><?= H::encode($result['client']); ?></td>
        <td style="<?= $style1px ?>">
            <?= $result['range_1'] > 0 ? '<strong>' : ''; ?>
            <?= H::encode($numberHelper->formatCurrency($result['range_1'])); ?>
        </td>
        <td style="<?= $style1px ?>">
            <?= $result['range_2'] > 0 ? '<strong>' : ''; ?>
            <?= H::encode($numberHelper->formatCurrency($result['range_2'])); ?>
        </td>
        <td style="<?= $style1px ?>">
            <?= $result['range_3'] > 0 ? '<strong>' : ''; ?>
            <?= H::encode($numberHelper->formatCurrency($result['range_3'])); ?>
        </td>
        <td style="<?= $style1px ?>">
            <?= $result['total_balance'] > 0 ? '<strong>' : ''; ?>
            <?= H::encode($numberHelper->formatCurrency($result['total_balance'])); ?>
        </td>
    </tr>
    <?php } ?>
    <tr>
        <td></td>
        <td style="<?= $style0px ?>"></td>
        <td style="<?= $style0px ?>"></td>
        <td style="<?= $style0px ?>"></td>
        <td style="<?= $style0px ?>"></td>
    </tr>
    <?php
        /**
         * @var array $dueInvoice
         * @var int $dueInvoice['range_index']
         * @var int $dueInvoice['invoice_balance']
         */
        foreach ($dueInvoices as $dueInvoice) { ?>
    <tr>
        <td><?= H::encode($dueInvoice['invoice_number']); ?></td>
        <td style="<?= $style1px ?>">
            <?= $dueInvoice['invoice_balance'] > 0 ? '<strong>' : ''; ?>
            <?= H::encode($dueInvoice['range_index'] == 1 ?
                    $numberHelper->formatCurrency(
                        $dueInvoice['invoice_balance']) : ''); ?>
        </td>
        <td style="<?= $style1px ?>">
            <?= $dueInvoice['invoice_balance'] > 0 ? '<strong>' : ''; ?>
            <?= H::encode($dueInvoice['range_index'] == 2 ?
                    $numberHelper->formatCurrency(
                        $dueInvoice['invoice_balance']) : ''); ?>
        </td>
        <td style="<?= $style1px ?>">
            <?= $dueInvoice['invoice_balance'] > 0 ? '<strong>' : ''; ?>
            <?= H::encode($dueInvoice['range_index'] == 3 ?
                    $numberHelper->formatCurrency(
                        $dueInvoice['invoice_balance']) : ''); ?>
        </td>
        <td style="<?= $style1px ?>"></td>
    </tr>
    <?php } ?>
</table>
<?php $this->endBody(); ?>
</body>
</html>
<?php $this->endPage(true);
