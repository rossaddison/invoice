<?php
declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Merchant\MerchantForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $invs
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataInv
 */
?>

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('MerchantForm')
    ->open() ?>

<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>

<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>    
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('invoice.error.summary'))
                    ->onlyCommonErrors()
                ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'inv')
                    ->label($translator->translate('invoice.invoice.number'))
                    ->addInputAttributes([
                        'readonly' => 'readonly',
                        'disabled' => 'disabled'
                    ])        
                    ->placeholder($translator->translate('invoice.successful'))    
                    ->value(Html::encode($form->getInv()?->getNumber() ?? $translator->translate('i.reason.uknown'))) 
                ?>
                <?= Html::closeTag('div'); ?>    
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::checkbox($form, 'successful')
                    ->inputLabelAttributes(['class' => 'form-check-label'])    
                    ->enclosedByLabel(true)
                    ->addInputAttributes([
                            'readonly' => 'readonly',
                            'disabled' => 'disabled'
                    ])                
                    ->inputClass('form-check-input')
                    ->ariaDescribedBy($translator->translate('invoice.successful'))
                ?>        
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::date($form, 'date')
                    ->label($translator->translate('i.date'))
                    ->addInputAttributes([
                        'readonly' => 'readonly',
                        'disabled' => 'disabled'
                    ])    
                    ->value(!is_string($form->getDate()) ? ($form->getDate())->format('Y-m-d') : '') 
                ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'driver')
                    ->label($translator->translate('invoice.merchant.driver'))
                    ->placeholder($translator->translate('invoice.merchant.driver'))
                    ->addInputAttributes([
                        'readonly' => 'readonly',
                        'disabled' => 'disabled'
                    ])        
                    ->value(Html::encode($form->getDriver() ?? '')) 
                ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'response')
                    ->label($translator->translate('invoice.merchant.response'))
                    ->placeholder($translator->translate('invoice.merchant.response'))
                    ->addInputAttributes([
                        'readonly' => 'readonly',
                        'disabled' => 'disabled'
                    ])        
                    ->value(Html::encode($form->getResponse() ?? '')) 
                ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'reference')
                    ->label($translator->translate('invoice.merchant.reference'))
                    ->placeholder($translator->translate('invoice.merchant.reference'))
                    ->addInputAttributes([
                        'readonly' => 'readonly',
                        'disabled' => 'disabled'
                    ])        
                    ->value(Html::encode($form->getReference() ?? '')) 
                ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>