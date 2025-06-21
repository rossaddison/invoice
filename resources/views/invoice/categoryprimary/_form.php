<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\CategoryPrimary\CategoryPrimaryForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string, list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $category_primaries
 */

?><?= Html::openTag('h1'); ?>
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
    <?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
        <?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
            <?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
                <?= Html::openTag('div',['class'=>'card-header']); ?>
                    <?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>
                        <?= $translator->translate('add'); ?>
                    <?= Html::closeTag('h1'); ?>
                    <?= Form::tag()->post($urlGenerator->generate($actionName, $actionArguments))->enctypeMultipartFormData()->csrf($csrf)->id('CategoryPrimaryForm')->open();?>
                        <?= $button::backSave(); ?>
                        <?= Html::openTag('div', ['class' => 'container']); ?>
                            <?= Html::openTag('div', ['class' => 'row']); ?>
                                <?= Html::openTag('div', ['class' => 'col card mb-3']); ?>
                                    <?= Html::openTag('div',['class' => 'card-header']); ?>
                                        <?= Html::openTag('h5'); ?>
                                            <?= Html::encode($title) ?>
                                        <?= Html::closeTag('h5'); ?>
                                        <?= Html::openTag('div'); ?>
                                            <?= Field::text($form,'name')
                                                ->label($translator->translate('name'))
                                                ->addInputAttributes([
                                                    'class' => 'form-control'
                                                ])
                                                ->value(Html::encode($form->getname()))
                                                ->placeholder($translator->translate('name')); ?>
                                        <?= Html::closeTag('div'); ?>
                                   <?= Html::closeTag('div'); ?>     
                                <?= Html::closeTag('div'); ?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('div'); ?>    
                    <?= Html::closeTag('form'); ?>
               <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>