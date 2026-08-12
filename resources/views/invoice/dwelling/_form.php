<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Dwelling\DwellingForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $families
 */

?>
<?= Html::openTag('h1'); ?>
    <?= Html::encode($title); ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
    <?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
        <?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
            <?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
                <?= Html::openTag('div', ['class' => 'card-header']); ?>
                    <?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
                        <?= $title; ?>
                    <?= Html::closeTag('h1'); ?>
                    <?=  new Form()
                        ->post($urlGenerator->generate($actionName, $actionArguments))
                        ->enctypeMultipartFormData()
                        ->csrf($csrf)
                        ->id('DwellingForm')
                        ->open(); ?>
                        <?= $button::backSave(); ?>
                        <?= Html::openTag('div', ['class' => 'container']); ?>
                            <?= Html::openTag('div', ['class' => 'row']); ?>
                                <?= Html::openTag('div', ['class' => 'col card mb-3']); ?>
                                    <?= Html::openTag('div', ['class' => 'card-header']); ?>
                                       <?= Html::openTag('h5'); ?>
                                            <?= Html::encode($title) ?>
                                       <?= Html::closeTag('h5'); ?>
                                       <?= Html::openTag('div'); ?>
                                            <?= Field::select($form, 'family_id')
                                               ->addInputAttributes(['class' => 'form-control form-control-lg',])
                                               ->value($form->getFamilyId())
                                               ->label($translator->translate('dwelling.family'))
                                               ->prompt($translator->translate('none'))
                                               ->optionsData($families); ?>
                                       <?= Html::closeTag('div'); ?>
                                       <?= Html::openTag('div'); ?>
                                            <?= Field::text($form, 'house_number_numeric')
                                                ->label($translator->translate('dwelling.house.number'))
                                                ->addInputAttributes(['class' => 'form-control form-control-lg', 'type' => 'number'])
                                                ->value((string) ($form->getHouseNumberNumeric() ?? '')); ?>
                                       <?= Html::closeTag('div'); ?>
                                       <?= Html::openTag('div'); ?>
                                            <?= Field::text($form, 'house_number_suffix')
                                                ->label($translator->translate('dwelling.house.number.suffix'))
                                                ->addInputAttributes(['class' => 'form-control form-control-lg', 'maxlength' => 5])
                                                ->value(Html::encode($form->getHouseNumberSuffix() ?? ''))
                                                ->placeholder('A'); ?>
                                       <?= Html::closeTag('div'); ?>
                                       <?= Html::openTag('div'); ?>
                                            <?= Field::text($form, 'flat_unit')
                                                ->label($translator->translate('dwelling.flat.unit'))
                                                ->addInputAttributes(['class' => 'form-control form-control-lg'])
                                                ->value(Html::encode($form->getFlatUnit() ?? ''))
                                                ->placeholder($translator->translate('dwelling.flat.unit')); ?>
                                       <?= Html::closeTag('div'); ?>
                                       <?= Html::openTag('div'); ?>
                                            <?= Field::text($form, 'postcode')
                                                ->label($translator->translate('dwelling.postcode'))
                                                ->addInputAttributes(['class' => 'form-control form-control-lg', 'maxlength' => 10])
                                                ->value(Html::encode($form->getPostcode() ?? ''))
                                                ->placeholder($translator->translate('dwelling.postcode')); ?>
                                       <?= Html::closeTag('div'); ?>
                                       <?= Html::openTag('div'); ?>
                                            <?= Field::text($form, 'latitude')
                                                ->label($translator->translate('dwelling.latitude'))
                                                ->addInputAttributes(['class' => 'form-control form-control-lg', 'type' => 'number', 'step' => 'any'])
                                                ->value($form->getLatitude() !== null ? (string) $form->getLatitude() : ''); ?>
                                       <?= Html::closeTag('div'); ?>
                                       <?= Html::openTag('div'); ?>
                                            <?= Field::text($form, 'longitude')
                                                ->label($translator->translate('dwelling.longitude'))
                                                ->addInputAttributes(['class' => 'form-control form-control-lg', 'type' => 'number', 'step' => 'any'])
                                                ->value($form->getLongitude() !== null ? (string) $form->getLongitude() : ''); ?>
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
