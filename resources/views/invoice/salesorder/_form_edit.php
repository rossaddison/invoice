<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;
use Yiisoft\Html\Tag\Form;

/**
 * Related logic: see App\Invoice\SalesOrder\SalesOrderController function add()
 * @var App\Invoice\CustomField\CustomFieldRepository $cfR
 * @var App\Invoice\CustomValue\CustomValueRepository $cvR
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\SalesOrder\SalesOrderForm $form
 * @var App\Invoice\SalesOrderCustom\SalesOrderCustomForm $salesOrderCustomForm
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $dels
 * @var array $errors
 * @var array $so_custom_values
 * @var array $custom_values
 * @var int $delCount
 * @var string $actionName
 * @var string $csrf
 * @var string $defaultGroupId
 * @var string $invNumber
 * @var string $terms_and_conditions_file
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string, Stringable|null|scalar> $actionArgumentsDelAdd
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array<array-key, array<array-key, string>|string>> $optionsData
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['client']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['deliveryLocation']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['group']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['salesOrderStatus']
 */

$vat = $s->getSetting('enable_vat_registration') === '1';

echo H::openTag('div', ['class' => 'container py-5 h-100']); //0
echo H::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); //1
echo H::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); //2
echo H::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); //3
echo H::openTag('div', ['class' => 'card-header']); //4
 echo H::tag('h1', $title, ['class' => 'fw-normal h3 text-center']);
 echo new Form()->post($urlGenerator->generate($actionName, $actionArguments))
                ->enctypeMultipartFormData()
                ->csrf($csrf)
                ->id('SalesOrderForm')
                ->open();
  echo H::openTag('div', ['class' => 'container']); //5
   echo H::openTag('div', ['class' => 'row']); //6
    echo H::openTag('div', ['class' => 'col card mb-3']); //7
     echo H::openTag('div', ['class' => 'card-header']); //8
      echo H::openTag('div'); //9
       echo Field::errorSummary($form)
        ->errors($errors)
        ->header($translator->translate('error.summary'))
        ->onlyCommonErrors();
      echo H::closeTag('div'); //9
      echo H::openTag('div'); //9
       echo Field::hidden($form, 'quote_id');
      echo H::closeTag('div'); //9
      echo H::openTag('div'); //9
       echo Field::hidden($form, 'number')
        ->hideLabel(false)
        ->label($translator->translate('salesorder'))
        ->addInputAttributes([
         'class' => 'form-control form-control-lg',
         'readonly' => 'readonly',
        ])
        ->value(H::encode($form->getNumber()));
      echo H::closeTag('div'); //9
      echo H::openTag('div'); //9
       echo Field::select($form, 'client_id')
        ->label($translator->translate('user.account.clients'))
        ->addInputAttributes(['class' => 'form-control form-control-lg'])
        ->value($form->getClientId())
        ->prompt($translator->translate('none'))
        ->optionsData($optionsData['client'])
        ->hint($translator->translate('hint.this.field.is.required'));
      echo H::closeTag('div'); //9
      echo H::openTag('div'); //9
       echo Field::select($form, 'group_id')
        ->label($translator->translate('salesorder.default.group'))
        ->addInputAttributes(['class' => 'form-control form-control-lg'])
        ->value($form->getGroupId() ?? $defaultGroupId)
        ->prompt($translator->translate('none'))
        ->optionsData($optionsData['group'])
        ->hint($translator->translate('hint.this.field.is.required'));
      echo H::closeTag('div'); //9
      // If there is no delivery location for this client, create one now
      if ($delCount == 0) {
       echo H::a(
        $translator->translate('delivery.location.add'),
        $urlGenerator->generate('del/add', $actionArgumentsDelAdd),
        ['class' => 'btn btn-danger btn-lg mt-3']
       );
      } else {
       echo H::openTag('div', ['class' => 'form-group']); //9
        echo H::openTag('label', ['for' => 'delivery_location_id']); //10
         echo $translator->translate('delivery.location') . ': ';
        echo H::closeTag('label'); //10
        echo H::openTag('select', [
         'name' => 'delivery_location_id',
         'id' => 'delivery_location_id',
         'class' => 'form-control form-control-lg',
         'disabled' => true,
        ]); //10
/**
 * @var App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation $del
 */
         foreach ($dels as $del) {
          $delAddress1 = $del->getAddress1();
          $delAddress2 = $del->getAddress2();
          $delCity     = $del->getCity();
          $delZip      = $del->getZip();
          echo new Option()
           ->value((string) $del->reqId())
           ->selected(true)
           ->content(
            (null !== $delAddress1 ? $delAddress1 : '') . ', '
            . (null !== $delAddress2 ? $delAddress2 : '') . ', '
            . (null !== $delCity ? $delCity : '') . ', '
            . (null !== $delZip ? $delZip : '')
           );
         }
        echo H::closeTag('select'); //10
       echo H::closeTag('div'); //9
      }
      echo H::tag('br', '');
      echo H::tag('br', '');
      echo H::openTag('div'); //9
       echo Field::date($form, 'date_created')
        ->label($translator->translate('date.issued'))
        ->value(
         H::encode($form->getDateCreated() instanceof \DateTimeImmutable
          ? $form->getDateCreated()->format('Y-m-d')
          : (is_string($form->getDateCreated()) ? $form->getDateCreated() : ''))
        )
        ->hint($translator->translate('hint.this.field.is.required'));
      echo H::closeTag('div'); //9
      echo H::openTag('div'); //9
       echo Field::password($form, 'password')
        ->label($translator->translate('password'))
        ->addInputAttributes(['class' => 'form-control form-control-lg'])
        ->value(H::encode($form->getPassword()))
        ->placeholder($translator->translate('password'))
        ->hint($translator->translate('hint.this.field.is.not.required'));
      echo H::closeTag('div'); //9
      echo H::openTag('div'); //9
       echo Field::select($form, 'status_id')
        ->label($translator->translate('status'))
        ->addInputAttributes(['class' => 'form-control form-control-lg'])
        ->value($form->getStatusId())
        ->prompt($translator->translate('none'))
        ->optionsData($optionsData['salesOrderStatus'])
        ->hint($translator->translate('hint.this.field.is.not.required'));
      echo H::closeTag('div'); //9
      echo H::openTag('div'); //9
       if ($form->getStatusId() == 1) {
        echo Field::hidden($form, 'url_key')->hideLabel(true);
       }
       if ($form->getStatusId() > 1) {
        echo Field::text($form, 'url_key')
         ->hideLabel(false)
         ->label($translator->translate('guest.url'));
       }
      echo H::closeTag('div'); //9
      if (!$vat) {
       echo H::openTag('div'); //9
        echo Field::text($form, 'discount_amount')
         ->hideLabel(false)
         ->label($translator->translate('discount') . ' ' . $s->getSetting('currency_symbol'))
         ->addInputAttributes(['class' => 'form-control form-control-lg'])
         ->value($s->formatAmount(($form->getDiscountAmount() ?? 0.00)))
         ->placeholder($translator->translate('discount'));
       echo H::closeTag('div'); //9
      }
      echo H::openTag('div'); //9
       echo Field::hidden($form, 'inv_id')->hideLabel();
      echo H::closeTag('div'); //9
      /**
       * @var App\Invoice\Entity\CustomField $customField
       */
      foreach ($cfR->repoTablequery('sales_order_custom') as $customField) {
       $custom_values = $cvR->fixCfValueToCf($cfR->repoTablequery('salesorder_custom'));
       $cvH->printFieldForForm($customField, $salesOrderCustomForm, $translator,
        $urlGenerator, $so_custom_values, $custom_values);
      }
      echo H::openTag('div'); //9
       echo H::openTag('div', ['class' => 'row']); //10
        echo H::openTag('label', ['for' => 'terms_and_conditions_file', 'class' => 'control-label']); //11
         echo $translator->translate('term');
        echo H::closeTag('label'); //11
        echo H::openTag('textarea', [
         'id' => 'terms_and_conditions_file',
         'class' => 'form-control form-control-lg',
         'rows' => '20',
         'cols' => '20',
        ]); //11
         echo $terms_and_conditions_file;
        echo H::closeTag('textarea'); //11
       echo H::closeTag('div'); //10
       echo H::openTag('div', ['class' => 'row']); //10
        echo H::openTag('div', ['class' => 'col-xs-12 col-sm-2']); //11
         echo H::openTag('label', ['for' => 'inv_number', 'class' => 'control-label']); //12
          echo $translator->translate('salesorder.invoice.number');
         echo H::closeTag('label'); //12
         echo H::tag('input', '', [
          'type' => 'text',
          'name' => 'inv_number',
          'id' => 'inv_number',
          'class' => 'form-control form-control-lg',
          'required' => true,
          'disabled' => true,
          'value' => $invNumber ?: $translator->translate('not.set'),
         ]);
        echo H::closeTag('div'); //11
       echo H::closeTag('div'); //10
      echo H::closeTag('div'); //9
     echo H::closeTag('div'); //8
    echo H::closeTag('div'); //7
   echo H::closeTag('div'); //6
   echo $button::backSave();
  echo H::closeTag('div'); //5
 echo H::closeTag('form');
echo H::closeTag('div'); //4
echo H::closeTag('div'); //3
echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
echo H::closeTag('div'); //0
