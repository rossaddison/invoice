<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\HomeCareRunSheet\HomeCareRunSheet;
use App\Infrastructure\Persistence\HomeCareRunSheetItem\HomeCareRunSheetItem;
use App\Invoice\Enum\HomeCareRunSheetStatus;
use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var string $csrf
 * @var HomeCareRunSheet $runSheet
 * @var array<int, array{item: HomeCareRunSheetItem, invoice_number: string,
 *     client_name: string, expected_worker_name: string, detected_worker_name: string}> $rows
 * @var string $alert
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

echo H::openTag('div', ['class' => 'row']);
 echo H::openTag('div', ['class' => 'col-12']);
  echo H::openTag('div', ['class' => 'card']);
   echo H::openTag('div', ['class' => 'card-header']);
    echo H::encode($translator->translate('homecare.runsheet.review.title'));
   echo H::closeTag('div');
   echo H::openTag('div', ['class' => 'card-body']);
    echo H::tag('p', $translator->translate('homecare.runsheet.review.description'),
        ['class' => 'text-muted']);
    echo H::tag('p', $translator->translate('homecare.runsheet.review.status')
        . ': ' . $runSheet->getStatus()->value, ['class' => 'fw-bold']);

    if ($runSheet->getSpreadsheetFileName() !== null) {
        echo H::a(H::encode($translator->translate('homecare.runsheet.review.download')),
            $urlGenerator->generate('homecarerunsheet/download', ['id' => (string) $runSheet->reqId()]),
            ['class' => 'btn btn-outline-secondary btn-sm mb-3'])->encode(false)->render();
    }

    if ($runSheet->getStatus() === HomeCareRunSheetStatus::Exported
        || $runSheet->getStatus() === HomeCareRunSheetStatus::Scanned
    ) {
        echo new Form()
            ->post($urlGenerator->generate('homecarerunsheet/upload', ['id' => (string) $runSheet->reqId()]))
            ->csrf($csrf)
            ->enctypeMultipartFormData()
            ->addAttributes(['class' => 'mb-3'])
            ->open();
        echo H::tag('label', $translator->translate('homecare.runsheet.review.upload.label'),
            ['class' => 'form-label d-block']);
        echo H::input('file', 'scan', null, ['class' => 'form-control d-inline-block w-auto', 'accept' => 'image/*'])->render();
        echo H::submitButton($translator->translate('homecare.runsheet.review.upload.button'))
            ->addAttributes(['class' => 'btn btn-primary ms-2'])
            ->render();
        echo new Form()->close();
    }

    $hasBeenRead = $runSheet->getStatus() === HomeCareRunSheetStatus::PendingReview
        || $runSheet->getStatus() === HomeCareRunSheetStatus::Applied;

    if ($hasBeenRead && $rows === []) {
        echo H::tag('p', $translator->translate('homecare.runsheet.review.no.changes'));
    } elseif ($hasBeenRead) {
        echo new Form()
            ->post($urlGenerator->generate('homecarerunsheet/save', ['id' => (string) $runSheet->reqId()]))
            ->csrf($csrf)
            ->open();

        echo H::openTag('table', ['class' => 'table table-striped table-sm']);
         echo H::openTag('thead');
          echo H::openTag('tr');
           echo H::tag('th', $translator->translate('homecare.runsheet.review.accept'));
           echo H::tag('th', $translator->translate('homecare.runsheet.review.invoice'));
           echo H::tag('th', $translator->translate('homecare.runsheet.review.client'));
           echo H::tag('th', $translator->translate('homecare.runsheet.review.expected.worker'));
           echo H::tag('th', $translator->translate('homecare.runsheet.review.detected.worker'));
           echo H::tag('th', $translator->translate('homecare.runsheet.review.completed'));
           echo H::tag('th', $translator->translate('homecare.runsheet.review.reason'));
          echo H::closeTag('tr');
         echo H::closeTag('thead');
         echo H::openTag('tbody');
          foreach ($rows as $row) {
           $item = $row['item'];
           echo H::openTag('tr');
            echo H::tag('td', H::checkbox('accepted[]', (string) $item->reqId())
                ->checked($item->getAccepted())
                ->render())->encode(false);
            echo H::tag('td', $row['invoice_number']);
            echo H::tag('td', $row['client_name']);
            echo H::tag('td', $row['expected_worker_name']);
            echo H::tag('td', $row['detected_worker_name']);
            echo H::tag('td', $item->getDetectedCompleted() === true ? '✅' : '❌')->encode(false);
            echo H::tag('td', $item->getDetectedReasonCode() ?? '');
           echo H::closeTag('tr');
          }
         echo H::closeTag('tbody');
        echo H::closeTag('table');

        echo H::submitButton($translator->translate('homecare.runsheet.review.save'))
            ->addAttributes(['class' => 'btn btn-primary'])
            ->render();
        echo new Form()->close();
    }

    if ($runSheet->getStatus() === HomeCareRunSheetStatus::PendingReview) {
        echo new Form()
            ->post($urlGenerator->generate('homecarerunsheet/apply', ['id' => (string) $runSheet->reqId()]))
            ->csrf($csrf)
            ->addAttributes(['class' => 'mt-3'])
            ->open();
        echo H::tag('p', $translator->translate('homecare.runsheet.review.apply.description'),
            ['class' => 'text-muted']);
        echo H::submitButton($translator->translate('homecare.runsheet.review.apply'))
            ->addAttributes(['class' => 'btn btn-success'])
            ->render();
        echo new Form()->close();
    }
   echo H::closeTag('div');
  echo H::closeTag('div');
 echo H::closeTag('div');
echo H::closeTag('div');
