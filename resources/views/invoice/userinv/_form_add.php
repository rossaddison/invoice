<?php

declare(strict_types=1);

use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Infrastructure\Persistence\UserInv\UserInv $userInv
 * @var App\Invoice\UserInv\UserInvForm $form
 * @var App\Invoice\UserInv\UserInvRepository $uiR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\User\UserRepository $uR
 * @var App\Widget\Button $button
 * @var App\Widget\FormFields $formFields
 * @var Yiisoft\Aliases\Aliases $aliases
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\View $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $actionName
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @var string $csrf
 * @psalm-var array<string,list<string>> $errors
 * @var string $title
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataSignedUpUsersNotInUserInv
 */

$fc     = 'form-control form-control-lg';
$lPhone = $translator->translate('phone');
$lFax   = $translator->translate('fax');
$lMob   = $translator->translate('mobile');
$lEmail = $translator->translate('email');
$lWeb   = $translator->translate('web.address');
$lTax   = $translator->translate('tax.code');
$lSub   = $translator->translate('user.subscriber.number');
$lIban  = $translator->translate('user.iban');
$lGln   = $translator->translate('delivery.location.global.location.number');
$lLimit = $translator->translate('user.inv.list.limit');

?>

<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
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
        <?= $button::backSave(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::openTag('div', ['id' => 'content']); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::errorSummary($form)
                ->errors($errors)
                ->header($translator->translate('error.summary'))
                ->onlyProperties(...[''])
                ->onlyCommonErrors()
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'm-0']); ?>
            <?php
                // build an array of userinv ids
                $userInvIds = [];
/**
 * @var App\Infrastructure\Persistence\UserInv\UserInv $userInv
 */
foreach ($uiR->findAllPreloaded() as $userInv) {
    $userInvIds[] = $userInv->reqId();
}

// build an array of newly signed up users not in userinv
$optionsDataSignedUpUsersNotInUserInv = [];
/**
 * @var App\Infrastructure\Persistence\User\User $user
 */
foreach ($uR->findAllPreloaded() as $user) {
    $userId = $user->reqId();
    if (!in_array($userId, $userInvIds)) {
        $optionsDataSignedUpUsersNotInUserInv[$userId] = $user->getLogin();
    }
}
?>
            <?= $formFields->userInvUserSelect($form, $optionsDataSignedUpUsersNotInUserInv); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?php
    $typeOptions = [
        0 => $translator->translate('administrator'),
        1 => $translator->translate('guest.read.only'),
    ];
?>
            <?= $formFields->userInvTypeSelect($form, $typeOptions); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Html::openTag('div', ['class' => 'p-2']); ?>
                <?= $formFields->userInvCheckboxField($form, 'active', 'active'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?><?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= $formFields->userInvCheckboxField($form, 'all_clients', 'user.all.clients'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'm-0']); ?>
            <?php
    $languageOptions = [];
/** @var string $language */
foreach (ArrayHelper::map($s->expandDirectoriesMatrix($aliases->get('@language'), 0), 'name', 'name') as $language) {
    $languageOptions[$language] = ucfirst($language);
}
?>
            <?= $formFields->userInvLanguageSelect($form, $languageOptions); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= $formFields->userInvTextField($form, 'name', 'name', true); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= $formFields->userInvTextField($form, 'company', 'company', false); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= $formFields->userInvTextField($form, 'address_1', 'street.address', false); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= $formFields->userInvTextField($form, 'address_2', 'street.address.2', false); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= $formFields->userInvTextField($form, 'city', 'city', false); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= $formFields->userInvTextField($form, 'state', 'state', false); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= $formFields->userInvTextField($form, 'zip', 'zip', false); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= $formFields->userInvTextField($form, 'country', 'country', false); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::telephone($form, 'phone')
    ->label($lPhone)
    ->addInputAttributes(['placeholder' => $lPhone, 'class' => $fc, 'id' => 'phone'])
    ->value(Html::encode($form->getPhone() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::telephone($form, 'fax')
    ->label($lFax)
    ->addInputAttributes(['placeholder' => $lFax, 'class' => $fc, 'id' => 'fax'])
    ->value(Html::encode($form->getFax() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
           <?= Field::telephone($form, 'mobile')
    ->label($lMob)
    ->addInputAttributes(['placeholder' => $lMob, 'class' => $fc, 'id' => 'mobile'])
    ->value(Html::encode($form->getMobile() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::email($form, 'user')
    ->label($lEmail)
    ->addInputAttributes(['placeholder' => $lEmail, 'class' => $fc, 'id' => 'email'])
    ->disabled(true)
    ->value(Html::encode($form->getUser()?->getEmail() ?? '#'));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'web')
    ->label($lWeb)
    ->addInputAttributes(['placeholder' => $lWeb, 'class' => $fc, 'id' => 'web'])
    ->value(Html::encode($form->getWeb() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'tax_code')
    ->label($lTax)
    ->addInputAttributes(['placeholder' => $lTax, 'class' => $fc, 'id' => 'tax_code'])
    ->value(Html::encode($form->getTaxCode() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'subscribernumber')
    ->label($lSub)
    ->addInputAttributes(['placeholder' => $lSub, 'class' => $fc, 'id' => 'subscribernumber'])
    ->value(Html::encode($form->getSubscribernumber() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'iban')
    ->label($lIban)
    ->addInputAttributes(['placeholder' => $lIban, 'class' => $fc, 'id' => 'iban'])
    ->value(Html::encode($form->getSubscribernumber() ?? ''));
?>
         <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'gln')
    ->label($lGln)
    ->addInputAttributes(['placeholder' => $lGln, 'class' => $fc, 'id' => 'gln'])
    ->value(Html::encode($form->getGln() ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'listLimit')
    ->label($lLimit)
    ->addInputAttributes(['placeholder' => $lLimit, 'class' => $fc, 'id' => 'listLimit'])
    ->value(Html::encode($form->getListLimit() ?? 10));
?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?=  new Form()->close() ?>
