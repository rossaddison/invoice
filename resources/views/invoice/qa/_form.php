<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Qa\QaForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $qa
 */
echo Html::openTag('div', ['class'=>'container-fluid py-3']);
 echo Html::openTag('div',
        ['class'=>'row justify-content-center']);  //1
  echo Html::openTag('div',['class'=>'col-12 col-lg-10 col-xl-10']);  //2
   echo Html::openTag('div',
           ['class'=>'card border border-dark shadow-2-strong rounded-3']);  //3
    echo Html::openTag('div',['class'=>'card-header']);  //4
     echo Html::openTag('h1',['class'=>'fw-normal h3 text-center']);  //5
      echo Html::encode('➕'); 
     echo Html::closeTag('h1');  //5
     echo new Form()->post($urlGenerator->generate($actionName, $actionArguments))
              ->enctypeMultipartFormData()
              ->csrf($csrf)
              ->id('QaForm')
              ->open();
     echo $button::backSave();
      echo Html::openTag('div', ['class' => 'container']);  //6
       echo Html::openTag('div', ['class' => 'row']);  //7
        echo Html::openTag('div', ['class' => 'col card mb-3']);  //8
         echo Html::openTag('div');  //9
          echo Field::text($form,'question')
               ->label($translator->translate('faq.question'))
               ->addInputAttributes([
                   'class' => 'form-control form-control-lg',
               ])
               ->value(Html::encode($form->getQuestion()))
               ->placeholder($translator->translate('faq.question'));
         echo Html::closeTag('div');  //9
         echo Html::openTag('div');  //9
          echo Field::text($form,'answer')
               ->label($translator->translate('faq.answer'))
               ->addInputAttributes([
                   'class' => 'form-control form-control-lg',
               ])
               ->value(Html::encode($form->getAnswer()))
               ->placeholder($translator->translate('faq.answer'));
         echo Html::closeTag('div');  //9
         echo Html::openTag('div');  //9
          echo Field::text($form,'sort_order')
               ->label($translator->translate('faq.sort.order'))
               ->addInputAttributes([
                   'class' => 'form-control form-control-lg',
               ])
               ->value(Html::encode($form->getSortOrder()))
               ->placeholder($translator->translate('faq.sort.order'));
         echo Html::closeTag('div');  //9
         echo Html::openTag('div');  //9
          echo Field::checkbox($form, 'active')
                ->inputLabelAttributes([
                    'class' => 'form-check-label',
                ])
                ->inputClass('form-check-input')
                ->ariaDescribedBy($translator->translate('active'));
         echo Html::closeTag('div');   //9
        echo Html::closeTag('div');  //8
       echo Html::closeTag('div');  //7
      echo Html::closeTag('div');  //6
     echo Html::closeTag('form');  //5
    echo Html::closeTag('div');  //4
   echo Html::closeTag('div');  //3
  echo Html::closeTag('div');  //2
echo Html::closeTag('div');   //1