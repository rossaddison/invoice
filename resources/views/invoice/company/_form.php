<?php

declare(strict_types=1); 

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

<?= Html::openTag('h1'); ?>
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>

<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('CompanyForm')
    ->open() ?>

    <?= Html::openTag('div', ['class' => 'headerbar']); ?>
        <?= $button::back_save(); ?> 
        <?= Html::openTag('div',['id' => 'content']); ?>
            <?= Html::openTag('div', ['class' => 'row']); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>    
                    <?= Field::errorSummary($form)
                        ->errors($errors)
                        ->header($translator->translate('invoice.client.error.summary'))
                        ->onlyCommonErrors()
                    ?>
                <?= Html::closeTag('div'); ?>    
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
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
                        ->enclosedByLabel(true)
                        ->inputClass('form-check-input')
                        ->ariaDescribedBy($translator->translate('i.active'))
                    ?>    
                <?= Html::closeTag('div'); ?>    
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'name')
                        ->label($translator->translate('i.name'))
                        ->addInputAttributes([
                            'placeholder' => $translator->translate('i.name'),
                            'class' => 'form-control'
                        ])
                        ->required(true)
                        ->value(Html::encode($form->getName() ?? ''))
                        ->hint($translator->translate('invoice.hint.this.field.is.required'));
                    ?>    
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::email($form, 'email')
                        ->label($translator->translate('i.email'))
                        ->addInputAttributes([
                            'placeholder' => $translator->translate('i.email'),
                            'class' => 'form-control'
                        ])
                        ->required(true)
                        ->value(Html::encode($form->getEmail() ?? ''))
                        ->hint($translator->translate('invoice.hint.this.field.is.required'));
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'web')
                        ->label($translator->translate('i.web'))
                        ->addInputAttributes([
                            'placeholder' => $translator->translate('i.web'),
                            'class' => 'form-control'
                        ])
                        ->value(Html::encode($form->getWeb() ?? ''))
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'address_1')
                        ->label($translator->translate('i.street_address'))
                        ->addInputAttributes([
                            'placeholder' => $translator->translate('i.street_address'),
                            'class' => 'form-control'
                        ])
                        ->value(Html::encode($form->getAddress_1() ?? ''))
                    ?>    
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'address_2')
                        ->label($translator->translate('i.street_address_2'))
                        ->addInputAttributes([
                            'placeholder' => $translator->translate('i.street_address_2'),
                            'class' => 'form-control'
                        ])
                        ->value(Html::encode($form->getAddress_2() ?? ''))
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'city')
                        ->label($translator->translate('i.city'))
                        ->addInputAttributes([
                            'placeholder' => $translator->translate('i.city'),
                            'class' => 'form-control'
                        ])
                        ->value(Html::encode($form->getCity() ?? ''))
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'state')
                        ->label($translator->translate('i.state'))
                        ->addInputAttributes([
                            'placeholder' => $translator->translate('i.state'),
                            'class' => 'form-control'
                        ])
                        ->value(Html::encode($form->getState() ?? ''))
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'zip')
                        ->label($translator->translate('i.zip'))
                        ->addInputAttributes([
                            'placeholder' => $translator->translate('i.zip'),
                            'class' => 'form-control'
                        ])
                        ->value(Html::encode($form->getZip() ?? ''))
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'country')
                        ->label($translator->translate('i.country'))
                        ->addInputAttributes([
                            'placeholder' => $translator->translate('i.country'),
                            'class' => 'form-control'
                        ])
                        ->value(Html::encode($form->getCountry() ?? ''))
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::telephone($form, 'phone')
                        ->label($translator->translate('i.phone'))
                        ->addInputAttributes([
                            'placeholder' => $translator->translate('i.phone'),
                            'class' => 'form-control'
                        ])
                        ->value(Html::encode($form->getPhone() ?? ''))
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::telephone($form, 'fax')
                        ->label($translator->translate('i.fax'))
                        ->addInputAttributes([
                            'placeholder' => $translator->translate('i.fax'),
                            'class' => 'form-control'
                        ])
                        ->value(Html::encode($form->getFax() ?? ''))
                    ?>
                <?= Html::closeTag('div'); ?>                
            <?= Html::closeTag('div'); ?>        
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>
