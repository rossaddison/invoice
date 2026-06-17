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
 * @var App\Widget\Button $button
 * @var Yiisoft\Aliases\Aliases $aliases
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $users
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\View\View $this
 * @var string $csrf
 * @var string $title
 * @var string $actionName
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

?>

<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?=  new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('UserInvForm')
    ->open() ?>

<?= Html::openTag('div', ['class' => 'headerbar']); ?>
        <?= Html::openTag('h1', ['class' => 'headerbar-title']); ?>
            <?= Html::encode($title) ?>
        <?= Html::closeTag('h1'); ?>
        <?= $button::back(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::openTag('div', ['id' => 'content']); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div', ['class' => 'm-0']); ?>
            <?php
                $optionsDataUser = [];
/**
 * @var App\Infrastructure\Persistence\User\User $user
 */
foreach ($uR->findAllPreloaded() as $user) {
    /**
     * @psalm-suppress PossiblyNullArrayOffset $user->reqId()
     */
    $optionsDataUser[$user->reqId()] = ucfirst($user->getLogin());    
}
echo Field::select($form, 'user_id')
->label($translator->translate('users'))
->addInputAttributes([
    'class' => 'form-control form-control-lg',
    'id' => 'user_id',
    'readonly' => 'readonly',
])
->optionsData($optionsDataUser)
->value(Html::encode($form->user_id ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
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
    'class' => 'form-control form-control-lg',
    'id' => 'type',
    'readonly' => 'readonly',
])
->optionsData($optionsDataType)
->value(Html::encode($form->type))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Html::openTag('div', ['class' => 'p-2']); ?>
                <?= Field::checkbox($form, 'active')
        ->inputLabelAttributes(['class' => 'form-check-label'])
        ->inputClass('form-check-input')
        ->ariaDescribedBy($translator->translate('active'))
?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?><?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::checkbox($form, 'all_clients')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('user.all.clients'));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'm-0']); ?>
            <?php
    $optionsDataLanguage = [];
/**
 * @var string $language
 */
foreach (ArrayHelper::map($s->expandDirectoriesMatrix($aliases->get('@language'), 0), 'name', 'name') as $language) {
    $optionsDataLanguage[$language] = ucfirst($language);
}
echo Field::select($form, 'language')
->label($translator->translate('language'))
->addInputAttributes([
    'class' => 'form-control form-control-lg',
    'id' => 'language',
    'readonly' => 'readonly',
])
->optionsData($optionsDataLanguage)
->value(Html::encode($form->language))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'name')
    ->label($translator->translate('name'))
    ->addInputAttributes([
        'class' => 'form-control form-control-lg',
        'id' => 'name',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->name ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'company')
    ->label($translator->translate('company'))
    ->addInputAttributes([
        'class' => 'form-control form-control-lg',
        'id' => 'company',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->company ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'address_1')
    ->label($translator->translate('street.address'))
    ->addInputAttributes([
        'class' => 'form-control form-control-lg',
        'id' => 'address_1',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->address_1 ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'address_2')
    ->label($translator->translate('street.address.2'))
    ->addInputAttributes([
        'class' => 'form-control form-control-lg',
        'id' => 'address_2',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->address_2 ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'city')
    ->label($translator->translate('city'))
    ->addInputAttributes([
        'class' => 'form-control form-control-lg',
        'id' => 'city',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->city ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'state')
    ->label($translator->translate('state'))
    ->addInputAttributes([
        'class' => 'form-control form-control-lg',
        'id' => 'state',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->state ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'zip')
    ->label($translator->translate('zip'))
    ->addInputAttributes([
        'class' => 'form-control form-control-lg',
        'id' => 'zip',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->zip ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'country')
    ->label($translator->translate('country'))
    ->addInputAttributes([
        'class' => 'form-control form-control-lg',
        'id' => 'country',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->country ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::telephone($form, 'phone')
    ->label($translator->translate('phone'))
    ->addInputAttributes([
        'class' => 'form-control form-control-lg',
        'id' => 'phone',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->phone ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::telephone($form, 'fax')
    ->label($translator->translate('fax'))
    ->addInputAttributes([
        'class' => 'form-control form-control-lg',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->fax ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
           <?= Field::telephone($form, 'mobile')
    ->label($translator->translate('mobile'))
    ->addInputAttributes([
        'class' => 'form-control form-control-lg',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->mobile ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'web')
    ->label($translator->translate('web.address'))
    ->addInputAttributes([
        'class' => 'form-control form-control-lg',
        'id' => 'web',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->web ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'tax_code')
    ->label($translator->translate('tax.code'))
    ->addInputAttributes([
        'class' => 'form-control form-control-lg',
        'id' => 'tax_code',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->tax_code ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'subscribernumber')
    ->label($translator->translate('user.subscriber.number'))
    ->addInputAttributes([
        'class' => 'form-control form-control-lg',
        'id' => 'subscribernumber',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->subscribernumber ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'iban')
    ->label($translator->translate('user.iban'))
    ->addInputAttributes([
        'class' => 'form-control form-control-lg',
        'id' => 'iban',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->subscribernumber ?? ''))
?>
         <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'gln')
    ->label($translator->translate('delivery.location.global.location.number'))
    ->addInputAttributes([
        'class' => 'form-control form-control-lg',
        'id' => 'gln',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->gln ?? ''))
?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?=  new Form()->close() ?>
