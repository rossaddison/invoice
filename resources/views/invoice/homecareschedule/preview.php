<?php

declare(strict_types=1);

use App\Invoice\HomeCareSchedule\ParsedSchedule;
use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var string $csrf
 * @var ParsedSchedule $schedule
 * @var string $text
 * @var array{runs: array<string, int|null>, workers: array<string, int|null>} $suggested
 * @var array<int, string> $runs
 * @var array<int, string> $workers
 * @var array<string, bool> $existing
 * @var array<int, bool> $startless
 * @var array<array-key, string> $primaries
 * @var string $alert
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$runNames = $schedule->runNames();
$crewNames = $schedule->crewNames();

echo new Form()->post($urlGenerator->generate('homecareschedule/save'))->csrf($csrf)->open();
echo H::hiddenInput('year', (string) $schedule->year)->render();
echo H::hiddenInput('month', (string) $schedule->month)->render();
echo H::hiddenInput('schedule', $text)->render();

if ([] !== $schedule->warnings) {
    echo H::openTag('div', ['class' => 'alert alert-warning']);
     echo H::tag('strong', H::encode($translator->translate('homecare.schedule.warnings')));
     echo H::openTag('ul', ['class' => 'mb-0']);
      foreach ($schedule->warnings as $warning) {
          echo H::tag('li', H::encode($warning));
      }
     echo H::closeTag('ul');
    echo H::closeTag('div');
}

echo H::openTag('div', ['class' => 'row']);
 // Runs
 echo H::openTag('div', ['class' => 'col-12 col-lg-6']);
  echo H::openTag('div', ['class' => 'card mb-3']);
   echo H::tag('div', H::encode($translator->translate('homecare.schedule.runs.found')), ['class' => 'card-header']);
   echo H::openTag('div', ['class' => 'card-body']);
    echo H::tag('p', $translator->translate('homecare.schedule.runs.hint'), ['class' => 'text-muted']);
    foreach ($runNames as $i => $name) {
        $matched = $suggested['runs'][$name] ?? null;
        echo H::openTag('div', ['class' => 'mb-2']);
         echo H::tag('label', H::encode($name), ['class' => 'form-label fw-bold', 'for' => 'run-' . $i]);
         echo H::select('run[' . $i . ']')
             ->id('run-' . $i)
             ->optionsData(['new' => '➕ ' . $translator->translate('homecare.schedule.create.run')] + $runs)
             ->prompt('— ' . $translator->translate('homecare.schedule.skip.run') . ' —')
             ->value(null === $matched ? '' : (string) $matched)
             ->addAttributes(['class' => 'form-select'])
             ->render();
         if (null !== $matched && ($startless[$matched] ?? false)) {
             echo H::tag('div', H::encode($translator->translate('homecare.schedule.no.start.postcode')), ['class' => 'form-text text-warning']);
         }
        echo H::closeTag('div');
    }
    echo H::openTag('div', ['class' => 'mt-3']);
     echo H::tag('label', $translator->translate('homecare.schedule.new.run.category'), ['class' => 'form-label', 'for' => 'new-run-category']);
     echo H::select('new_run_category_primary_id')
         ->id('new-run-category')
         ->optionsData($primaries)
         ->addAttributes(['class' => 'form-select'])
         ->render();
    echo H::closeTag('div');
   echo H::closeTag('div');
  echo H::closeTag('div');
 echo H::closeTag('div');

 // Workers
 echo H::openTag('div', ['class' => 'col-12 col-lg-6']);
  echo H::openTag('div', ['class' => 'card mb-3']);
   echo H::tag('div', H::encode($translator->translate('homecare.schedule.crew.found')), ['class' => 'card-header']);
   echo H::openTag('div', ['class' => 'card-body']);
    foreach ($crewNames as $i => $name) {
        $matched = $suggested['workers'][$name] ?? null;
        echo H::openTag('div', ['class' => 'mb-2']);
         echo H::tag('label', H::encode($name), ['class' => 'form-label fw-bold', 'for' => 'worker-' . $i]);
         echo H::select('worker[' . $i . ']')
             ->id('worker-' . $i)
             ->optionsData(['new' => '➕ ' . $translator->translate('homecare.schedule.create.worker')] + $workers)
             ->prompt('— ' . $translator->translate('homecare.schedule.skip.worker') . ' —')
             ->value(null === $matched ? '' : (string) $matched)
             ->addAttributes(['class' => 'form-select'])
             ->render();
        echo H::closeTag('div');
    }
   echo H::closeTag('div');
  echo H::closeTag('div');
 echo H::closeTag('div');
echo H::closeTag('div');

// The days as read
echo H::openTag('div', ['class' => 'card mb-3']);
 echo H::tag('div', H::encode($translator->translate('homecare.schedule.days.found')), ['class' => 'card-header']);
 echo H::openTag('div', ['class' => 'card-body']);
  echo H::openTag('table', ['class' => 'table table-sm table-striped']);
   echo H::openTag('tbody');
    foreach ($schedule->entries as $entry) {
        echo H::openTag('tr');
         echo H::tag('td', H::encode(new DateTimeImmutable($entry->date)->format('D j M')));
         if ($entry->isOpenDay()) {
             echo H::tag('td', H::encode($translator->translate('homecare.schedule.open.day')), ['class' => 'text-muted', 'colspan' => '2']);
         } else {
             echo H::tag('td', H::encode((string) $entry->runName));
             echo H::tag('td', H::encode(implode(', ', $entry->allCrewNames()) . ($entry->crewFromDitto ? ' (")' : '')));
         }
         echo H::tag('td', ($existing[$entry->date] ?? false)
             ? H::tag('span', H::encode($translator->translate('homecare.schedule.replaces')), ['class' => 'badge text-bg-warning'])->render()
             : '')->encode(false);
        echo H::closeTag('tr');
    }
   echo H::closeTag('tbody');
  echo H::closeTag('table');
 echo H::closeTag('div');
echo H::closeTag('div');

echo H::submitButton($translator->translate('homecare.schedule.save'))->addAttributes(['class' => 'btn btn-success me-2'])->render();
echo H::a($translator->translate('cancel'), $urlGenerator->generate('homecareschedule/index', [], ['year' => (string) $schedule->year, 'month' => (string) $schedule->month]), ['class' => 'btn btn-outline-secondary'])->render();
echo new Form()->close();
