<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Public list of every HMRC Making Tax Digital API this application
 * currently integrates with -- see SiteController::hmrcApiStatus()'s
 * own docblock. Single source of truth is HmrcApiCatalogue::all();
 * this page never keeps its own copy of that data, just renders it,
 * so it can't drift out of sync with what the app actually requests.
 *
 * @var array<string, array{
 *     name: string,
 *     scopes: list<string>,
 *     needs: string,
 *     serviceName: string,
 *     version: string,
 * }> $apis
 * @var string $asOfDate
 */
?>
<?= Html::openTag('section', ['class' => 'py-5']); ?>
<?= Html::openTag('div', ['class' => 'container']); ?>
<?= Html::tag(
    'h1',
    'HMRC API Status',
    ['class' => 'display-5 fw-bold mb-3'],
)->render(); ?>
<?= Html::tag(
    'p',
    'Every HMRC Making Tax Digital API this application currently'
        . ' integrates with, as of ' . Html::encode($asOfDate) . '.',
    ['class' => 'lead mb-4'],
)->render(); ?>

<?= Html::openTag('div', ['class' => 'table-responsive']); ?>
<?= Html::openTag('table', ['class' => 'table table-hover align-middle']); ?>
<?= Html::openTag('thead', ['class' => 'table-dark']); ?>
<?= Html::openTag('tr'); ?>
<?php foreach (['API', 'Version', 'Scopes', 'Needs'] as $col): ?>
    <?= Html::tag('th', $col)->render(); ?>
<?php endforeach; ?>
<?= Html::closeTag('tr'); ?>
<?= Html::closeTag('thead'); ?>
<?= Html::openTag('tbody'); ?>
<?php foreach ($apis as $api): ?>
    <?= Html::openTag('tr'); ?>
    <?= Html::tag('td', $api['name'], ['data-label' => 'API'])
        ->render(); ?>
    <?= Html::tag('td', $api['version'], ['data-label' => 'Version'])
        ->render(); ?>
    <?php $scopes = implode(' ', $api['scopes']); ?>
    <?= Html::tag(
        'td',
        $scopes,
        ['class' => 'small text-muted font-monospace', 'data-label' => 'Scopes'],
    )->render(); ?>
    <?php $needs = strtoupper($api['needs']); ?>
    <?= Html::tag('td', $needs, ['data-label' => 'Needs'])->render(); ?>
    <?= Html::closeTag('tr'); ?>
<?php endforeach; ?>
<?= Html::closeTag('tbody'); ?>
<?= Html::closeTag('table'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('section'); ?>
