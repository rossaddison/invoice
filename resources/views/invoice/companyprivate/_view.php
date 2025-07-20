<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\CompanyPrivate\CompanyPrivateForm $form
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Widget\Button $button
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $company_public
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataCompany
 * @psalm-var array<string,list<string>> $errors
 */

?>
<?= Html::openTag('h1');?>
    <?= Html::encode($title); ?>
<?=Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
<?= $translator->translate('setting.company.private'); ?>
<?= Html::closeTag('h1'); ?>

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('CompanyPrivateForm')
    ->open() ?>

    <?= Html::openTag('div', ['id' => 'headerbar']); ?>
        <?= Html::openTag('h1', ['class' => 'headerbar-title']); ?>
            <?= $translator->translate('view'); ?>
        <?= Html::closeTag('h1'); ?>
        <?= Html::openTag('div', ['id' => 'content']); ?>
            <?= Html::openTag('div', ['class' => 'row']); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'company_public_name')
                        ->readonly(true); ?>
                <?= Html::closeTag('div'); ?>    
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'tax_code')
                        ->readonly(true); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'iban')
                        ->readonly(true); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'gln')
                        ->addInputAttributes(['class' => 'form-control'])
                        ->label($translator->translate('gln'))
                        ->readonly(true)
                        ->value(Html::encode($form->getGln() ??  '')); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'rcc')
                        ->readonly(true); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'container-fluid px-1']); ?>
                    <?= Html::openTag('div', ['class' => 'p-3 border bg-light']); ?>
                        <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                            <?= Field::text($form, 'logo_filename')
                                ->readonly(true)
                                ->value($form->getLogo_filename()); ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                            <?= Field::image()
                               ->src('/logo/' . ($form->getLogo_filename() ?? '#'))
                               ->height($form->getLogo_height())
                               ->width($form->getLogo_width()); ?>
                        <?= Html::closeTag('div'); ?>
                    <?= Html::closeTag('div'); ?>  
                <?= Html::Tag('br'); ?> 
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'logo_width')
                        ->readonly(true); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'logo_height')
                        ->readonly(true); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'logo_margin')
                        ->readonly(true); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Html::openTag('div', ['class' => 'input-group']); ?>               
                        <?= Field::date($form, 'start_date')
                            ->addInputAttributes(
                                [
                                    'class' => 'form-control',
                                    'placeholder' => ' (' . $dateHelper->display() . ')',
                                    'readonly' => 'readonly',
                                    'disabled' => 'disabled',
                                ],
                            )
                            ->value(Html::encode(!is_string($startdate = $form->getStart_date()) && null !== $startdate
                                                ? $startdate->format('Y-m-d')
                                                : (new \DateTimeImmutable('now'))->format('Y-m-d')));
?>
                    <?= Html::closeTag('div'); ?>                                
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Html::openTag('div', ['class' => 'input-group']); ?>               
                        <?= Field::date($form, 'end_date')
    ->addInputAttributes(
        [
            'class' => 'form-control',
            'placeholder' => ' (' . $dateHelper->display() . ')',
            'readonly' => 'readonly',
            'disabled' => 'disabled',
        ],
    )
    ->value(Html::encode(!is_string($enddate = $form->getEnd_date()) && null !== $enddate
                        ? $enddate->format('Y-m-d')
                        : (new \DateTimeImmutable('now'))->format('Y-m-d')));
?>
                    <?= Html::closeTag('div'); ?>                                
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>    
    <?= Html::closeTag('div'); ?>
<?= $button::back(); ?>
<?= Form::tag()->close() ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
