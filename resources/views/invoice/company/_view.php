<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Invoice\Company\CompanyForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $companyPublic
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */
?>

<?php echo Html::openTag('h1'); ?>
    <?php echo Html::encode($title.' '.$companyPublic); ?>
<?php echo Html::closeTag('h1'); ?>


<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('CompanyForm')
    ->open(); ?>

    <?php echo Html::openTag('div', ['class' => 'headerbar']); ?>
        <?php echo $button::back(); ?> 
        <?php echo Html::openTag('div', ['id' => 'content']); ?>
            <?php echo Html::openTag('div', ['class' => 'row']); ?>
                <?php echo Html::closeTag('div'); ?>    
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::hidden($form, 'id')
                    ->addInputAttributes([
                        'class' => 'form-control',
                    ])
                    ->hideLabel()
                    ->value(Html::encode($form->getId() ?? ''));
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'form-check form-switch']); ?>
                    <?php echo Field::checkbox($form, 'current')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('active'))
    ->disabled(true);
?>    
                <?php echo Html::closeTag('div'); ?>    
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::text($form, 'name')
                    ->label($translator->translate('name'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('name'),
                        'class'       => 'form-control',
                    ])
                    ->required(true)
                    ->value(Html::encode($form->getName() ?? ''))
                    ->disabled(true);
?>    
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::email($form, 'email')
                    ->label($translator->translate('email'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('email'),
                        'class'       => 'form-control',
                    ])
                    ->required(true)
                    ->value(Html::encode($form->getEmail() ?? ''))
                    ->disabled(true);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::text($form, 'web')
    ->label($translator->translate('web'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('web'),
        'class'       => 'form-control',
    ])
    ->value(Html::encode($form->getWeb() ?? ''))
    ->disabled(true);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::text($form, 'address_1')
    ->label($translator->translate('street.address'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('street.address'),
        'class'       => 'form-control',
    ])
    ->value(Html::encode($form->getAddress_1() ?? ''))
    ->disabled(true);
?>    
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::text($form, 'address_2')
                    ->label($translator->translate('street.address.2'))
                    ->addInputAttributes([
                        'placeholder' => $translator->translate('street.address.2'),
                        'class'       => 'form-control',
                    ])
                    ->value(Html::encode($form->getAddress_2() ?? ''))
                    ->disabled(true);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::text($form, 'city')
    ->label($translator->translate('city'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('city'),
        'class'       => 'form-control',
    ])
    ->value(Html::encode($form->getCity() ?? ''))
    ->disabled(true);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::text($form, 'state')
    ->label($translator->translate('state'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('state'),
        'class'       => 'form-control',
    ])
    ->value(Html::encode($form->getState() ?? ''))
    ->disabled(true);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::text($form, 'zip')
    ->label($translator->translate('zip'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('zip'),
        'class'       => 'form-control',
    ])
    ->value(Html::encode($form->getZip() ?? ''))
    ->disabled(true);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::text($form, 'country')
    ->label($translator->translate('country'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('country'),
        'class'       => 'form-control',
    ])
    ->value(Html::encode($form->getCountry() ?? ''))
    ->disabled(true);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::telephone($form, 'phone')
    ->label($translator->translate('phone'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('phone'),
        'class'       => 'form-control',
    ])
    ->value(Html::encode($form->getPhone() ?? ''))
    ->disabled(true);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::telephone($form, 'fax')
    ->label($translator->translate('fax'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('fax'),
        'class'       => 'form-control',
    ])
    ->value(Html::encode($form->getFax() ?? ''))
    ->disabled(true);
?>
                <?php echo Html::closeTag('div'); ?>                
            <?php echo Html::closeTag('div'); ?>        
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Form::tag()->close(); ?>

