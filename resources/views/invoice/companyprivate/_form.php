<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\CompanyPrivate\CompanyPrivateForm $form
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Widget\Button $button
 * @var Yiisoft\View\View $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $companies
 * @var string $company_public
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataCompany
 * @psalm-var array<string,list<string>> $errors
 */

?>
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
            <?= $title; ?>
        <?= Html::closeTag('h1'); ?>
        <?= Html::openTag('div', ['id' => 'content']); ?>
            <?= Html::openTag('div', ['class' => 'row']); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::errorSummary($form)
                        ->errors($errors)
                        ->header($translator->translate('error.summary'))
                        ->onlyCommonErrors()
?>
                <?= Html::closeTag('div'); ?>    
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?php
    $optionsDataCompany = [];
/**
 * @var App\Invoice\Entity\Company $company
 */
foreach ($companies as $company) {
    if (null !== ($companyId = $company->getId()) && null !== ($companyName = $company->getName())) {
        $optionsDataCompany[(string) $companyId] = $companyName;
    }
}
?>
                    <?=
    Field::select($form, 'company_id')
    ->label($company_public)
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'company_id',
    ])
    ->optionsData($optionsDataCompany)
    ->required(true)
    ->hint($translator->translate('hint.this.field.is.required'));
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::hidden($form, 'id')
    ->addInputAttributes(['class' => 'form-control'])
    ->hideLabel()
    ->value(Html::encode($form->getId() ??  '')); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'tax_code')
    ->addInputAttributes(['class' => 'form-control'])
    ->label($translator->translate('tax.code'))
    ->value(Html::encode($form->getTax_code() ??  '')); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'iban')
    ->addInputAttributes(['class' => 'form-control'])
    ->label($translator->translate('user.iban'))
    ->value(Html::encode($form->getIban() ??  '')); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'gln')
    ->addInputAttributes(['class' => 'form-control'])
    ->label($translator->translate('gln'))
    ->value(Html::encode($form->getGln() ??  '')); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'rcc')
    ->addInputAttributes(['class' => 'form-control'])
    ->label($translator->translate('sumex.rcc'))
    ->value(Html::encode($form->getRcc() ??  '')); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?> 
                    <?= Html::openTag('label'); ?>
                        <?= Html::encode($form->getLogo_filename()); ?>
                    <?= Html::closeTag('label'); ?>
                    <?= Field::file($form, 'logo_filename')
    ->accept('image/*')
    ->value(Html::encode($form->getLogo_filename())); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'logo_width')
    ->addInputAttributes(['class' => 'form-control'])
    ->label($translator->translate('company.private.logo.width'))
    ->value(Html::encode($form->getLogo_width() ??  '')); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'logo_height')
    ->addInputAttributes(['class' => 'form-control'])
    ->label($translator->translate('company.private.logo.height'))
    ->value(Html::encode($form->getLogo_height() ??  '')); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Field::text($form, 'logo_margin')
    ->addInputAttributes(['class' => 'form-control'])
    ->label($translator->translate('company.private.logo.margin'))
    ->value(Html::encode($form->getLogo_margin() ??  '')); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Html::openTag('div', ['class' => 'input-group']); ?>               
                        <?= Field::date($form, 'start_date')
        ->addInputAttributes(
            [
                'class' => 'form-control',
                'placeholder' => ' (' . $dateHelper->display() . ')',
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
<?= $button::backSave(); ?>
<?= Form::tag()->close() ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
