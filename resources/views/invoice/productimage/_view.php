<?php

declare(strict_types=1);


use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\ProductImage\ProductImageForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */
?>

<?=  new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('ProductImageForm')
    ->open() ?>

<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>    
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
               <?= Html::closeTag('div'); ?>
                    <?= Html::openTag('div'); ?>
                        <?= Field::hidden($form, 'product_id')
                            ->hideLabel(true);
?>    
                    <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::date($form, 'uploaded_date')
->addInputAttributes([
    'readonly' => 'readonly',
    'disabled' => 'disabled',
])
->label($translator->translate('date'))
->required(true)
->value($form->getUploadedDate() instanceof DateTimeImmutable ? ($form->getUploadedDate())->format('Y-m-d') : '')
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'file_name_original')
    ->disabled(true); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'file_name_new')
    ->disabled(true); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'description')
    ->disabled(true); ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?=  new Form()->close() ?>
