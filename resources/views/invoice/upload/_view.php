<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Invoice\Upload\UploadForm $form
 * @var App\Widget\Button $button
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Translator\TranslatorInterface $translator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $actionName
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @var string $csrf
 * @var string $title
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClients
 */
?>

<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('UploadForm')
    ->open();
?>

<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>

<?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>    
    <?php echo Html::encode($title); ?>
<?php echo Html::closeTag('h1'); ?>
<?php echo Html::openTag('div', ['id' => 'headerbar']); ?>
    <?php echo $button::back(); ?>
    <?php echo Html::openTag('div', ['id' => 'content']); ?>
        <?php echo Html::openTag('div', ['class' => 'row']); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::select($form, 'client_id')
        ->label($translator->translate('clients'))
        ->optionsData($optionsDataClients)
        ->disabled(true);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::text($form, 'url_key')
    ->label($translator->translate('upload.url.key'))
    ->value(Html::encode($form->getUrl_key()))
    ->disabled(true);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::text($form, 'file_name_original')
    ->label($translator->translate('upload.filename.original'))
    ->value(Html::encode($form->getFile_name_original()))
    ->disabled(true);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::text($form, 'file_name_new')
    ->label($translator->translate('upload.filename.new'))
    ->value(Html::encode($form->getFile_name_new()))
    ->disabled(true);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::text($form, 'description')
    ->label($translator->translate('upload.description'))
    ->value(Html::encode($form->getDescription()))
    ->disabled(true);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::date($form, 'uploaded_date')
    ->label($translator->translate('date'))
    ->required(true)
    ->value($form->getUploaded_date() instanceof DateTimeImmutable ? ($form->getUploaded_date())->format('Y-m-d') : '')
    ->disabled(true);
?>
            <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Form::tag()->close(); ?>