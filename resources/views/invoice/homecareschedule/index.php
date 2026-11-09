<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Invoice\Helpers\GoogleMapsLink;
use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var string $csrf
 * @var int $year
 * @var int $month
 * @var list<array{date: string, run: CategorySecondary|null, crew: list<string>}> $days
 * @var string $alert
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$previous = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month))->modify('-1 month');
$next = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month))->modify('+1 month');

echo H::openTag('div', ['class' => 'row']);
 echo H::openTag('div', ['class' => 'col-12 col-lg-5']);
  echo H::openTag('div', ['class' => 'card mb-3']);
   echo H::tag('div', H::encode($translator->translate('homecare.schedule.paste')), ['class' => 'card-header']);
   echo H::openTag('div', ['class' => 'card-body']);
    echo H::tag('p', $translator->translate('homecare.schedule.paste.hint'), ['class' => 'text-muted']);
    echo new Form()->post($urlGenerator->generate('homecareschedule/preview'))->csrf($csrf)->open();
    echo H::openTag('div', ['class' => 'row g-2 mb-2']);
     echo H::openTag('div', ['class' => 'col-6']);
      echo H::tag('label', $translator->translate('homecare.schedule.year'), ['class' => 'form-label', 'for' => 'schedule-year']);
      echo H::input('number', 'year', (string) $year)->id('schedule-year')->addAttributes(['class' => 'form-control', 'min' => '2000', 'max' => '2100'])->render();
     echo H::closeTag('div');
     echo H::openTag('div', ['class' => 'col-6']);
      echo H::tag('label', $translator->translate('homecare.schedule.month'), ['class' => 'form-label', 'for' => 'schedule-month']);
      echo H::input('number', 'month', (string) $month)->id('schedule-month')->addAttributes(['class' => 'form-control', 'min' => '1', 'max' => '12'])->render();
     echo H::closeTag('div');
    echo H::closeTag('div');
    echo H::textarea('schedule')->id('schedule-text')->addAttributes(['class' => 'form-control mb-2', 'rows' => '14', 'spellcheck' => 'false'])->render();
    echo H::submitButton($translator->translate('homecare.schedule.read'))->addAttributes(['class' => 'btn btn-primary'])->render();
    echo new Form()->close();
   echo H::closeTag('div');
  echo H::closeTag('div');
 echo H::closeTag('div');

 echo H::openTag('div', ['class' => 'col-12 col-lg-7']);
  echo H::openTag('div', ['class' => 'card']);
   echo H::openTag('div', ['class' => 'card-header d-flex justify-content-between align-items-center']);
    echo H::a('‹', $urlGenerator->generate('homecareschedule/index', [], ['year' => $previous->format('Y'), 'month' => $previous->format('n')]), ['class' => 'btn btn-sm btn-outline-secondary', 'aria-label' => $translator->translate('homecare.schedule.previous.month')])->render();
    echo H::tag('strong', H::encode(new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month))->format('F Y')));
    echo H::a('›', $urlGenerator->generate('homecareschedule/index', [], ['year' => $next->format('Y'), 'month' => $next->format('n')]), ['class' => 'btn btn-sm btn-outline-secondary', 'aria-label' => $translator->translate('homecare.schedule.next.month')])->render();
   echo H::closeTag('div');
   echo H::openTag('div', ['class' => 'card-body']);
    if ([] === $days) {
        echo H::tag('p', $translator->translate('homecare.schedule.empty'), ['class' => 'text-muted mb-0']);
    } else {
        echo H::openTag('table', ['class' => 'table table-striped table-sm align-middle']);
         echo H::openTag('thead');
          echo H::openTag('tr');
           echo H::tag('th', $translator->translate('homecare.schedule.date'));
           echo H::tag('th', $translator->translate('homecare.current.run'));
           echo H::tag('th', $translator->translate('homecare.schedule.crew'));
           echo H::tag('th', $translator->translate('run.start.postcode'));
           echo H::tag('th', '');
          echo H::closeTag('tr');
         echo H::closeTag('thead');
         echo H::openTag('tbody');
          foreach ($days as $day) {
              $postcode = $day['run']?->getStartPostcode();
              echo H::openTag('tr');
               echo H::tag('td', H::encode(new DateTimeImmutable($day['date'])->format('D j M')));
               echo H::tag('td', H::encode($day['run']?->getName() ?? ''));
               echo H::tag('td', H::encode(implode(', ', $day['crew'])));
               echo H::tag('td', null === $postcode || '' === $postcode
                   ? ''
                   : H::a('🧭 ' . $postcode, GoogleMapsLink::directionsTo($postcode), [
                       'target' => '_blank',
                       'rel' => 'noopener noreferrer',
                       'title' => $translator->translate('run.start.navigate'),
                   ])->render())->encode(false);
               echo H::openTag('td');
                echo new Form()->post($urlGenerator->generate('homecareschedule/clear'))->csrf($csrf)->open();
                echo H::hiddenInput('date', $day['date'])->render();
                echo H::submitButton('✕')->addAttributes(['class' => 'btn btn-sm btn-outline-danger', 'aria-label' => $translator->translate('homecare.schedule.clear'), 'title' => $translator->translate('homecare.schedule.clear')])->render();
                echo new Form()->close();
               echo H::closeTag('td');
              echo H::closeTag('tr');
          }
         echo H::closeTag('tbody');
        echo H::closeTag('table');
    }
   echo H::closeTag('div');
  echo H::closeTag('div');
 echo H::closeTag('div');
echo H::closeTag('div');
