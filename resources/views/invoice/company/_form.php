<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Company\CompanyForm $form
 * @var App\Widget\Button $button
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
    <?= Html::encode($title.' '. $companyPublic); ?>
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
                    <?= Field::hidden($form, 'id')
    ->addInputAttributes([
        'class' => 'form-control'
    ])
    ->hideLabel()
    ->value(Html::encode($form->getId() ??  ''));
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'form-check form-switch']); ?>
                    <?= Field::checkbox($form, 'current')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('active'))
?>    
                <?= Html::closeTag('div'); ?>    
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'name')
    ->label($translator->translate('name'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('name'),
        'class' => 'form-control'
    ])
    ->required(true)
    ->value(Html::encode($form->getName() ?? ''))
    ->hint($translator->translate('hint.this.field.is.required'));
?>    
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::email($form, 'email')
    ->label($translator->translate('email'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('email'),
        'class' => 'form-control'
    ])
    ->required(true)
    ->value(Html::encode($form->getEmail() ?? ''))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'web')
    ->label($translator->translate('web'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('web'),
        'class' => 'form-control'
    ])
    ->value(Html::encode($form->getWeb() ?? ''))
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'address_1')
    ->label($translator->translate('street.address'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('street.address'),
        'class' => 'form-control'
    ])
    ->value(Html::encode($form->getAddress_1() ?? ''))
?>    
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'address_2')
    ->label($translator->translate('street.address.2'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('street.address.2'),
        'class' => 'form-control'
    ])
    ->value(Html::encode($form->getAddress_2() ?? ''))
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'city')
    ->label($translator->translate('city'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('city'),
        'class' => 'form-control'
    ])
    ->value(Html::encode($form->getCity() ?? ''))
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'state')
    ->label($translator->translate('state'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('state'),
        'class' => 'form-control'
    ])
    ->value(Html::encode($form->getState() ?? ''))
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'zip')
    ->label($translator->translate('zip'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('zip'),
        'class' => 'form-control'
    ])
    ->value(Html::encode($form->getZip() ?? ''))
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'country')
    ->label($translator->translate('country'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('country'),
        'class' => 'form-control'
    ])
    ->value(Html::encode($form->getCountry() ?? ''))
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::telephone($form, 'phone')
    ->label($translator->translate('phone'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('phone'),
        'class' => 'form-control'
    ])
    ->value(Html::encode($form->getPhone() ?? ''))
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::telephone($form, 'fax')
    ->label($translator->translate('fax'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('fax'),
        'class' => 'form-control'
    ])
    ->value(Html::encode($form->getFax() ?? ''))
?>
                <?= Html::closeTag('div'); ?>                
            <?= Html::closeTag('div'); ?>        
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>
