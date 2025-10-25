<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Security\Random;

/**
 * @var App\Invoice\Entity\UserInv $userinv
 * @var App\Invoice\Entity\Client $client
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Invoice\Client\ClientRepository $cR
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 * @var App\Invoice\UserClient\UserClientForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var array $availableClientIdList
 * @var string $actionName
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string, list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClient
 */

?>
<?= Html::openTag('form', ['method' => 'post']); ?>
<?= Html::openTag('input', ['type' => 'hidden', 'name' => '_csrf', 'value' => $csrf]); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= Html::openTag('h1', ['class' => 'headerbar-title']); ?>
        <?= $translator->translate('assign.client'); ?>
    <?= Html::closeTag('h1'); ?>
    <?= $button::backSave(); ?>
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div', ['id' => 'content']); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div', ['class' => 'col-xs-12 col-md-6 col-md-offset-3']); ?>
            <?= Field::hidden($form, 'user_id')
                    ->inputId('user_id-' . Random::string(10))
                    ->value($userinv->getUser_id()); ?>
                <?= Html::openTag('div', ['class' => 'panel panel-default']); ?>
                    <?= Html::openTag('div', ['class' => 'panel-heading']); ?>
                        <?= Html::encode($userinv->getName()); ?>
                    <?= Html::closeTag('div'); ?>
                    <?= Html::openTag('div', ['class' => 'panel-body']); ?>
                        <?= Html::openTag('div', ['class' => 'alert alert-info']); ?>
                            <?= Field::errorSummary($form)
                                ->errors($errors)
                                ->header($translator->translate('client.error.summary'))
                                ->onlyProperties(...['client_name', 'client_surname', 'client_email', 'client_age'])
                                ->onlyCommonErrors()
?>
                            
                            <?= Field::checkbox($form, 'user_all_clients')
    ->inputLabelAttributes([
        'class' => 'form-check-label',
    ])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('user.all.clients'))
?>    
                            <?= Html::openTag('div'); ?>
                                <?= $translator->translate('user.all.clients.text') ?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('div'); ?>

                        <?= Html::openTag('div', ['id' => 'list_client']); ?>
                            <?php
   $clients = !empty($availableClientIdList) ? $cR->repoUserClient($availableClientIdList) : [];
if ($clients) {
    $optionsDataClient = [];
    /**
     * @var Yiisoft\Data\Cycle\Reader\EntityReader|array $clients
     * @var App\Invoice\Entity\Client $client
     */
    foreach ($clients as $client) {
        $clientId = $client->getClient_id();
        if (null !== $clientId) {
            $optionsDataClient[$clientId] = Html::encode($clientHelper->format_client($client));
        }
    }
    echo Field::select($form, 'client_id')
    ->label($translator->translate('client'))
    ->addInputAttributes([
        'id' => 'client_id',
        'class' => 'form-control',
        'autofocus' => 'autofocus',
        'selected' => $s->check_select(Html::encode($body['client_id'] ?? ''), $client->getClient_id()),
    ])
    ->optionsData($optionsDataClient);

} else {

    $optionsDataClient[0] = $translator->translate('none');
    echo Field::select($form, 'client_id')
    ->label($translator->translate('client'))
    ->addInputAttributes([
        'id' => 'client_id',
        'class' => 'form-control',
        'autofocus' => 'autofocus',
    ])
    ->optionsData($optionsDataClient);
} ?>
                        <?= Html::closeTag('div'); ?>
                    <?= Html::closeTag('div'); ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('form'); ?>