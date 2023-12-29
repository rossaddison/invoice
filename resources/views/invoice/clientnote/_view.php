<?php

declare(strict_types=1); 

use App\Widget\Button;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $action
 * @var string $title
 */
?>

<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('ClientNoteForm')
    ->open() ?>

<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>

<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>    
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= Button::back($translator); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div'); ?>
                <?php
                    $date_note = $datehelper->get_or_set_with_style($form->getDate_note() ?? new \DateTimeImmutable('now'));
                ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::date($form, 'date_note')
                    ->addInputAttributes([
                        'readonly' => 'readonly',
                        'disabled' => 'disabled'
                    ])    
                    ->label($translator->translate('i.date'), ['class' => 'form-label'])
                    ->required(true)
                    ->value($form->getDate_note() ? ($form->getDate_note())->format('Y-m-d') : '')
                ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::textarea($form, 'note')
                    ->label($translator->translate('i.note'), ['form-label'])
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('i.note'),
                        'value' => Html::encode($form->getNote() ?? ''),
                        'class' => 'form-control',
                        'id' => 'note',
                        'readonly' => 'readonly',
                        'disabled' => 'disabled'
                    ])
                ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>