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

<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('CompanyForm')
    ->open() ?>

    <?= Html::openTag('div', ['class' => 'headerbar']); ?>
        <?= $button::back($translator); ?> 
        <?= Html::openTag('div',['id' => 'content']); ?>
            <?= Html::openTag('div', ['class' => 'row']); ?>
                <?= Html::closeTag('div'); ?>    
                <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
                    <?= Field::hidden($form, 'id')
                        ->addInputAttributes([
                            'class' => 'form-control',
                        ])
                        ->hideLabel()
                        ->value(Html::encode($form->getId() ??  ''))
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'form-check form-switch']); ?>
                    <?= Field::checkbox($form, 'current')
                        ->inputLabelAttributes(['class' => 'form-check-label'])    
                        ->enclosedByLabel(true)
                        ->inputClass('form-check-input')
                        ->ariaDescribedBy($translator->translate('i.active'))
                        ->disabled(true)
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
                        ->disabled(true)
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
                        ->disabled(true);
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
                        ->disabled(true)
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
                        ->disabled(true)
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
                        ->disabled(true)
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
                        ->disabled(true)
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
                        ->disabled(true)
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
                        ->disabled(true)
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
                        ->disabled(true)
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
                        ->disabled(true)
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
                        ->disabled(true)
                    ?>
                <?= Html::closeTag('div'); ?>                
            <?= Html::closeTag('div'); ?>        
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>

