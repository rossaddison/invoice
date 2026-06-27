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
 * @var App\Widget\UserInvFormFields $formFields
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

$fc       = 'form-control form-control-lg';
$lAddr    = $translator->translate('street.address');
$lCity    = $translator->translate('city');
$lState   = $translator->translate('state');
$lZip     = $translator->translate('zip');
$lCountry = $translator->translate('country');
$lPhone   = $translator->translate('phone');
$lFax     = $translator->translate('fax');
$lEmail   = $translator->translate('email');
$lMob     = $translator->translate('mobile');
$lWeb     = $translator->translate('web.address');
$lTax     = $translator->translate('tax.code');
$lSub     = $translator->translate('user.subscriber.number');
$lIban    = $translator->translate('user.iban');
$lGln     = $translator->translate('delivery.location.global.location.number');
$lLimit   = $translator->translate('user.inv.list.limit');
$lChatId  = $translator->translate('consent.telegram.chat.id');

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
            <?= $formFields->userInvUserIdField($form); ?>
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
            <?= Field::text($form, 'address_1')
    ->label($lAddr)
    ->addInputAttributes(['placeholder' => $lAddr, 'class' => $fc, 'id' => 'address_1'])
    ->value(Html::encode($form->address_1 ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'address_2')
    ->label($translator->translate('street.address.2'))
    ->addInputAttributes(['placeholder' => $lAddr, 'class' => $fc, 'id' => 'address_2'])
    ->value(Html::encode($form->address_2 ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'city')
    ->label($lCity)
    ->addInputAttributes(['placeholder' => $lCity, 'class' => $fc, 'id' => 'city'])
    ->value(Html::encode($form->city ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'state')
    ->label($lState)
    ->addInputAttributes(['placeholder' => $lState, 'class' => $fc, 'id' => 'state'])
    ->value(Html::encode($form->state ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'zip')
    ->label($lZip)
    ->addInputAttributes(['placeholder' => $lZip, 'class' => $fc, 'id' => 'zip'])
    ->value(Html::encode($form->zip ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'country')
    ->label($lCountry)
    ->addInputAttributes(['placeholder' => $lCountry, 'class' => $fc, 'id' => 'country'])
    ->value(Html::encode($form->country ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::telephone($form, 'phone')
    ->label($lPhone)
    ->addInputAttributes(['placeholder' => $lPhone, 'class' => $fc, 'id' => 'phone'])
    ->value(Html::encode($form->phone ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::telephone($form, 'fax')
    ->label($lFax)
    ->addInputAttributes(['placeholder' => $lFax, 'class' => $fc, 'id' => 'fax'])
    ->value(Html::encode($form->fax ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::email($form, 'user')
    ->label($lEmail)
    ->addInputAttributes(['placeholder' => $lEmail, 'class' => $fc, 'id' => 'email'])
    ->disabled(true)
    ->value(Html::encode($form->user?->getEmail() ?? '#'));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
           <?= Field::telephone($form, 'mobile')
    ->label($lMob)
    ->addInputAttributes(['placeholder' => $lMob, 'class' => $fc, 'id' => 'mobile'])
    ->value(Html::encode($form->mobile ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'web')
    ->label($lWeb)
    ->addInputAttributes(['placeholder' => $lWeb, 'class' => $fc, 'id' => 'web'])
    ->value(Html::encode($form->web ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'tax_code')
    ->label($lTax)
    ->addInputAttributes(['placeholder' => $lTax, 'class' => $fc, 'id' => 'tax_code'])
    ->value(Html::encode($form->tax_code ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'subscribernumber')
    ->label($lSub)
    ->addInputAttributes(['placeholder' => $lSub, 'class' => $fc, 'id' => 'subscribernumber'])
    ->value(Html::encode($form->subscribernumber ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'iban')
    ->label($lIban)
    ->addInputAttributes(['placeholder' => $lIban, 'class' => $fc, 'id' => 'iban'])
    ->value(Html::encode($form->subscribernumber ?? ''));
?>
         <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'gln')
    ->label($lGln)
    ->addInputAttributes(['placeholder' => $lGln, 'class' => $fc, 'id' => 'gln'])
    ->value(Html::encode($form->gln ?? ''));
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'list_limit')
    ->label($lLimit)
    ->addInputAttributes(['placeholder' => $lLimit, 'class' => $fc, 'id' => 'list_limit'])
    ->value($form->list_limit !== null ? (string) $form->list_limit : null);
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 border-top pt-3']); ?>
            <?= Field::checkbox($form, 'consent_periodic_invoice')
    ->label($translator->translate('consent.periodic.invoice'))
    ->addInputAttributes(['id' => 'consent_periodic_invoice']);
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::checkbox($form, 'consent_telegram_outstanding')
    ->label($translator->translate('consent.telegram.outstanding'))
    ->addInputAttributes(['id' => 'consent_telegram_outstanding']);
?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'telegram_chat_id')
    ->label($lChatId)
    ->addInputAttributes(['placeholder' => $lChatId, 'class' => $fc, 'id' => 'telegram_chat_id'])
    ->value(Html::encode($form->telegram_chat_id ?? ''));
?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?=  new Form()->close() ?>
