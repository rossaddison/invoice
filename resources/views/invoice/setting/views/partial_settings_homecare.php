<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\H6;
use Yiisoft\Html\Tag\Input;
use Yiisoft\Html\Tag\Option;

/**
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
* @var array $hidden_inv_columns
* @var array $hidden_inv_guest_columns
* @var array $category_secondaries
* @var array $body
*/

// key => translation key. Worker, Amount/Total, and the do_not_send badge
// are deliberately absent — always visible in HomeCare mode, never
// hideable. See InvsColumnBuilder::buildColumns() for where each key is
// actually applied.
$toggleableColumns = [
    'workflow_type'    => 'homecare.column.workflow.type',
    'pdf_email'        => 'homecare.column.pdf.email',
    'family_name'      => 'family.name',
    'year_month'       => 'homecare.column.year.month',
    'quick_pay'        => 'homecare.column.quick.pay',
    'client_active'    => 'homecare.column.client.active',
    'credit_note'      => 'credit.invoice.for.invoice',
    'client'           => 'client',
    'client_number'    => 'client.number',
    'client_address_1' => 'street.address',
    'client_address_2' => 'street.address.2',
    'client_group'     => 'client.group',
    'time_created'     => 'datetime.immutable.time.created',
    'delivery_add'     => 'homecare.column.delivery.add',
    'delete'           => 'delete',
];

