<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Input;
use Yiisoft\Html\Tag\Option;

/**
 * @var App\Invoice\Entity\CustomField|null $custom_field
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var array $custom_values
 * @var array $custom_values_types
 * @var string $csrf
 * @var string $custom_field_id
 * @var string $title
 */

$row         = ['class' => 'row'];
$colMd6Off3  = ['class' => 'col-xs-12 col-md-6 col-md-offset-3'];
$formGroup   = ['class' => 'form-group'];
$formControlLg = 'form-control form-control-lg';

echo H::openTag('form', ['method' => 'post']); //0
 echo new Input()->type('hidden')->name('_csrf')->value($csrf);
 echo H::openTag('div', ['id' => 'headerbar']); //1
  echo H::tag('h1', $translator->translate('custom.values'),
          ['class' => 'headerbar-title']);
  echo H::openTag('div', ['class' => 'headerbar-item pull-right']); //2
   echo H::openTag('div', ['class' => 'btn-group btn-group-sm']); //3
    echo H::a(
     H::tag('i', '', ['class' => 'bi bi-arrow-left']) .
            ' '
            . $translator->translate('back'),
     $urlGenerator->generate('customfield/index'),
     ['class' => 'btn btn-default']
    );
    echo H::a(
     H::tag('i', '', ['class' => 'bi bi-plus-lg'])
            . ' '
            . $translator->translate('new'),
     $urlGenerator->generate('customvalue/new', ['id' => $custom_field_id]),
     ['class' => 'btn btn-primary']
    );
   echo H::closeTag('div'); //3
  echo H::closeTag('div'); //2
 echo H::closeTag('div'); //1
 echo H::openTag('div', ['id' => 'content']); //1
  if (null !== $custom_field) {
   echo H::openTag('div', $row); //2
    echo H::openTag('div', $colMd6Off3); //3
     echo H::openTag('div', $formGroup); //4
      echo H::openTag('label', ['for' => 'label']); //5
       echo $translator->translate('field') . ': ';
      echo H::closeTag('label'); //5
      $customFieldLabel = $custom_field->getLabel() ?? '';
      echo new Input()
       ->type('text')
       ->name('label')
       ->id('label')
       ->class($formControlLg)
       ->value(H::encode(strlen($customFieldLabel) > 0 ? $customFieldLabel : ''))
       ->addAttributes(['disabled' => 'disabled']);
     echo H::closeTag('div'); //4
     echo H::openTag('div', $formGroup); //4
      echo H::openTag('label', ['for' => 'types']); //5
       echo $translator->translate('type') . ': ';
      echo H::closeTag('label'); //5
      echo H::openTag('select', [
       'name'     => 'types',
       'id'       => 'types',
       'class'    => $formControlLg,
       'disabled' => 'disabled',
      ]); //5
       /**
        * @var string $type
        */
       foreach ($custom_values_types as $type) {
        $alpha = str_replace('-', '_', strtolower($type));
        echo new Option()
         ->value($type)
         ->selected($custom_field->getType() === $type)
         ->content($translator->translate($alpha));
       }
      echo H::closeTag('select'); //5
     echo H::closeTag('div'); //4
     echo H::openTag('div', $formGroup); //4
      echo H::openTag('table', ['class' => 'table table-bordered']); //5
       echo H::openTag('thead'); //6
        echo H::openTag('tr'); //7
         echo H::tag('th', $translator->translate('id'));
         echo H::tag('th', $translator->translate('label'));
         echo H::tag('th', $translator->translate('options'));
        echo H::closeTag('tr'); //7
       echo H::closeTag('thead'); //6
       echo H::openTag('tbody'); //6
        /**
         * @var App\Invoice\Entity\CustomValue $custom_value
         */
        foreach ($custom_values as $custom_value) {
         echo H::openTag('tr'); //7
          echo H::tag('td', $custom_value->getId());
          echo H::tag('td', H::encode($custom_value->getValue()));
          echo H::openTag('td'); //8
           echo H::openTag('div', ['class' => 'options btn-group']); //9
            echo H::a(
             H::tag('i', '', ['class' => 'bi bi-gear'])
                    . ' '
                    . $translator->translate('options'),
             '#',
             ['class' => 'btn btn-default btn-sm dropdown-toggle',
                 'data-bs-toggle' => 'dropdown']
            );
            echo H::openTag('ul', ['class' => 'dropdown-menu']); //10
             echo H::openTag('li'); //11
              echo H::a(
               H::tag('i', '', ['class' => 'bi-pencil-square'])
                      . ' '
                      . $translator->translate('edit'),
               $urlGenerator->generate('customvalue/edit',
                    ['id' => $custom_value->getId()]),
               ['style' => 'text-decoration:none']
              );
             echo H::closeTag('li'); //11
             echo H::openTag('li'); //11
              echo H::a(
               H::tag('i', '', ['class' => 'bi-trash'])
                      . $translator->translate('delete'),
               $urlGenerator->generate('customvalue/delete',
                    ['id' => $custom_value->getId()]),
               [
                'style'   => 'text-decoration:none',
                'onclick' => 'return confirm('
                   . H::encode("'"
                   . $translator->translate('delete.record.warning')
                   . "'") . ')',
               ]
              );
             echo H::closeTag('li'); //11
            echo H::closeTag('ul'); //10
           echo H::closeTag('div'); //9
          echo H::closeTag('td'); //8
         echo H::closeTag('tr'); //7
        }
       echo H::closeTag('tbody'); //6
      echo H::closeTag('table'); //5
     echo H::closeTag('div'); //4
    echo H::closeTag('div'); //3
   echo H::closeTag('div'); //2
  }
 echo H::closeTag('div'); //1
echo H::closeTag('form'); //0
