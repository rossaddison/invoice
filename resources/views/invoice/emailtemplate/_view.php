<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

/**
 * @var App\Invoice\EmailTemplate\EmailTemplateForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

// A pure display page — see docs/READONLY_VIEW_FIELDS_AUGUST_2026.md.
?>
<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-body']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
     <?= $translator->translate('view'); ?>
<?= Html::closeTag('h1'); ?>
<?= $button::back(); ?>
<?= Html::openTag('div'); ?>
    <?php
        ReadOnlyField::render($translator->translate('title'), $form->getEmailTemplateTitle());
ReadOnlyField::render($translator->translate('type'), $form->getEmailTemplateType());
ReadOnlyField::render($translator->translate('body'), $form->getEmailTemplateBody());
ReadOnlyField::render($translator->translate('subject'), $form->getEmailTemplateSubject());
ReadOnlyField::render($translator->translate('from.name'), $form->getEmailTemplateFromName());
ReadOnlyField::render($translator->translate('from.email'), $form->getEmailTemplateFromEmail());
ReadOnlyField::render($translator->translate('cc'), $form->getEmailTemplateCc());
ReadOnlyField::render($translator->translate('bcc'), $form->getEmailTemplateBcc());
ReadOnlyField::render($translator->translate('pdf.template'), $form->getEmailTemplatePdfTemplate());
?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
