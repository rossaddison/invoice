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
        <?= $button::back(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::openTag('div', ['id' => 'content']); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>        
        <?= Html::openTag('div', ['class' => 'mb-3 form-group no-margin']); ?>
            <?php
                $optionsDataUser = [];
/**
 * @var App\User\User $user
 */
foreach ($uR->findAllPreloaded() as $user) {
    if (null !== $user->getId()) {
        /**
         * @psalm-suppress PossiblyNullArrayOffset $user->getId()
         */
        $optionsDataUser[$user->getId()] = ucfirst($user->getLogin());
    }
}
echo Field::select($form, 'user_id')
->label($translator->translate('i.users'))
->addInputAttributes([
    'class' => 'form-control',
    'id' => 'user_id',
    'readonly' => 'readonly'
])
->optionsData($optionsDataUser)
->value(Html::encode($form->getUser_id() ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php
  $types = [
      0 => $translator->translate('i.administrator'),
      1 => $translator->translate('i.guest_read_only'),
  ]
?>
            <?php
    $optionsDataType = [];
foreach ($types as $key => $value) {
    $optionsDataType[$key] = $value;
}
echo Field::select($form, 'type')
->label($translator->translate('i.type'))
->addInputAttributes([
    'class' => 'form-control',
    'id' => 'type',
    'readonly' => 'readonly'
])
->optionsData($optionsDataType)
->value(Html::encode($form->getType()))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Html::openTag('div', ['class' => 'p-2']); ?> 
                <?= Field::checkbox($form, 'active')
        ->inputLabelAttributes(['class' => 'form-check-label'])
        ->inputClass('form-check-input')
        ->ariaDescribedBy($translator->translate('i.active'))
?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?><?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::checkbox($form, 'all_clients')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('i.user_all_clients'));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group no-margin']); ?>
            <?php
    $optionsDataLanguage = [];
/**
 * @var string $language
 */
foreach (ArrayHelper::map($s->expandDirectoriesMatrix($aliases->get('@language'), 0), 'name', 'name') as $language) {
    $optionsDataLanguage[$language] = ucfirst($language);
}
echo Field::select($form, 'language')
->label($translator->translate('i.language'))
->addInputAttributes([
    'class' => 'form-control',
    'id' => 'language',
    'readonly' => 'readonly'
])
->optionsData($optionsDataLanguage)
->value(Html::encode($form->getLanguage()))
?>
        <?= Html::closeTag('div'); ?>   
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'name')
    ->label($translator->translate('i.name'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'name',
        'readonly' => 'readonly'
    ])
    ->value(Html::encode($form->getName() ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'company')
    ->label($translator->translate('i.company'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'company',
        'readonly' => 'readonly'
    ])
    ->value(Html::encode($form->getCompany() ?? ''))
?>
        <?= Html::closeTag('div'); ?>   
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'address_1')
    ->label($translator->translate('i.street_address'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'address_1',
        'readonly' => 'readonly'
    ])
    ->value(Html::encode($form->getAddress_1() ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'address_2')
    ->label($translator->translate('i.street_address_2'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'address_2',
        'readonly' => 'readonly'
    ])
    ->value(Html::encode($form->getAddress_2() ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'city')
    ->label($translator->translate('i.city'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'city',
        'readonly' => 'readonly'
    ])
    ->value(Html::encode($form->getCity() ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'state')
    ->label($translator->translate('i.state'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'state',
        'readonly' => 'readonly'
    ])
    ->value(Html::encode($form->getState() ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'zip')
    ->label($translator->translate('i.zip'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'zip',
        'readonly' => 'readonly'
    ])
    ->value(Html::encode($form->getZip() ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'country')
    ->label($translator->translate('i.country'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'country',
        'readonly' => 'readonly'
    ])
    ->value(Html::encode($form->getCountry() ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::telephone($form, 'phone')
    ->label($translator->translate('i.phone'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'phone',
        'readonly' => 'readonly'
    ])
    ->value(Html::encode($form->getPhone() ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::telephone($form, 'fax')
    ->label($translator->translate('i.fax'))
    ->addInputAttributes([
        'class' => 'form-control',
        'readonly' => 'readonly'
    ])
    ->value(Html::encode($form->getFax() ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
           <?= Field::telephone($form, 'mobile')
    ->label($translator->translate('i.mobile'))
    ->addInputAttributes([
        'class' => 'form-control',
        'readonly' => 'readonly'
    ])
    ->value(Html::encode($form->getMobile() ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'web')
    ->label($translator->translate('i.web_address'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'web',
        'readonly' => 'readonly'
    ])
    ->value(Html::encode($form->getWeb() ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'tax_code')
    ->label($translator->translate('i.tax_code'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'tax_code',
        'readonly' => 'readonly'
    ])
    ->value(Html::encode($form->getTax_code() ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'subscribernumber')
    ->label($translator->translate('i.user_subscriber_number'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'subscribernumber',
        'readonly' => 'readonly'
    ])
    ->value(Html::encode($form->getSubscribernumber() ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'iban')
    ->label($translator->translate('i.user_iban'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'iban',
        'readonly' => 'readonly'
    ])
    ->value(Html::encode($form->getSubscribernumber() ?? ''))
?>
         <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'gln')
    ->label($translator->translate('invoice.delivery.location.global.location.number'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'gln',
        'readonly' => 'readonly'
    ])
    ->value(Html::encode($form->getGln() ?? ''))
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'rcc')
    ->label($translator->translate('i.sumex_rcc'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id' => 'rcc'
    ])
    ->value(Html::encode($form->getRcc() ?? ''))
?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>
