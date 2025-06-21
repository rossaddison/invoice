<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\EmailTemplate\EmailTemplateForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

?>
<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
     <?= $translator->translate('view'); ?>
<?= Html::closeTag('h1'); ?>
<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('EmailTemplateForm')
    ->open()
?>

<?= Html::openTag('div', ['class' => 'container']); ?>
<?= Html::openTag('div', ['class' => 'row']); ?>
<?= Html::openTag('div', ['class' => 'col card mb-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
    <?= Html::openTag('div'); ?>
        <?= Field::text($form, 'email_template_title')
            ->label($translator->translate('title'))
            ->addInputAttributes([
                'class' => 'form-control'
            ])
            ->value(Html::encode($form->getEmail_template_title()))
            ->readonly(true)
            ->placeholder($translator->translate('title'))
?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div'); ?>
        <?= Field::text($form, 'email_template_type')
   ->label($translator->translate('type'))
   ->addInputAttributes([
       'class' => 'form-control'
   ])
   ->value(Html::encode($form->getEmail_template_type()))
   ->readonly(true)
   ->placeholder($translator->translate('type'))
?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div'); ?>
        <?= Field::text($form, 'email_template_body')
   ->label($translator->translate('body'))
   ->addInputAttributes([
       'class' => 'form-control'
   ])
   ->value($form->getEmail_template_body())
   ->readonly(true)
   ->placeholder($translator->translate('body'))
?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div'); ?>
        <?= Field::text($form, 'email_template_subject')
   ->label($translator->translate('subject'))
   ->addInputAttributes([
       'class' => 'form-control'
   ])
   ->value(Html::encode($form->getEmail_template_subject()))
   ->readonly(true)
   ->placeholder($translator->translate('subject'))
?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div'); ?>
        <?= Field::text($form, 'email_template_from_name')
   ->label($translator->translate('from.name'))
   ->addInputAttributes([
       'class' => 'form-control'
   ])
   ->value(Html::encode($form->getEmail_template_from_name()))
   ->readonly(true)
   ->placeholder($translator->translate('from.name'))
?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div'); ?>
        <?= Field::text($form, 'email_template_from_email')
   ->label($translator->translate('from.email'))
   ->addInputAttributes([
       'class' => 'form-control'
   ])
   ->value(Html::encode($form->getEmail_template_from_email()))
   ->readonly(true)
   ->placeholder($translator->translate('from.email'))
?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div'); ?>
        <?= Field::text($form, 'email_template_cc')
   ->label($translator->translate('cc'))
   ->addInputAttributes([
       'class' => 'form-control'
   ])
   ->value(Html::encode($form->getEmail_template_cc()))
   ->readonly(true)
   ->placeholder($translator->translate('cc'))
?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div'); ?>
        <?= Field::text($form, 'email_template_bcc')
   ->label($translator->translate('bcc'))
   ->addInputAttributes([
       'class' => 'form-control'
   ])
   ->value(Html::encode($form->getEmail_template_bcc()))
   ->readonly(true)
   ->placeholder($translator->translate('bcc'))
?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div'); ?>
        <?= Field::text($form, 'email_template_pdf_template')
   ->label($translator->translate('pdf.template'))
   ->addInputAttributes([
       'class' => 'form-control'
   ])
   ->value(Html::encode($form->getEmail_template_pdf_template()))
   ->readonly(true)
   ->placeholder($translator->translate('pdf.template'))
?>
    <?= Html::closeTag('div'); ?>
    <?= $button::back(); ?>
<?= Html::closeTag('form'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
