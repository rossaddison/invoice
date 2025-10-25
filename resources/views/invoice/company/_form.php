<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Company\CompanyForm $form
 * @var App\Widget\Button $button
 * @var App\Widget\FormFields $formFields
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $companyPublic
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 */

?>

<?= Html::openTag('h1'); ?>
    <?= Html::encode($title . ' ' . $companyPublic); ?>
<?= Html::closeTag('h1'); ?>

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('CompanyForm')
    ->open() ?>

    <?= Html::openTag('div', ['class' => 'headerbar']); ?>
        <?= $button::backSave(); ?> 
        <?= Html::openTag('div', ['id' => 'content']); ?>
            <?= Html::openTag('div', ['class' => 'row']); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>    
                    <?= Field::errorSummary($form)
                        ->errors($errors)
                        ->header($translator->translate('client.error.summary'))
                        ->onlyCommonErrors()
?>
                <?= Html::closeTag('div'); ?>    
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= $formFields->companyHiddenField($form, 'id'); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'form-check form-switch']); ?>
                    <?= $formFields->companyCheckboxField($form, 'current', 'active'); ?>    
                <?= Html::closeTag('div'); ?>    
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= $formFields->companyTextField($form, 'name', 'name', true); ?>    
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= $formFields->companyEmailField($form); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= $formFields->companyTextField($form, 'web', 'web', false); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= $formFields->companyTextField($form, 'address_1', 'street.address', false); ?>    
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= $formFields->companyTextField($form, 'address_2', 'street.address.2', false); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= $formFields->companyTextField($form, 'city', 'city', false); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= $formFields->companyTextField($form, 'state', 'state', false); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= $formFields->companyTextField($form, 'zip', 'zip', false); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= $formFields->companyTextField($form, 'country', 'country', false); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= $formFields->companyTelephoneField($form, 'phone', 'phone'); ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= $formFields->companyTelephoneField($form, 'fax', 'fax'); ?>
                <?= Html::closeTag('div'); ?>                
            <?= Html::closeTag('div'); ?>        
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>