echo H::openTag('div', ['class' => 'row']); //1
 echo H::openTag('div', ['class' => 'col-12 col-md-8 offset-md-2']); //2

  echo H::openTag('div', ['class' => 'alert alert-info mb-3']); //android-rotate-tip
   echo H::tag('strong', H::encode($translator->translate('homecare.mobile.rotate.tip.title')));
   echo ' ' . H::encode($translator->translate('homecare.mobile.rotate.tip.body'));
  echo H::closeTag('div'); //android-rotate-tip

  echo H::a(
   '📋 ' . $translator->translate('homecare.visit.log.title'),
   $urlGenerator->generate('homecarevisit/index'),
   ['class' => 'btn btn-outline-secondary btn-sm mb-3']
  );

  echo H::openTag('div', ['class' => 'card mb-3']); //current-run-card
   echo H::openTag('div', ['class' => 'card-header']); //4
    echo new H6()->content($translator->translate('homecare.current.run'));
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'card-body']); //4

    echo H::openTag('div', ['class' => 'mb-3']); //5
     $shaie = 'settings[homecare_auto_invoice_enabled]';
     echo H::openTag('label', [
      'for' => $shaie
     ]);
      echo $translator->translate(
       'homecare.auto.invoice.enabled'
      );
      echo $s->infoIcon('homecare_auto_invoice_enabled');
     echo H::closeTag('label');

     $body[$shaie] =
     $s->getSetting('homecare_auto_invoice_enabled');

     echo H::openTag('select', [
      'name' => $shaie,
      'id' => $shaie,
      'class' => 'form-select',
     ]);
      echo  new Option()
       ->value('0')
       ->content(
        $translator->translate('no')
       );
      echo  new Option()
       ->value('1')
       ->selected(
        $body[$shaie] == '1'
      )
       ->content(
        $translator->translate('yes')
       );
     echo H::closeTag('select');
    echo H::closeTag('div'); //5

    echo H::openTag('div', ['class' => 'mb-3']); //5
     $shcrcsi = 'settings[homecare_current_run_category_secondary_id]';
     echo H::openTag('label', [
      'for' => $shcrcsi
     ]);
      echo $translator->translate(
       'homecare.current.run.category.secondary'
      );
      echo $s->infoIcon('homecare_current_run_category_secondary_id');
     echo H::closeTag('label');

     $body[$shcrcsi] =
     $s->getSetting('homecare_current_run_category_secondary_id');

     echo H::openTag('select', [
      'name' => $shcrcsi,
      'id' => $shcrcsi,
      'class' => 'form-select',
     ]);
      echo  new Option()
       ->value('0')
       ->content(
        $translator->translate('not.set')
       );
      /**
       * @var string $categorySecondaryName
       */
      foreach ($category_secondaries as $categorySecondaryId => $categorySecondaryName) {
       echo  new Option()
        ->value((string) $categorySecondaryId)
        ->selected(
         $body[$shcrcsi] == (string) $categorySecondaryId
       )
        ->content(
         $categorySecondaryName
        );
      }
     echo H::closeTag('select');
    echo H::closeTag('div'); //5

    echo H::openTag('div', ['class' => 'mb-3']); //5
     $shcrlrd = 'settings[homecare_current_run_last_run_date]';
     echo H::openTag('label', [
      'for' => $shcrlrd
     ]);
      echo $translator->translate(
       'homecare.current.run.last.run.date'
      );
      echo $s->infoIcon('homecare_current_run_last_run_date');
     echo H::closeTag('label');

     echo H::input('date', $shcrlrd, $s->getSetting(
      'homecare_current_run_last_run_date'
     ), [
      'id' => $shcrlrd,
      'class' => 'form-control',
      'data-action' => 'show-picker',
     ]);
    echo H::closeTag('div'); //5

   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //current-run-card

  echo H::openTag('div', ['class' => 'card mb-3']); //vision-api-key-card
   echo H::openTag('div', ['class' => 'card-header']); //4
    echo new H6()->content($translator->translate('homecare.vision.api.key.title'));
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'card-body']); //4
    echo H::tag('p', $translator->translate('homecare.vision.api.key.description'),
        ['class' => 'text-muted']);

    echo H::openTag('div', ['class' => 'mb-3']); //5
     $hvak = 'settings[homecare_vision_api_key]';
     echo H::openTag('label', [
      'for' => $hvak
     ]);
      echo $translator->translate('homecare.vision.api.key');
      echo $s->infoIcon('homecare_vision_api_key');
     echo H::closeTag('label');
     echo H::openTag('a', [
      'href' => 'https://console.anthropic.com/settings/keys',
      'target' => '_blank',
      'rel' => 'noopener noreferrer',
      'class' => 'small ms-2',
     ]);
      echo $translator->translate('homecare.vision.api.key.get');
     echo H::closeTag('a');

     $body[$hvak] = $s->getSetting('homecare_vision_api_key');
     echo new Input()
          ->type('password')
          ->class('form-control')
          ->id($hvak)
          ->name($hvak)
          ->value(H::encode($body[$hvak]));
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //vision-api-key-card

  echo H::openTag('div', ['class' => 'card']); //3
   echo H::openTag('div', ['class' => 'card-header']); //4
    echo new H6()->content($translator->translate('homecare.hidden.columns'));
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'card-body']); //4
    echo H::tag('p', $translator->translate('homecare.hidden.columns.description'),
        ['class' => 'text-muted']);
    echo $s->infoIcon('homecare_hidden_inv_columns');

    // Fallback so unchecking every box still submits the key — without
    // this, saving with nothing checked would leave the setting untouched
    // instead of clearing it.
    echo H::openTag('input', [
     'type' => 'hidden',
     'name' => 'settings[homecare_hidden_inv_columns][]',
     'value' => '',
    ]);

    foreach ($toggleableColumns as $columnKey => $labelKey) {
     echo H::openTag('div', ['class' => 'form-check']);
      $inputId = 'homecare-hide-' . $columnKey;
      echo H::openTag('input', [
       'type' => 'checkbox',
       'class' => 'form-check-input',
       'id' => $inputId,
       'name' => 'settings[homecare_hidden_inv_columns][]',
       'value' => $columnKey,
       'checked' => in_array($columnKey, $hidden_inv_columns, true) ? 'checked' : null,
      ]);
      echo H::openTag('label', ['class' => 'form-check-label', 'for' => $inputId]);
       echo $translator->translate($labelKey);
      echo H::closeTag('label');
     echo H::closeTag('div');
    }
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3

  echo H::openTag('div', ['class' => 'card mt-3']); //3
   echo H::openTag('div', ['class' => 'card-header']); //4
    echo new H6()->content($translator->translate('homecare.hidden.columns.guest'));
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'card-body']); //4
    echo H::tag('p', $translator->translate('homecare.hidden.columns.guest.description'),
        ['class' => 'text-muted']);
    echo $s->infoIcon('homecare_hidden_inv_guest_columns');

    // Same fallback reasoning as the staff-side list above.
    echo H::openTag('input', [
     'type' => 'hidden',
     'name' => 'settings[homecare_hidden_inv_guest_columns][]',
     'value' => '',
    ]);

    // Only the columns inv/guest.php actually renders — Number, the PDF
    // download menu, id, and Status are deliberately absent, always
    // visible there, same as Worker/Amount/do_not_send on the staff side.
    $toggleableGuestColumns = [
     'paid'         => 'paid',
     'credit_note'  => 'credit.invoice.for.invoice',
     'client'       => 'client',
     'date_created' => 'date.created',
     'date_due'     => 'due.date',
     'total'        => 'total',
     'balance'      => 'balance',
    ];

    foreach ($toggleableGuestColumns as $columnKey => $labelKey) {
     echo H::openTag('div', ['class' => 'form-check']);
      $inputId = 'homecare-hide-guest-' . $columnKey;
      echo H::openTag('input', [
       'type' => 'checkbox',
       'class' => 'form-check-input',
       'id' => $inputId,
       'name' => 'settings[homecare_hidden_inv_guest_columns][]',
       'value' => $columnKey,
       'checked' => in_array($columnKey, $hidden_inv_guest_columns, true) ? 'checked' : null,
      ]);
      echo H::openTag('label', ['class' => 'form-check-label', 'for' => $inputId]);
       echo $translator->translate($labelKey);
      echo H::closeTag('label');
     echo H::closeTag('div');
    }
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3

 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
