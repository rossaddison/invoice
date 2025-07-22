<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Invoice\EmailTemplate\EmailTemplateForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

?>
<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>
<?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
     <?php echo $translator->translate('view'); ?>
<?php echo Html::closeTag('h1'); ?>
<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('EmailTemplateForm')
    ->open();
?>

<?php echo Html::openTag('div', ['class' => 'container']); ?>
<?php echo Html::openTag('div', ['class' => 'row']); ?>
<?php echo Html::openTag('div', ['class' => 'col card mb-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>
    <?php echo Html::openTag('div'); ?>
        <?php echo Field::text($form, 'email_template_title')
    ->label($translator->translate('title'))
    ->addInputAttributes([
        'class' => 'form-control',
    ])
    ->value(Html::encode($form->getEmail_template_title()))
    ->readonly(true)
    ->placeholder($translator->translate('title'));
?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::openTag('div'); ?>
        <?php echo Field::text($form, 'email_template_type')
    ->label($translator->translate('type'))
    ->addInputAttributes([
        'class' => 'form-control',
    ])
    ->value(Html::encode($form->getEmail_template_type()))
    ->readonly(true)
    ->placeholder($translator->translate('type'));
?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::openTag('div'); ?>
        <?php echo Field::text($form, 'email_template_body')
    ->label($translator->translate('body'))
    ->addInputAttributes([
        'class' => 'form-control',
    ])
    ->value($form->getEmail_template_body())
    ->readonly(true)
    ->placeholder($translator->translate('body'));
?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::openTag('div'); ?>
        <?php echo Field::text($form, 'email_template_subject')
    ->label($translator->translate('subject'))
    ->addInputAttributes([
        'class' => 'form-control',
    ])
    ->value(Html::encode($form->getEmail_template_subject()))
    ->readonly(true)
    ->placeholder($translator->translate('subject'));
?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::openTag('div'); ?>
        <?php echo Field::text($form, 'email_template_from_name')
    ->label($translator->translate('from.name'))
    ->addInputAttributes([
        'class' => 'form-control',
    ])
    ->value(Html::encode($form->getEmail_template_from_name()))
    ->readonly(true)
    ->placeholder($translator->translate('from.name'));
?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::openTag('div'); ?>
        <?php echo Field::text($form, 'email_template_from_email')
    ->label($translator->translate('from.email'))
    ->addInputAttributes([
        'class' => 'form-control',
    ])
    ->value(Html::encode($form->getEmail_template_from_email()))
    ->readonly(true)
    ->placeholder($translator->translate('from.email'));
?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::openTag('div'); ?>
        <?php echo Field::text($form, 'email_template_cc')
    ->label($translator->translate('cc'))
    ->addInputAttributes([
        'class' => 'form-control',
    ])
    ->value(Html::encode($form->getEmail_template_cc()))
    ->readonly(true)
    ->placeholder($translator->translate('cc'));
?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::openTag('div'); ?>
        <?php echo Field::text($form, 'email_template_bcc')
    ->label($translator->translate('bcc'))
    ->addInputAttributes([
        'class' => 'form-control',
    ])
    ->value(Html::encode($form->getEmail_template_bcc()))
    ->readonly(true)
    ->placeholder($translator->translate('bcc'));
?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::openTag('div'); ?>
        <?php echo Field::text($form, 'email_template_pdf_template')
    ->label($translator->translate('pdf.template'))
    ->addInputAttributes([
        'class' => 'form-control',
    ])
    ->value(Html::encode($form->getEmail_template_pdf_template()))
    ->readonly(true)
    ->placeholder($translator->translate('pdf.template'));
?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo $button::back(); ?>
<?php echo Html::closeTag('form'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
