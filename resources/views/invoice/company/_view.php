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
   echo Html::openTag('div', ['class' => 'mb-3 form-group']); //3
    echo Field::hidden($form, 'id')
         ->addInputAttributes([
              'class' => 'form-control form-control-lg',
         ])
         ->hideLabel()
         ->value(Html::encode($form->getId() ??  ''));
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'form-check form-switch']); //3
    echo Field::checkbox($form, 'current')
         ->inputLabelAttributes(['class' => 'form-check-label'])
         ->inputClass('form-check-input')
         ->ariaDescribedBy($translator->translate('active'))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3 form-group']); //3
    echo Field::text($form, 'name')
         ->label($translator->translate('name'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('name'),
            'class' => 'form-control form-control-lg',
         ])
         ->required(true)
         ->value(Html::encode($form->getName() ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3 form-group']); //3
    echo Field::email($form, 'email')
         ->label($translator->translate('email'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('email'),
            'class' => 'form-control form-control-lg',
         ])
         ->required(true)
         ->value(Html::encode($form->getEmail() ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3 form-group']); //3
    echo Field::text($form, 'web')
         ->label($translator->translate('web'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('web'),
            'class' => 'form-control form-control-lg',
         ])
         ->value(Html::encode($form->getWeb() ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3 form-group']); //3
    echo Field::text($form, 'address_1')
         ->label($translator->translate('street.address'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('street.address'),
            'class' => 'form-control form-control-lg',
         ])
         ->value(Html::encode($form->getAddress1() ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3 form-group']); //3
    echo Field::text($form, 'address_2')
         ->label($translator->translate('street.address.2'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('street.address.2'),
            'class' => 'form-control form-control-lg',
         ])
         ->value(Html::encode($form->getAddress2() ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3 form-group']); //3
    echo Field::text($form, 'city')
         ->label($translator->translate('city'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('city'),
            'class' => 'form-control form-control-lg',
         ])
         ->value(Html::encode($form->getCity() ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3 form-group']);
    echo Field::text($form, 'state')
         ->label($translator->translate('state'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('state'),
            'class' => 'form-control form-control-lg',
         ])
         ->value(Html::encode($form->getState() ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3 form-group']); //3
    echo Field::text($form, 'zip')
         ->label($translator->translate('zip'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('zip'),
            'class' => 'form-control form-control-lg',
         ])
         ->value(Html::encode($form->getZip() ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3 form-group']); //3
    echo Field::text($form, 'country')
         ->label($translator->translate('country'))
         ->addInputAttributes([
           'placeholder' => $translator->translate('country'),
           'class' => 'form-control form-control-lg',
         ])
         ->value(Html::encode($form->getCountry() ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3 form-group']);
    echo Field::telephone($form, 'phone')
         ->label($translator->translate('phone'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('phone'),
            'class' => 'form-control form-control-lg',
         ])
         ->value(Html::encode($form->getPhone() ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
   echo Html::openTag('div', ['class' => 'mb-3 form-group']); //3
    echo Field::telephone($form, 'fax')
         ->label($translator->translate('fax'))
         ->addInputAttributes([
            'placeholder' => $translator->translate('fax'),
             'class' => 'form-control form-control-lg',
         ])
         ->value(Html::encode($form->getFax() ?? ''))
         ->disabled(true);
   echo Html::closeTag('div'); //3
  echo Html::closeTag('div'); //2
 echo Html::closeTag('div'); //1
echo  new Form()->close();
