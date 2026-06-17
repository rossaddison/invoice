<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Company\CompanyForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $companyPublic
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */


echo Html::openTag('h1');
 echo Html::encode($title . ' ' . $companyPublic);
echo Html::closeTag('h1');
echo new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('CompanyForm')
    ->open();

 echo Html::openTag('div', ['class' => 'headerbar']); //1
 echo $button::back();
  echo Html::openTag('div', ['id' => 'content']); //2
   echo Html::openTag('div', ['class' => 'row']);//3
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'form-check form-switch']); //3
    echo Field::checkbox($form, 'current')
         ->inputLabelAttributes(['class' => 'form-check-label'])
         ->inputClass('form-check-input')
         ->ariaDescribedBy($translator->translate('active'))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3']); //3
    echo Field::text($form, 'name')
         ->label($translator->translate('name'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('name'),
            'class' => 'form-control form-control-lg',
         ])
         ->required(true)
         ->value(Html::encode($form->name ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3']); //3
    echo Field::email($form, 'email')
         ->label($translator->translate('email'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('email'),
            'class' => 'form-control form-control-lg',
         ])
         ->required(true)
         ->value(Html::encode($form->email ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3']); //3
    echo Field::text($form, 'web')
         ->label($translator->translate('web'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('web'),
            'class' => 'form-control form-control-lg',
         ])
         ->value(Html::encode($form->web ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3']); //3
    echo Field::text($form, 'address_1')
         ->label($translator->translate('street.address'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('street.address'),
            'class' => 'form-control form-control-lg',
         ])
         ->value(Html::encode($form->address_1 ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3']); //3
    echo Field::text($form, 'address_2')
         ->label($translator->translate('street.address.2'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('street.address.2'),
            'class' => 'form-control form-control-lg',
         ])
         ->value(Html::encode($form->address_2 ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3']); //3
    echo Field::text($form, 'city')
         ->label($translator->translate('city'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('city'),
            'class' => 'form-control form-control-lg',
         ])
         ->value(Html::encode($form->city ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3']);
    echo Field::text($form, 'state')
         ->label($translator->translate('state'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('state'),
            'class' => 'form-control form-control-lg',
         ])
         ->value(Html::encode($form->state ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3']); //3
    echo Field::text($form, 'zip')
         ->label($translator->translate('zip'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('zip'),
            'class' => 'form-control form-control-lg',
         ])
         ->value(Html::encode($form->zip ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3']); //3
    echo Field::text($form, 'country')
         ->label($translator->translate('country'))
         ->addInputAttributes([
           'placeholder' => $translator->translate('country'),
           'class' => 'form-control form-control-lg',
         ])
         ->value(Html::encode($form->country ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3']);
    echo Field::telephone($form, 'phone')
         ->label($translator->translate('phone'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('phone'),
            'class' => 'form-control form-control-lg',
         ])
         ->value(Html::encode($form->phone ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3']); //3
    echo Field::telephone($form, 'fax')
         ->label($translator->translate('fax'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('fax'),
             'class' => 'form-control form-control-lg',
         ])
         ->value(Html::encode($form->fax ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
  echo Html::closeTag('div'); //2
 echo Html::closeTag('div'); //1
echo  new Form()->close();
