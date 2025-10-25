<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\CompanyPrivate\CompanyPrivateForm $form
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Widget\Button $button
 * @var App\Widget\FormFields $formFields
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
                    <?= $formFields->companyPrivateCompanySelect($form, $optionsDataCompany, $company_public); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= $formFields->companyPrivateHiddenField($form, 'id'); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= $formFields->companyPrivateTextField($form, 'tax_code', 'tax.code'); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= $formFields->companyPrivateTextField($form, 'iban', 'user.iban'); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= $formFields->companyPrivateTextField($form, 'gln', 'gln'); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= $formFields->companyPrivateTextField($form, 'rcc', 'sumex.rcc'); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?> 
                    <?= Html::openTag('label'); ?>
                        <?= Html::encode($form->getLogo_filename()); ?>
                    <?= Html::closeTag('label'); ?>
                    <?= $formFields->companyPrivateFileField($form, 'logo_filename'); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= $formFields->companyPrivateTextField($form, 'logo_width', 'company.private.logo.width'); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= $formFields->companyPrivateTextField($form, 'logo_height', 'company.private.logo.height'); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= $formFields->companyPrivateTextField($form, 'logo_margin', 'company.private.logo.margin'); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Html::openTag('div', ['class' => 'input-group']); ?>               
                        <?= $formFields->companyPrivateDateField($form, 'start_date', ' (' . $dateHelper->display() . ')'); ?>
                    <?= Html::closeTag('div'); ?>                                
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb3 form-group']); ?>
                    <?= Html::openTag('div', ['class' => 'input-group']); ?>               
                        <?= $formFields->companyPrivateDateField($form, 'end_date', ' (' . $dateHelper->display() . ')'); ?>
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
