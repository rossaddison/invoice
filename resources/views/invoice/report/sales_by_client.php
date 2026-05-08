<?php
declare(strict_types=1);

use App\Invoice\Asset\ReportAsset;
use Yiisoft\Html\Html as H;

/**
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
<html lang="<?= $translator->translate('cldr'); ?>">
<head>
    <title><?= $translator->translate('sales.by.client'); ?></title>
</head>
<body>
<?php $this->beginBody();
      $style0px = 'width:15%;text-align:right;border-bottom: 0px solid black;';
?>
<h3 class="report_title">
    <?= $translator->translate('sales.by.client'); ?><br/>
    <small><?= $from_date . ' - ' . $to_date ?></small>
</h3>
<table>
    <tr>
        <th><?= $translator->translate('client'); ?></th>
        <th class="amount"><?= $translator->translate('count'); ?></th>
        <th class="amount"><?= $translator->translate('sales'); ?></th>
        <th class="amount"><?= $translator->translate('item.tax'); ?></th>
        <th class="amount"><?= $translator->translate('tax'); ?></th>
        <th class="amount"><?= $translator->translate('sales.with.tax'); ?></th>
    </tr>
    <?php
        /**
         * @var array $result
         */
        foreach ($results as $result) { ?>
        <tr>
            <td><?= H::encode(($result['client_name_surname'])); ?></td>
            <td style="<?= $style0px ?>">
                <?= H::encode($result['inv_count']); ?>
            </td>
            <td style="<?= $style0px ?>">
                <?= H::encode($numberHelper->formatCurrency($result['sales_no_tax'])); ?>
            </td>
            <td style="<?= $style0px ?>">
                <?= H::encode($numberHelper->formatCurrency($result['item_tax_total'])); ?>
            </td>
            <td style="<?= $style0px ?>">
                <?= H::encode($numberHelper->formatCurrency($result['tax_total'])); ?>
            </td>
            <td style="<?= $style0px ?>">
                <?= H::encode($numberHelper->formatCurrency($result['sales_with_tax'])); ?>
            </td>
        </tr>
    <?php } ?>
</table>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage(true); ?>
