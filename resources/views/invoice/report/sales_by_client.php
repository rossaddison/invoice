<?php
declare(strict_types=1);

use App\Asset\ReportAsset;
use Yiisoft\Html\Html;

$this->beginPage();

/**
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var \Yiisoft\Assets\AssetManager $assetManager 
 * @var \DateTimeImmutable $from_date
 * @var \DateTimeImmutable $to_date 
 * @var array $results 
 */

$assetManager->register(ReportAsset::class);
?>
<!DOCTYPE html>
<html lang="<?= $s->trans('cldr'); ?>">
<head>
    <title><?= $s->trans('sales_by_client'); ?></title>
</head>
<body>
<?php $this->beginBody() ?>
<h3 class="report_title">
    <?= $s->trans('sales_by_client'); ?><br/>
    <small><?= $from_date . ' - ' . $to_date ?></small>
</h3>
<table>
    <tr>
        <th><?= $s->trans('client'); ?></th>
        <th class="amount"><?= $s->trans('invoice_count'); ?></th>
        <th class="amount"><?= $s->trans('sales'); ?></th>
        <th class="amount"><?= $s->trans('item_tax'); ?></th>
        <th class="amount"><?= $s->trans('invoice_tax'); ?></th>
        <th class="amount"><?= $s->trans('sales_with_tax'); ?></th>
    </tr>
    <?php foreach ($results as $result) { ?>
        <tr>
            <td><?= Html::encode(($result['client_name_surname'])); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?= Html::encode($result['inv_count']); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?= Html::encode($numberhelper->format_currency($result['sales_no_tax'])); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?= Html::encode($numberhelper->format_currency($result['item_tax_total'])); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?= Html::encode($numberhelper->format_currency($result['tax_total'])); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?= Html::encode($numberhelper->format_currency($result['sales_with_tax'])); ?></td>
        </tr>
    <?php } ?>
</table>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage(true); ?> 
