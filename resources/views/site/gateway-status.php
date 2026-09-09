<?php

declare(strict_types=1);

use App\Invoice\Asset\YiiDataViewNoInlineJsAsset;
use App\Invoice\PaymentInformation\GatewayStatus\Widget\GatewayStatusListWidget;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Html\Html;

/**
 * Public payment-gateway coverage table. See
 * docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md for how this data is generated
 * and kept up to date. Sorting, pagination, and per-column filtering
 * (region, sandbox status, retest-needed) are all real
 * (GatewayStatusListWidget/GridView) — the same grid mechanics as the
 * app's internal list pages, not a bespoke static table. The filter row is
 * rendered natively by the grid itself now (DropdownFilter widgets on each
 * DataColumn) rather than a separate hand-rolled <form> above the table —
 * see GatewayStatusListWidget::render() and
 * App\Invoice\Inv\Widget\InvsColumnBuilder::buildColumns()'s filterClient
 * column, the precedent this reuses.
 *
 * @var GatewayStatusListWidget $gatewayStatusGrid
 * @var AssetManager $assetManager
 */

// The grid's DropdownFilter widgets use useInlineJs(false) (CSP script-src
// 'self' blocks the vendor's default inline onChange handler -- see
// docs/YII_DATAVIEW_DROPDOWNFILTER_UPSTREAM_FIX.md), which needs this
// asset's delegated change listener registered. Mirrors
// resources/views/invoice/inv/index.php's own registration exactly.
$assetManager->register(YiiDataViewNoInlineJsAsset::class);
?>
<?= Html::openTag('section', ['class' => 'py-5']); ?>
    <?= Html::openTag('div', ['class' => 'container']); ?>
        <?= Html::tag('h1', 'Payment Gateway Coverage', ['class' => 'display-5 fw-bold mb-3'])->render(); ?>
        <?= Html::tag(
            'p',
            'Every payment gateway this project supports, the SDK version currently pinned, and when it was'
                . ' last tested — sandbox automatically via a weekly check, live only ever by hand. We\'re'
                . ' building out coverage region by region, starting with Asia.',
            ['class' => 'lead mb-4'],
        )->render(); ?>

        <?= $gatewayStatusGrid->render(); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('section'); ?>
