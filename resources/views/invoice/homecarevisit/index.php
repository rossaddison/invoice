<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\HomeCareVisit\HomeCareVisit;
use Yiisoft\Html\Html as H;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var array<int, array{visit: HomeCareVisit, client_name: string}> $visits
 * @var string $alert
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$outcomeBadge = static function (string $outcome): string {
    $class = match ($outcome) {
        'generated' => 'text-bg-success',
        'not_eligible' => 'text-bg-secondary',
        'contact_us' => 'text-bg-danger',
        default => 'text-bg-warning',
    };
    return '<span class="badge ' . $class . '">' . H::encode($outcome) . '</span>';
};

echo H::openTag('div', ['class' => 'row']);
 echo H::openTag('div', ['class' => 'col-12']);
  echo H::openTag('div', ['class' => 'card']);
   echo H::openTag('div', ['class' => 'card-header']);
    echo H::encode($translator->translate('homecare.visit.log.title'));
   echo H::closeTag('div');
   echo H::openTag('div', ['class' => 'card-body']);
    echo H::tag('p', H::encode($translator->translate('homecare.visit.log.description')),
        ['class' => 'text-muted']);

    if ($visits === []) {
        echo H::tag('p', H::encode($translator->translate('no.records')));
    } else {
        echo H::openTag('table', ['class' => 'table table-striped table-sm']);
         echo H::openTag('thead');
          echo H::openTag('tr');
           echo H::tag('th', H::encode($translator->translate('client')));
           echo H::tag('th', H::encode($translator->translate('homecare.visit.log.visited.at')));
           echo H::tag('th', H::encode($translator->translate('homecare.visit.log.outcome')));
           echo H::tag('th', H::encode($translator->translate('homecare.visit.log.invoice')));
           echo H::tag('th', H::encode($translator->translate('homecare.visit.log.reason')));
           echo H::tag('th', H::encode($translator->translate('datetime.immutable.time.created')));
          echo H::closeTag('tr');
         echo H::closeTag('thead');
         echo H::openTag('tbody');
          foreach ($visits as $row) {
           $visit = $row['visit'];
           echo H::openTag('tr');
            echo H::tag('td', H::encode($row['client_name']));
            echo H::tag('td', H::encode($visit->getVisitedAt()?->format('Y-m-d') ?? ''));
            echo H::tag('td', $outcomeBadge($visit->getOutcome()), ['encode' => false]);
            $invoiceId = $visit->getInvoiceId();
            echo H::tag('td', $invoiceId !== null
                ? H::a('#' . $invoiceId,
                    $urlGenerator->generate('inv/view', ['id' => $invoiceId]))->render()
                : '', ['encode' => false]);
            echo H::tag('td', H::encode($visit->getReason() ?? ''));
            echo H::tag('td', H::encode($visit->getCreatedAt()->format('Y-m-d H:i:s')));
           echo H::closeTag('tr');
          }
         echo H::closeTag('tbody');
        echo H::closeTag('table');
    }
   echo H::closeTag('div');
  echo H::closeTag('div');
 echo H::closeTag('div');
echo H::closeTag('div');
