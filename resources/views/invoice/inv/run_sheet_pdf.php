<?php
declare(strict_types=1);

use App\Invoice\Asset\ReportAsset;
use Yiisoft\Html\Html as H;

/**
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 * @var list<App\Invoice\Inv\RunSheetPdfRow> $rows
 * @var string $title
 */

$assetManager->register(ReportAsset::class);

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= $translator->translate('cldr'); ?>">
<head>
    <title><?= H::encode($title); ?></title>
</head>
<body>
<?php $this->beginBody(); ?>
<h3 class="report_title"><?= H::encode($title); ?></h3>
<table>
    <tr>
        <th style="width:50%;">
            <?= H::encode($translator->translate('street.address')); ?>
        </th>
        <th><?= H::encode($translator->translate('client')); ?></th>
        <th><?= H::encode($translator->translate('phone')); ?></th>
        <th><?= H::encode($translator->translate('invoice')); ?></th>
        <th class="text-end">
            <?= H::encode($translator->translate('balance')); ?>
        </th>
    </tr>
    <?php foreach ($rows as $row) { ?>
    <tr>
        <td style="font-size:14px;font-weight:bold;">
            <?php if ($row->mapsUrl !== null) { ?>
            <a
                href="<?= H::encode($row->mapsUrl); ?>"
                target="_blank"
                rel="noopener noreferrer"
            >
                <?= H::encode($row->address); ?>
            </a>
            <?php } else { ?>
            <?= H::encode($row->address); ?>
            <?php } ?>
        </td>
        <td><?= H::encode($row->clientName); ?></td>
        <td><?= H::encode($row->phone ?? '—'); ?></td>
        <td><?= H::encode($row->invNumber); ?></td>
        <td class="text-end">
            <?= H::encode($numberHelper->formatCurrency($row->balance)); ?>
        </td>
    </tr>
    <?php } ?>
</table>
<?php $this->endBody(); ?>
</body>
</html>
<?php $this->endPage(true);
