<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\HomeCareRunSheet\HomeCareRunSheet;
use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var string $csrf
 * @var array<int, HomeCareRunSheet> $runSheets
 * @var array<int, string> $categoryNames
 * @var array<int, int> $itemCounts
 * @var array<int, string> $optionsCategorySecondary
 * @var string $today
 * @var string $alert
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

echo H::openTag('div', ['class' => 'row']);
echo H::openTag('div', ['class' => 'col-12']);
echo H::openTag('div', ['class' => 'card mb-3']);
echo H::openTag('div', ['class' => 'card-header']);
echo H::encode($translator->translate('homecare.runsheet.index.title'));
echo H::closeTag('div');
echo H::openTag('div', ['class' => 'card-body']);
echo H::tag(
    'p',
    $translator->translate('homecare.runsheet.index.description'),
    ['class' => 'text-muted']
);

echo new Form()
    ->post($urlGenerator->generate('homecarerunsheet/export'))
    ->csrf($csrf)
    ->open();

echo H::openTag('div', ['class' => 'row g-2 align-items-end']);
echo H::openTag('div', ['class' => 'col-auto']);
echo H::tag('label', $translator->translate('homecare.current.run'), ['class' => 'form-label']);
echo H::select('category_secondary_id')
    ->optionsData($optionsCategorySecondary)
    ->prompt($translator->translate('none'))
    ->addAttributes(['class' => 'form-select'])
    ->render();
echo H::closeTag('div');
echo H::openTag('div', ['class' => 'col-auto']);
echo H::tag('label', $translator->translate('homecare.runsheet.index.run.date'), ['class' => 'form-label']);
echo H::input('date', 'run_date', $today)->addAttributes(['class' => 'form-control'])->render();
echo H::closeTag('div');
echo H::openTag('div', ['class' => 'col-auto']);
echo H::submitButton($translator->translate('homecare.runsheet.index.export'))
    ->addAttributes(['class' => 'btn btn-primary'])
    ->render();
echo H::closeTag('div');
echo H::closeTag('div');
echo new Form()->close();
echo H::closeTag('div');
echo H::closeTag('div');

echo H::openTag('div', ['class' => 'card']);
echo H::openTag('div', ['class' => 'card-header']);
echo H::encode($translator->translate('homecare.runsheet.index.recent'));
echo H::closeTag('div');
echo H::openTag('div', ['class' => 'card-body']);
if ($runSheets === []) {
    echo H::tag('p', $translator->translate('no.records'));
} else {
    echo H::openTag('table', ['class' => 'table table-striped table-sm']);
    echo H::openTag('thead');
    echo H::openTag('tr');
    echo H::tag('th', $translator->translate('id'));
    echo H::tag('th', $translator->translate('homecare.current.run'));
    echo H::tag('th', $translator->translate('homecare.runsheet.index.run.date'));
    echo H::tag('th', $translator->translate('homecare.runsheet.review.status'));
    echo H::tag('th', $translator->translate('homecare.runsheet.index.items'));
    echo H::closeTag('tr');
    echo H::closeTag('thead');
    echo H::openTag('tbody');
    foreach ($runSheets as $runSheet) {
        $id = $runSheet->reqId();
        echo H::openTag('tr');
        echo H::tag('td', H::a(
            '#' . $id,
            $urlGenerator->generate('homecarerunsheet/review', ['id' => (string) $id])
        )->render())
            ->encode(false);
        echo H::tag('td', $categoryNames[$id] ?? '');
        echo H::tag('td', $runSheet->getRunDate()?->format('Y-m-d') ?? '');
        echo H::tag('td', $runSheet->getStatus()->value);
        echo H::tag('td', (string) ($itemCounts[$id] ?? 0));
        echo H::closeTag('tr');
    }
    echo H::closeTag('tbody');
    echo H::closeTag('table');
}
echo H::closeTag('div');
echo H::closeTag('div');
echo H::closeTag('div');
echo H::closeTag('div');
