<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Inv\Inv;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $trashed
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $alert
 * @var string $csrf
 */

echo $alert;

$backButton = new A()
    ->addClass('btn btn-secondary mb-3 bi bi-arrow-left me-1')
    ->href($urlGenerator->generate('inv/index'))
    ->content($translator->translate('invoices'))
    ->render();

echo Html::openTag('div', ['class' => 'container-fluid']);
 echo Html::openTag('div', ['class' => 'd-flex align-items-center mb-3 gap-2']);
  echo Html::tag('h4', $translator->translate('delete.invoice.trash'),
          ['class' => 'mb-0 me-3']);
  echo $backButton;
 echo Html::closeTag('div');

 $rows = iterator_to_array($trashed->getIterator());

 if (empty($rows)) {
     echo Html::tag('div',
         $translator->translate('delete.invoice.trash.empty'),
         ['class' => 'alert alert-info']
     );
 } else {
     echo Html::openTag('div', ['class' => 'table-responsive']);
      echo Html::openTag('table',
            ['class' => 'table table-hover table-bordered align-middle']);
       echo Html::openTag('thead', ['class' => 'table-dark']);
        echo Html::openTag('tr');
         echo Html::tag('th', '#');
         echo Html::tag('th', $translator->translate('invoice') . ' '
                 . $translator->translate('number'));
         echo Html::tag('th', $translator->translate('client'));
         echo Html::tag('th', $translator->translate('delete.invoice.date.soft.deleted'));
         echo Html::tag('th', $translator->translate('delete.invoice.restore'));
        echo Html::closeTag('tr');
       echo Html::closeTag('thead');
       echo Html::openTag('tbody');
        /** @var Inv $inv */
        foreach ($rows as $inv) {
            $id         = $inv->reqId();
            $numberRaw  = $inv->getNumber();
            $number     = ($numberRaw !== null && $numberRaw !== '') ? $numberRaw : '—';
            $client     = $inv->getClient()?->getClientFullName() ?? '—';
            $deletedAt  = $inv->getDeletedAt()?->format('Y-m-d H:i') ?? '—';
            $modalId    = 'restore-inv-' . $id;

            echo Html::openTag('tr');
             echo Html::tag('td', (string) $id);
             echo Html::tag('td', Html::encode($number));
             echo Html::tag('td', Html::encode($client));
             echo Html::tag('td', Html::encode($deletedAt));
             echo Html::openTag('td');
              echo new A()
                ->addAttributes([
                    'data-bs-toggle' => 'modal',
                    'href'           => '#' . $modalId,
                    'style'          => 'text-decoration:none',
                ])
                ->addClass('btn btn-success btn-sm bi bi-arrow-counterclockwise me-1')
                ->content($translator->translate('delete.invoice.restore'))
                ->render();
             echo Html::closeTag('td');
            echo Html::closeTag('tr');

            // Restore confirmation modal for this row
            echo Html::openTag('div', [
                'id' => $modalId,
                'class' => 'modal',
                'tabindex' => '-1']);
             echo Html::openTag('div', ['class' => 'modal-dialog']);
              echo Html::openTag('div', ['class' => 'modal-content']);
               echo Html::openTag('div', ['class' => 'modal-header']);
                echo Html::tag('h5',
                    $translator->translate('delete.invoice.restore'),
                        ['class' => 'modal-title']);
                echo Html::tag('button', '', [
                    'type'            => 'button',
                    'class'           => 'btn-close',
                    'data-bs-dismiss' => 'modal',
                    'aria-label'      => 'Close',
                ]);
               echo Html::closeTag('div');
               echo Html::openTag('div', ['class' => 'modal-body']);
                echo Html::tag('div',
                    $translator->translate('delete.invoice.restore.warning'),
                    ['class' => 'alert alert-info']
                );
                echo Html::openTag('form', [
                    'action' => $urlGenerator->generate('inv/restore',
                            ['id' => $id]),
                    'method' => 'POST',
                ]);
                 echo Html::hiddenInput('_csrf', $csrf);
                 echo Html::openTag('div', ['class' => 'btn-group']);
                  echo Html::submitButton(' ' .
                        $translator->translate('delete.invoice.restore'),
                    ['class' => 'btn btn-success bi bi-arrow-counterclockwise']
                  );
                  echo New A()
                    ->addAttributes(['href' => '#', 'data-bs-dismiss' => 'modal'])
                    ->addClass('btn btn-secondary bi bi-x-lg')
                    ->content(' ' . $translator->translate('cancel'))
                    ->render();
                 echo Html::closeTag('div');
                echo Html::closeTag('form');
               echo Html::closeTag('div');
              echo Html::closeTag('div');
             echo Html::closeTag('div');
            echo Html::closeTag('div');
        }
       echo Html::closeTag('tbody');
      echo Html::closeTag('table');
     echo Html::closeTag('div');
 }

echo Html::closeTag('div');
