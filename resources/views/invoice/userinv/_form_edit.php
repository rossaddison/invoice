<?php

declare(strict_types=1);

use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\UserInv\UserInvForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\User\UserRepository $uR
 * @var App\Invoice\UserInv\UserInvRepository $uiR
 * @var App\Widget\Button $button
 * @var Yiisoft\Aliases\Aliases $aliases
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\View $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $actionName
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @var string $csrf
 * @psalm-var array<string,list<string>> $errors
 * @var string $title
 */

?>

<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('UserInvForm')
    ->open() ?>

<?= Html::openTag('div', ['class' => 'headerbar']); ?>
        <?= Html::openTag('h1', ['class' => 'headerbar-title']); ?>
            <?= Html::encode($title) ?>
        <?= Html::closeTag('h1'); ?>
        <?= $button::backSave(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::openTag('div', ['id' => 'content']); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group has-feedback']); ?>
            <?= Field::errorSummary($form)
                ->errors($errors)
                ->header($translator->translate('error.summary'))
                ->onlyProperties(...[''])
                ->onlyCommonErrors()
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group no-margin']); ?>
            <?php
   echo Field::text($form, 'user_id')
    ->label($translator->translate('users'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'user_id',
    ])
    ->readonly(true)
    ->value(Html::encode($form->getUser_id() ?? ''))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php
  $types = [
      0 => $translator->translate('administrator'),
      1 => $translator->translate('guest.read.only'),
  ]
?>
            <?php
    $optionsDataType = [];
foreach ($types as $key => $value) {
    $optionsDataType[$key] = $value;
}
echo Field::select($form, 'type')
->label($translator->translate('type'))
->addInputAttributes([
    'class' => 'form-control',
    'id' => 'type',
])
->optionsData($optionsDataType)
->value(Html::encode($form->getType() ?? 1))
->hint($translator->translate('hint.this.field.is.required'));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Html::openTag('div', ['class' => 'p-2']); ?> 
                <?= Field::checkbox($form, 'active')
        ->inputLabelAttributes(['class' => 'form-check-label'])
        ->inputClass('form-check-input')
        ->ariaDescribedBy($translator->translate('active'));
?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?><?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::checkbox($form, 'all_clients')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('user.all.clients'));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group no-margin']); ?>
            <?php
    $optionsDataLanguage = [];
/** @var string $language */
foreach (ArrayHelper::map($s->expandDirectoriesMatrix($aliases->get('@language'), 0), 'name', 'name') as $language) {
    $optionsDataLanguage[$language] = ucfirst($language);
}
echo Field::select($form, 'language')
->label($translator->translate('language'))
->addInputAttributes([
    'class' => 'form-control',
    'id' => 'language',
])
->optionsData($optionsDataLanguage)
->value(Html::encode($form->getLanguage() ?? ''))
->hint($translator->translate('hint.this.field.is.required'));
?>
        <?= Html::closeTag('div'); ?>   
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'name')
    ->label($translator->translate('name'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('name'),
        'class' => 'form-control',
        'id' => 'name',
    ])
    ->value(Html::encode($form->getName() ?? ''))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'company')
    ->label($translator->translate('company'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('company'),
        'class' => 'form-control',
        'id' => 'company',
    ])
    ->value(Html::encode($form->getCompany() ?? ''));
?>
        <?= Html::closeTag('div'); ?>   
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'address_1')
    ->label($translator->translate('street.address'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('street.address'),
        'class' => 'form-control',
        'id' => 'address_1',
    ])
    ->value(Html::encode($form->getAddress_1() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'address_2')
    ->label($translator->translate('street.address.2'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('street.address'),
        'class' => 'form-control',
        'id' => 'address_2',
    ])
    ->value(Html::encode($form->getAddress_2() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'city')
    ->label($translator->translate('city'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('city'),
        'class' => 'form-control',
        'id' => 'city',
    ])
    ->value(Html::encode($form->getCity() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'state')
    ->label($translator->translate('state'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('state'),
        'class' => 'form-control',
        'id' => 'state',
    ])
    ->value(Html::encode($form->getState() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'zip')
    ->label($translator->translate('zip'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('zip'),
        'class' => 'form-control',
        'id' => 'zip',
    ])
    ->value(Html::encode($form->getZip() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'country')
    ->label($translator->translate('country'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('country'),
        'class' => 'form-control',
        'id' => 'country',
    ])
    ->value(Html::encode($form->getCountry() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::telephone($form, 'phone')
    ->label($translator->translate('phone'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('phone'),
        'class' => 'form-control',
        'id' => 'phone',
    ])
    ->value(Html::encode($form->getPhone() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::telephone($form, 'fax')
    ->label($translator->translate('fax'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('fax'),
        'class' => 'form-control',
        'id' => 'fax',
    ])
    ->value(Html::encode($form->getFax() ?? ''));
?>
        <?= Html::closeTag('div'); ?>        
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::email($form, 'user')
    ->label($translator->translate('email'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('email'),
        'class' => 'form-control',
        'id' => 'email',
    ])
    ->disabled(true)
    ->value(Html::encode($form->getUser()?->getEmail() ?? '#'));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
           <?= Field::telephone($form, 'mobile')
    ->label($translator->translate('mobile'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('mobile'),
        'class' => 'form-control',
        'id' => 'mobile',
    ])
    ->value(Html::encode($form->getMobile() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'web')
    ->label($translator->translate('web.address'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('web.address'),
        'class' => 'form-control',
        'id' => 'web',
    ])
    ->value(Html::encode($form->getWeb() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'tax_code')
    ->label($translator->translate('tax.code'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('tax.code'),
        'class' => 'form-control',
        'id' => 'tax_code',
    ])
    ->value(Html::encode($form->getTax_code() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'subscribernumber')
    ->label($translator->translate('user.subscriber.number'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('user.subscriber.number'),
        'class' => 'form-control',
        'id' => 'subscribernumber',
    ])
    ->value(Html::encode($form->getSubscribernumber() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'iban')
    ->label($translator->translate('user.iban'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('user.iban'),
        'class' => 'form-control',
        'id' => 'iban',
    ])
    ->value(Html::encode($form->getSubscribernumber() ?? ''));
?>
         <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'gln')
    ->label($translator->translate('delivery.location.global.location.number'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('delivery.location.global.location.number'),
        'class' => 'form-control',
        'id' => 'gln',
    ])
    ->value(Html::encode($form->getGln() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'rcc')
    ->label($translator->translate('sumex.rcc'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('sumex.rcc'),
        'class' => 'form-control',
        'id' => 'rcc',
    ])
    ->value(Html::encode($form->getRcc() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'listLimit')
    ->label($translator->translate('user.inv.list.limit'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('user.inv.list.limit'),
        'class' => 'form-control',
        'id' => 'listLimit',
    ])
    ->value(Html::encode($form->getListLimit() ?? 10));
?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>
