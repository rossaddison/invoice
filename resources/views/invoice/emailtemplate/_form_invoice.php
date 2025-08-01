<?php

declare(strict_types=1);

use Yiisoft\Form\Theme\ThemeContainer;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\NoEncode;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Select;
use Yiisoft\Html\Tag\Span;

/**
 * @var App\Invoice\EmailTemplate\EmailTemplateForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $actionName
 * @var string $admin_email
 * @var string $csrf
 * @var string $email_template_tags
 * @var string $from_email
 * @var string $sender_email
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $invoiceTemplates
 */
?>

<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?= $translator->translate('email.template.form'); ?>
<?= Html::closeTag('h1'); ?>
<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('EmailTemplateForm')
    ->open()
?>

<?php ThemeContainer::initialize([
    'A' => [
        'containerClass' => 'mb-3',
        'hintClass' => 'text-danger h6',
        'errorClass' => 'fw-bold fst-italic text-info',
    ],
    'B' => [
        'containerClass' => 'form-floating mb-3',
        'hintClass' => 'text-danger h6',
        'errorClass' => 'fw-bold fst-italic text-info',
    ],
]);
?>

<?= Field::errorSummary($form)
    ->errors($errors)
    ->header($translator->translate('email.template.error.summary'))
    ->onlyProperties(...['email_template_title',
        'email_template_type',
        'email_template_body',
        'email_template_subject',
        'email_template_from_name',
        'email_template_from_email',
        'email_template_cc',
        'email_template_bcc',
        'email_template_pdf_tempalte',
    ])
    ->onlyCommonErrors()
?>

<?= Html::openTag('div', ['class' => 'row']); ?>
    <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>        
        <?= Field::text($form, 'email_template_type', theme: 'A')
            ->value(Html::encode('Invoice'))
            ->readonly(true)
            ->render(); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
        <?= Field::text($form, 'email_template_title', theme: 'A')
            ->label($translator->translate('title'))
            ->required(true)
            ->addInputAttributes([
                'class' => 'form-control',
            ])
            ->value(Html::encode($form->getEmail_template_title() ?? ''))
            ->placeholder($translator->translate('title'))
            ->hint($translator->translate('hint.this.field.is.required')); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
        <?= Field::text($form, 'email_template_from_name', theme: 'A')
            ->label($translator->translate('from.name'))
            ->required(true)
            ->addInputAttributes([
                'class' => 'form-control',
            ])
            ->value(Html::encode($form->getEmail_template_from_name() ?? ''))
            ->placeholder($translator->translate('from.name'))
            ->hint($translator->translate('hint.this.field.is.required')); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
        <?= Html::openTag('div', ['class' => 'panel panel-default']); ?>
            <?= Html::openTag('div', ['class' => 'panel-heading']); ?>
                <?= Html::openTag('fieldset'); ?>    
                    <?= Html::openTag('h5'); ?>
                        <?= $translator->translate('email.template.from.source'); ?>
                    <?= Html::closeTag('h5'); ?>
                    <?= Html::openTag('h6'); ?>
                        <?= str_repeat("&nbsp;", 5) . $translator->translate('email.template.from.email.leave.blank'); ?>
                    <?= Html::closeTag('h6'); ?>
                <?= Html::closeTag('fieldset'); ?>
                <?= Html::openTag('div', ['id' => 'email_option']); ?>
                    <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                        <?= Html::tag('input', '', [
                            'type' => 'radio',
                            'id' => 'adminEmail',
                            'name' => 'from_email',
                            'value' => $admin_email,
                        ]); ?>
                        <?= Html::openTag('label', ['for' => 'adminEmail']); ?>
                            <?= $translator->translate('email.template.from.source.admin.email'); ?>
                        <?= Html::closeTag('label'); ?>
                        
                        <?= str_repeat("&nbsp;", 2); ?>
                        <?= Html::openTag('br'); ?>
                        
                        <?= Html::tag('input', '', [
                            'type' => 'radio',
                            'id' => 'senderEmail',
                            'name' => 'from_email',
                            'value' => $sender_email]); ?>
                        
                        <?= Html::openTag('label', ['for' => 'senderEmail']); ?>
                            <?= $translator->translate('email.template.from.source.sender.email'); ?>
                        <?= Html::closeTag('label'); ?>
                        
                        <?= str_repeat("&nbsp;", 2); ?>
                        <?= Html::openTag('br'); ?>
                        <?= Html::tag('input', '', [
                            'type' => 'radio',
                            'id' => 'fromEmail',
                            'name' => 'from_email',
                            'value' => $from_email]); ?>
                        
                        <?= Html::openTag('label', ['for' => 'fromEmail']); ?>
                            <?= $translator->translate('email.template.from.source.froms.email'); ?>
                        <?= Html::closeTag('label'); ?>

                        <?= Field::text($form, 'email_template_from_email')
                            ->label($translator->translate('from.email'))
                            ->addInputAttributes([
                                'class' => 'form-control',
                            ])
                            ->value(Html::encode($form->getEmail_template_from_email() ?? ''))
                            ->placeholder($translator->translate('from.email'))
                            ->hint($translator->translate('hint.this.field.is.required')); ?>
                    <?= Html::closeTag('div'); ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>    
    <?= Html::tag('br'); ?>
    <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
        <?= Field::text($form, 'email_template_cc', theme: 'A')
            ->label($translator->translate('cc'))
            ->required(false)
            ->addInputAttributes([
                'class' => 'form-control taggable',
            ])
            ->value(Html::encode($form->getEmail_template_cc() ?? ''))
            ->placeholder($translator->translate('cc'))
            ->hint($translator->translate('hint.this.field.is.not.required')); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
        <?= Field::text($form, 'email_template_bcc', theme: 'A')
            ->label($translator->translate('bcc'))
            ->required(false)
            ->addInputAttributes([
                'class' => 'form-control taggable',
            ])
            ->value(Html::encode($form->getEmail_template_bcc() ?? ''))
            ->placeholder($translator->translate('bcc'))
            ->hint($translator->translate('hint.this.field.is.not.required')); ?>         
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
        <?= Field::text($form, 'email_template_subject', theme: 'A')
            ->label($translator->translate('subject'))
            ->required(true)
            ->addInputAttributes([
                'class' => 'form-control taggable',
            ])
            ->value(Html::encode($form->getEmail_template_subject() ?? ''))
            ->placeholder($translator->translate('subject'))
            ->hint($translator->translate('hint.this.field.is.required')); ?>         
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'mb-3 input-group']); ?>
        <?php echo Select::tag()
            ->addAttributes([
                'class' => 'form-control',
            ])
            ->name('email_template_pdf_template')
            ->value(Html::encode($form->getEmail_template_pdf_template() ?? 'invoice'))
            ->form('EmailTemplateForm')
            ->optionsData($invoiceTemplates)
            ->prompt($translator->translate('none'))
            ->required(true)
            ->disabled(false)
            // allow selecting multiple values
            ->multiple(false);
