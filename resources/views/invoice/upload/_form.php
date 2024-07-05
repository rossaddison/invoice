<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Invoice\Upload\UploadForm $form
 * @var App\Widget\Button $button 
 * @var \Yiisoft\View\View $this 
 * @var \Yiisoft\Translator\TranslatorInterface $translator 
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $actionName 
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @var string $csrf
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClients
 * @var string $title
 */
?>

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('UploadForm')
    ->open();
?>

<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>

<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>    
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back_save(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('invoice.error.summary'))
                    ->onlyCommonErrors()
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::select($form, 'client_id')
                    ->label($translator->translate('i.clients'))
                    ->addInputAttributes(['readonly' => 'readonly'])
                    ->optionsData($optionsDataClients)
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'url_key')
                    ->label($translator->translate('invoice.upload.url.key'))
                    ->value(Html::encode($form->getUrl_key()))
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'file_name_original')
                    ->label($translator->translate('invoice.upload.filename.original'))
                    ->value(Html::encode($form->getFile_name_original()))
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'file_name_new')
                    ->label($translator->translate('invoice.upload.filename.new'))
                    ->value(Html::encode($form->getFile_name_new()))
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'description')
                    ->label($translator->translate('invoice.upload.description'))
                    ->value(Html::encode($form->getDescription()))
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::date($form, 'uploaded_date')
                    ->label($translator->translate('i.date'))
                    ->required(true)
                    ->value($form->getUploaded_date() instanceof \DateTimeImmutable ? ($form->getUploaded_date())->format('Y-m-d') : '')
                    ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                ?>
            <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>