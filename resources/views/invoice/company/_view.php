<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

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

// A pure display page — see docs/READONLY_VIEW_FIELDS_AUGUST_2026.md.

echo Html::openTag('h1');
 echo Html::encode($title . ' ' . $companyPublic);
echo Html::closeTag('h1');

echo Html::openTag('div', ['class' => 'headerbar']); //1
echo $button::back();
 echo Html::openTag('div', ['id' => 'content']); //2
  echo Html::openTag('div', ['class' => 'row']);//3
  echo Html::closeTag('div'); //3
  ReadOnlyField::render(
      $translator->translate('active'),
      $translator->translate($form->current === 1 ? 'yes' : 'no'),
  );
  ReadOnlyField::render($translator->translate('name'), $form->name);
  ReadOnlyField::render($translator->translate('email'), $form->email);
  ReadOnlyField::render($translator->translate('web'), $form->web);
  ReadOnlyField::render($translator->translate('street.address'), $form->address_1);
  ReadOnlyField::render($translator->translate('street.address.2'), $form->address_2);
  ReadOnlyField::render($translator->translate('city'), $form->city);
  ReadOnlyField::render($translator->translate('state'), $form->state);
  ReadOnlyField::render($translator->translate('zip'), $form->zip);
  ReadOnlyField::render($translator->translate('country'), $form->country);
  ReadOnlyField::render($translator->translate('phone'), $form->phone);
  ReadOnlyField::render($translator->translate('fax'), $form->fax);
 echo Html::closeTag('div'); //2
echo Html::closeTag('div'); //1
