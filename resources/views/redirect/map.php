<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var string $mapSvg raw, trusted SVG markup loaded server-side from
 *     resources/redirect-map/world-map.svg — never user input, safe to
 *     echo directly.
 * @var string $countryStyle plain CSS rule body (no <style> tags) built
 *     by RedirectController::buildCountryStyle().
 * @var array<string, int> $counts country_code => click count, already
 *     sorted by insertion order from the repository; re-sorted below by
 *     count descending for the "top countries" list.
 */
$sortedCounts = $counts;
arsort($sortedCounts);
$total = array_sum($counts);
?>
<?= Html::openTag('div', ['class' => 'row']); ?>
    <?= Html::openTag('div', ['class' => 'col-12']); ?>
        <?= Html::openTag('div', ['class' => 'card']); ?>
            <?= Html::openTag('div', ['class' => 'card-header']); ?>
                <?= Html::tag('i', '', ['class' => 'bi bi-globe']); ?>
                GitHub link clicks by country
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'card-body']); ?>
                <?= Html::openTag('p', ['class' => 'text-secondary small']); ?>
                    All-time clicks recorded through the homepage's tracked
                    <?= Html::tag('code', '/go/github'); ?>
                    redirect —
                    <?= Html::encode((string) $total); ?>
                    total, across
                    <?= Html::encode((string) count($counts)); ?>
                    countries. Only the resolved country is stored, never
                    the visitor's IP address.
                <?= Html::closeTag('p'); ?>

                <?= Html::openTag('style'); ?>
                    <?= $countryStyle; ?>
                <?= Html::closeTag('style'); ?>

                <?= Html::openTag('div', ['class' => 'text-center mb-3', 'style' => 'overflow-x: auto']); ?>
                    <?= $mapSvg; ?>
                <?= Html::closeTag('div'); ?>

                <?php if ($sortedCounts !== []) { ?>
                    <?= Html::openTag('h3', ['class' => 'h6']); ?>
                        Top countries
                    <?= Html::closeTag('h3'); ?>
                    <?= Html::openTag('table', ['class' => 'table table-sm w-auto']); ?>
                        <?= Html::openTag('thead'); ?>
                            <?= Html::openTag('tr'); ?>
                                <?= Html::tag('th', 'Country code'); ?>
                                <?= Html::tag('th', 'Clicks'); ?>
                            <?= Html::closeTag('tr'); ?>
                        <?= Html::closeTag('thead'); ?>
                        <?= Html::openTag('tbody'); ?>
                            <?php foreach ($sortedCounts as $code => $count) { ?>
                                <?= Html::openTag('tr'); ?>
                                    <?= Html::tag('td', strtoupper($code)); ?>
                                    <?= Html::tag('td', (string) $count); ?>
                                <?= Html::closeTag('tr'); ?>
                            <?php } ?>
                        <?= Html::closeTag('tbody'); ?>
                    <?= Html::closeTag('table'); ?>
                <?php } else { ?>
                    <?= Html::openTag('p', ['class' => 'text-secondary']); ?>
                        No tracked clicks recorded yet.
                    <?= Html::closeTag('p'); ?>
                <?php } ?>

                <?= Html::openTag('p', ['class' => 'text-secondary small mb-0 mt-3']); ?>
                    Map: "Simple World Map" by Al MacDonald, edited by Fritz Lekschas —
                    <?= Html::a('github.com/flekschas/simple-world-map', 'https://github.com/flekschas/simple-world-map', ['target' => '_blank', 'rel' => 'noopener']); ?>
                    , licensed
                    <?= Html::a('CC BY-SA 3.0', 'https://creativecommons.org/licenses/by-sa/3.0/', ['target' => '_blank', 'rel' => 'noopener']); ?>
                    . Unmodified except for the fill colors above.
                <?= Html::closeTag('p'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
