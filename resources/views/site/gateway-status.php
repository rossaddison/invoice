<?php

declare(strict_types=1);

use App\Invoice\PaymentInformation\GatewayStatus\Widget\GatewayStatusListWidget;
use Yiisoft\Html\Html;

/**
 * Public payment-gateway coverage table. See
 * docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md for how this data is generated
 * and kept up to date. Sorting, pagination, and the region filter below are
 * all real (GatewayStatusListWidget/GridView) — the same grid mechanics as
 * the app's internal list pages, not a bespoke static table.
 *
 * @var GatewayStatusListWidget $gatewayStatusGrid
 * @var list<string> $regionOptions
 * @var string $selectedRegion
 */
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

        <?= Html::openTag('form', ['method' => 'get', 'class' => 'row g-2 align-items-end mb-4']); ?>
            <?= Html::openTag('div', ['class' => 'col-auto']); ?>
                <?= Html::tag('label', 'Region', ['for' => 'region', 'class' => 'form-label mb-1'])->render(); ?>
                <?= Html::openTag('select', ['name' => 'region', 'id' => 'region', 'class' => 'form-select']); ?>
                    <?= Html::tag('option', 'All regions', ['value' => ''])->render(); ?>
                    <?php foreach ($regionOptions as $region): ?>
                        <?= Html::tag(
                            'option',
                            ucwords($region),
                            array_filter(['value' => $region, 'selected' => $region === $selectedRegion]),
                        )->render(); ?>
                    <?php endforeach; ?>
                <?= Html::closeTag('select'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col-auto']); ?>
                <?= Html::tag('button', 'Filter', ['type' => 'submit', 'class' => 'btn btn-primary'])->render(); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('form'); ?>

        <?= $gatewayStatusGrid->render(); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('section'); ?>