?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
        <?= Html::openTag('div', ['class' => 'html-tags btn-group btn-group-sm']); ?>
            <?= Span::tag()
        ->addClass('html-tag btn btn-default')
        ->addAttributes(['data-tag-type' => 'text-paragraph'])
        ->content(I::tag()->addClass('fa fa-fw fa-paragraph'))
        ->render();
?>
            <?= Span::tag()
    ->addClass('html-tag btn btn-default')
    ->addAttributes(['data-tag-type' => 'text-linebreak'])
    ->content(Html::b(NoEncode::string('&lt;br&gt;')))
    ->render();
?> 
            <?= Span::tag()
    ->addClass('html-tag btn btn-default')
    ->addAttributes(['data-tag-type' => 'text-bold'])
    ->content(I::tag()->addClass('fa fa-fw fa-bold')->content('b'))
    ->render();
?>
            <?= Span::tag()
    ->addClass('html-tag btn btn-default')
    ->addAttributes(['data-tag-type' => 'text-italic'])
    ->content(I::tag()->addClass('fa fa-fw fa-italic'))
    ->render();
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'html-tags btn-group btn-group-sm']); ?>
            <?= Span::tag()
    ->addClass('html-tag btn btn-default')
    ->addAttributes(['data-tag-type' => 'text-h1'])
    ->content('H1')
    ->render();
?>
            <?= Span::tag()
    ->addClass('html-tag btn btn-default')
    ->addAttributes(['data-tag-type' => 'text-h2'])
    ->content('H2')
    ->render();
?>
            <?= Span::tag()
    ->addClass('html-tag btn btn-default')
    ->addAttributes(['data-tag-type' => 'text-h3'])
    ->content('H3')
    ->render();
?>
            <?= Span::tag()
    ->addClass('html-tag btn btn-default')
    ->addAttributes(['data-tag-type' => 'text-h4'])
    ->content('H4')
    ->render();
?>            
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'html-tags btn-group btn-group-sm']); ?>
            <?= Span::tag()
    ->addClass('html-tag btn btn-default')
    ->addAttributes(['data-tag-type' => 'text-code'])
    ->content(I::tag()->addClass('fa fa-fw fa-code'))
    ->render();
?>
            <?= Span::tag()
    ->addClass('html-tag btn btn-default')
    ->addAttributes(['data-tag-type' => 'text-hr'])
    ->content(Html::b(NoEncode::string('&lt;hr&gt;')))
    ->render();
?>
            <?= Span::tag()
    ->addClass('html-tag btn btn-default')
    ->addAttributes(['data-tag-type' => 'text-css'])
    ->content('CSS')
    ->render();
?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
        <?= Field::textarea($form, 'email_template_body', theme: 'A')
->required(true)
->addInputAttributes([
    'class' => 'email-template-body form-control taggable',
    'rows' => '20',
])
->value($form->getEmail_template_body() ?? '')
->hint($translator->translate('hint.this.field.is.required')); ?>
    <?= Html::closeTag('div'); ?>
    <?php
        /**
         * Templates can be viewed from the Email Template index i.e. preview
         * Related logic: see https://github.com/rossaddison/invoice/issues/12
         */
?>
    <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
       <?=
        $email_template_tags;
?>  
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= $button::backSave(); ?>
<?= Form::tag()->close(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>