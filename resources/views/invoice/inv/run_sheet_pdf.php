<?php
declare(strict_types=1);

use App\Invoice\Asset\ReportAsset;
use App\Invoice\Helpers\GoogleMapsLink;
use Yiisoft\Html\Html as H;

/**
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 * @var list<App\Invoice\Inv\RunSheetPdfRow> $rows
 * @var string $title
 * @var string|null $startPostcode
 * @var string $startLabel
 * @var bool $showPrivateDetails false on a field worker's sheet: no client, phone or balance column
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
<?php if (null !== $startPostcode && '' !== $startPostcode) { ?>
<p><strong><?= H::encode($startLabel); ?>:</strong>
    <a href="<?= H::encode(GoogleMapsLink::directionsTo($startPostcode)); ?>"><?= H::encode($startPostcode); ?></a></p>
<?php } ?>
<table>
    <tr>
        <th style="width:50%;">
            <?= H::encode($translator->translate('street.address')); ?>
        </th>
        <?php if ($showPrivateDetails) { ?>
        <th><?= H::encode($translator->translate('client')); ?></th>
        <?php } ?>
        <th><?= H::encode($translator->translate('allocation')); ?></th>
        <?php if ($showPrivateDetails) { ?>
        <th><?= H::encode($translator->translate('phone')); ?></th>
        <?php } ?>
        <th><?= H::encode($translator->translate('invoice')); ?></th>
        <?php if ($showPrivateDetails) { ?>
        <th class="text-end">
            <?= H::encode($translator->translate('balance')); ?>
        </th>
        <?php } ?>
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
            <?= H::encode($row->address !== '' ? $row->address : '—'); ?>
            <?php } ?>
            <?php if ($row->pullPoint) { ?>
            <?php $pullPointLabel = H::encode($translator->translate('dwelling.pull.point')); ?>
            <span title="<?= $pullPointLabel; ?>">📍 <?= $pullPointLabel; ?></span>
            <?php } ?>
        </td>
        <?php if ($showPrivateDetails) { ?>
        <td><?= H::encode($row->clientName); ?></td>
        <?php } ?>
        <td><?= H::encode($row->sides !== '' ? $row->sides : '—'); ?></td>
        <?php if ($showPrivateDetails) { ?>
        <td><?= H::encode($row->phone ?? '—'); ?></td>
        <?php } ?>
        <td><?= H::encode($row->invNumber); ?></td>
        <?php if ($showPrivateDetails) { ?>
        <td class="text-end">
            <?= H::encode($numberHelper->formatCurrency($row->balance)); ?>
        </td>
        <?php } ?>
    </tr>
    <?php } ?>
</table>
<?php $this->endBody(); ?>
</body>
</html>
<?php $this->endPage(true);
