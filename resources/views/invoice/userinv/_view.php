<?php

declare(strict_types=1);

use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
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

<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>

<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('UserInvForm')
    ->open(); ?>

<?php echo Html::openTag('div', ['class' => 'headerbar']); ?>
        <?php echo Html::openTag('h1', ['class' => 'headerbar-title']); ?>
            <?php echo Html::encode($title); ?>
        <?php echo Html::closeTag('h1'); ?>
        <?php echo $button::back(); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::openTag('div', ['id' => 'content']); ?>
    <?php echo Html::openTag('div', ['class' => 'row']); ?>        
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group no-margin']); ?>
            <?php
                $optionsDataUser = [];
/**
 * @var App\User\User $user
 */
foreach ($uR->findAllPreloaded() as $user) {
    if (null !== $user->getId()) {
        /*
         * @psalm-suppress PossiblyNullArrayOffset $user->getId()
         */
        $optionsDataUser[$user->getId()] = ucfirst($user->getLogin());
    }
}
echo Field::select($form, 'user_id')
    ->label($translator->translate('users'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'id'       => 'user_id',
        'readonly' => 'readonly',
    ])
    ->optionsData($optionsDataUser)
    ->value(Html::encode($form->getUser_id() ?? ''));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php
  $types = [
      0 => $translator->translate('administrator'),
      1 => $translator->translate('guest.read.only'),
  ];
?>
            <?php
    $optionsDataType = [];
foreach ($types as $key => $value) {
    $optionsDataType[$key] = $value;
}
echo Field::select($form, 'type')
    ->label($translator->translate('type'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'id'       => 'type',
        'readonly' => 'readonly',
    ])
    ->optionsData($optionsDataType)
    ->value(Html::encode($form->getType()));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Html::openTag('div', ['class' => 'p-2']); ?> 
                <?php echo Field::checkbox($form, 'active')
                    ->inputLabelAttributes(['class' => 'form-check-label'])
                    ->inputClass('form-check-input')
                    ->ariaDescribedBy($translator->translate('active'));
?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?><?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::checkbox($form, 'all_clients')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('user.all.clients'));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group no-margin']); ?>
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
        'class'    => 'form-control',
        'id'       => 'language',
        'readonly' => 'readonly',
    ])
    ->optionsData($optionsDataLanguage)
    ->value(Html::encode($form->getLanguage()));
?>
        <?php echo Html::closeTag('div'); ?>   
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::text($form, 'name')
            ->label($translator->translate('name'))
            ->addInputAttributes([
                'class'    => 'form-control',
                'id'       => 'name',
                'readonly' => 'readonly',
            ])
            ->value(Html::encode($form->getName() ?? ''));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::text($form, 'company')
    ->label($translator->translate('company'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'id'       => 'company',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->getCompany() ?? ''));
?>
        <?php echo Html::closeTag('div'); ?>   
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::text($form, 'address_1')
            ->label($translator->translate('street.address'))
            ->addInputAttributes([
                'class'    => 'form-control',
                'id'       => 'address_1',
                'readonly' => 'readonly',
            ])
            ->value(Html::encode($form->getAddress_1() ?? ''));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::text($form, 'address_2')
    ->label($translator->translate('street.address.2'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'id'       => 'address_2',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->getAddress_2() ?? ''));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::text($form, 'city')
    ->label($translator->translate('city'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'id'       => 'city',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->getCity() ?? ''));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::text($form, 'state')
    ->label($translator->translate('state'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'id'       => 'state',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->getState() ?? ''));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::text($form, 'zip')
    ->label($translator->translate('zip'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'id'       => 'zip',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->getZip() ?? ''));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::text($form, 'country')
    ->label($translator->translate('country'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'id'       => 'country',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->getCountry() ?? ''));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::telephone($form, 'phone')
    ->label($translator->translate('phone'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'id'       => 'phone',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->getPhone() ?? ''));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::telephone($form, 'fax')
    ->label($translator->translate('fax'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->getFax() ?? ''));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
           <?php echo Field::telephone($form, 'mobile')
    ->label($translator->translate('mobile'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->getMobile() ?? ''));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::text($form, 'web')
    ->label($translator->translate('web.address'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'id'       => 'web',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->getWeb() ?? ''));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::text($form, 'tax_code')
    ->label($translator->translate('tax.code'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'id'       => 'tax_code',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->getTax_code() ?? ''));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::text($form, 'subscribernumber')
    ->label($translator->translate('user.subscriber.number'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'id'       => 'subscribernumber',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->getSubscribernumber() ?? ''));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::text($form, 'iban')
    ->label($translator->translate('user.iban'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'id'       => 'iban',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->getSubscribernumber() ?? ''));
?>
         <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::text($form, 'gln')
    ->label($translator->translate('delivery.location.global.location.number'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'id'       => 'gln',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->getGln() ?? ''));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php echo Field::text($form, 'rcc')
    ->label($translator->translate('sumex.rcc'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id'    => 'rcc',
    ])
    ->value(Html::encode($form->getRcc() ?? ''));
?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Form::tag()->close(); ?>
