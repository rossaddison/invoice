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
        <?= Html::openTag('h1');?>
            <?= Html::encode($s->trans('companies_form')); ?>
        <?=Html::closeTag('h1'); ?>
        <?php $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]); ?>        
        <?php echo (string)$response->getBody(); ?>
        <?= Html::openTag('div',['id' => 'content']); ?>
            <?= Html::openTag('div', ['class' => 'row']); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>    
                    <?= Field::errorSummary($form)
                        ->errors($errors)
                        ->header($translator->translate('invoice.client.error.summary'))
                        ->onlyProperties(...['client_name', 'client_surname', 'client_email', 'client_age'])    
                        ->onlyCommonErrors()
                    ?>
                <?= Html::closeTag('div'); ?>    
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::hidden($form, 'id')
                        ->addInputAttributes([
                            'class' => 'form-control'
                        ])
                        ->label('')
                        ->value(Html::encode($form->getId() ??  ''));
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'form-check form-switch']); ?>
                    <?= Field::checkbox($form, 'current')
                        ->inputLabelAttributes(['class' => 'form-check-label'])    
                        ->enclosedByLabel(true)
                        ->inputClass('form-check-input')
                        ->ariaDescribedBy($s->trans('active'))
                    ?>    
                <?= Html::closeTag('div'); ?>    
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'name')
                        ->label($s->trans('name'))
                        ->addInputAttributes([
                            'placeholder' => $s->trans('name'),
                            'class' => 'form-control'
                        ])
                        ->required(true)
                        ->value(Html::encode($form->getName() ?? ''))
                        ->hint($translator->translate('invoice.hint.this.field.is.required'));
                    ?>    
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::email($form, 'email')
                        ->label($s->trans('email'))
                        ->addInputAttributes([
                            'placeholder' => $s->trans('email'),
                            'class' => 'form-control'
                        ])
                        ->required(true)
                        ->value(Html::encode($form->getEmail() ?? ''))
                        ->hint($translator->translate('invoice.hint.this.field.is.required'));
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'web')
                        ->label($s->trans('web'))
                        ->addInputAttributes([
                            'placeholder' => $s->trans('web'),
                            'class' => 'form-control'
                        ])
                        ->value(Html::encode($form->getWeb() ?? ''))
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'address_1')
                        ->label($s->trans('street_address'))
                        ->addInputAttributes([
                            'placeholder' => $s->trans('street_address'),
                            'class' => 'form-control'
                        ])
                        ->value(Html::encode($form->getAddress_1() ?? ''))
                    ?>    
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'address_2')
                        ->label($s->trans('street_address_2'))
                        ->addInputAttributes([
                            'placeholder' => $s->trans('street_address_2'),
                            'class' => 'form-control'
                        ])
                        ->value(Html::encode($form->getAddress_2() ?? ''))
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'city')
                        ->label($s->trans('city'))
                        ->addInputAttributes([
                            'placeholder' => $s->trans('city'),
                            'class' => 'form-control'
                        ])
                        ->value(Html::encode($form->getCity() ?? ''))
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'state')
                        ->label($s->trans('state'))
                        ->addInputAttributes([
                            'placeholder' => $s->trans('state'),
                            'class' => 'form-control'
                        ])
                        ->value(Html::encode($form->getState() ?? ''))
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'zip')
                        ->label($s->trans('zip'))
                        ->addInputAttributes([
                            'placeholder' => $s->trans('zip'),
                            'class' => 'form-control'
                        ])
                        ->value(Html::encode($form->getZip() ?? ''))
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'country')
                        ->label($s->trans('country'))
                        ->addInputAttributes([
                            'placeholder' => $s->trans('country'),
                            'class' => 'form-control'
                        ])
                        ->value(Html::encode($form->getCountry() ?? ''))
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::telephone($form, 'phone')
                        ->label($s->trans('phone'))
                        ->addInputAttributes([
                            'placeholder' => $s->trans('phone'),
                            'class' => 'form-control'
                        ])
                        ->value(Html::encode($form->getPhone() ?? ''))
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::telephone($form, 'fax')
                        ->label($s->trans('fax'))
                        ->addInputAttributes([
                            'placeholder' => $s->trans('fax'),
                            'class' => 'form-control'
                        ])
                        ->value(Html::encode($form->getFax() ?? ''))
                    ?>
                <?= Html::closeTag('div'); ?>                
            <?= Html::closeTag('div'); ?>        
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>
