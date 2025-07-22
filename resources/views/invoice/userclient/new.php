<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;

/*
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
<?php echo Html::openTag('form', ['method' => 'post']); ?>
<?php echo Html::openTag('input', ['type' => 'hidden', 'name' => '_csrf', 'value' => $csrf]); ?>
<?php echo Html::openTag('div', ['id' => 'headerbar']); ?>
    <?php echo Html::openTag('h1', ['class' => 'headerbar-title']); ?>
        <?php echo $translator->translate('assign.client'); ?>
    <?php echo Html::closeTag('h1'); ?>
    <?php echo $button::backSave(); ?>
<?php echo Html::closeTag('div'); ?>

<?php echo Html::openTag('div', ['id' => 'content']); ?>
    <?php echo Html::openTag('div', ['class' => 'row']); ?>
        <?php echo Html::openTag('div', ['class' => 'col-xs-12 col-md-6 col-md-offset-3']); ?>
            <?php echo Html::openTag('input', ['type' => 'hidden', 'name' => 'user_id', 'id' => 'user_id', 'value' => $userinv->getUser_id()]); ?>
                <?php echo Html::openTag('div', ['class' => 'panel panel-default']); ?>
                    <?php echo Html::openTag('div', ['class' => 'panel-heading']); ?>
                        <?php echo Html::encode($userinv->getName()); ?>
                    <?php echo Html::closeTag('div'); ?>
                    <?php echo Html::openTag('div', ['class' => 'panel-body']); ?>
                        <?php echo Html::openTag('div', ['class' => 'alert alert-info']); ?>
                            <?php echo Field::errorSummary($form)
    ->errors($errors)
    ->header($translator->translate('client.error.summary'))
    ->onlyProperties(...['client_name', 'client_surname', 'client_email', 'client_age'])
    ->onlyCommonErrors();
?>
                            
                            <?php echo Field::checkbox($form, 'user_all_clients')
                                ->inputLabelAttributes([
                                    'class' => 'form-check-label',
                                ])
                                ->inputClass('form-check-input')
                                ->ariaDescribedBy($translator->translate('user.all.clients'));
?>    
                            <?php echo Html::openTag('div'); ?>
                                <?php echo $translator->translate('user.all.clients.text'); ?>
                            <?php echo Html::closeTag('div'); ?>
                        <?php echo Html::closeTag('div'); ?>

                        <?php echo Html::openTag('div', ['id' => 'list_client']); ?>
                            <?php
   $clients = !empty($availableClientIdList) ? $cR->repoUserClient($availableClientIdList) : [];
if ($clients) {
    $optionsDataClient = [];
    /**
     * @var Yiisoft\Data\Cycle\Reader\EntityReader|array $clients
     * @var App\Invoice\Entity\Client                    $client
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
            'id'        => 'client_id',
            'class'     => 'form-control',
            'autofocus' => 'autofocus',
            'selected'  => $s->check_select(Html::encode($body['client_id'] ?? ''), $client->getClient_id()),
        ])
        ->optionsData($optionsDataClient);
} else {
    $optionsDataClient[0] = $translator->translate('none');
    echo Field::select($form, 'client_id')
        ->label($translator->translate('client'))
        ->addInputAttributes([
            'id'        => 'client_id',
            'class'     => 'form-control',
            'autofocus' => 'autofocus',
        ])
        ->optionsData($optionsDataClient);
} ?>
                        <?php echo Html::closeTag('div'); ?>
                    <?php echo Html::closeTag('div'); ?>
                <?php echo Html::closeTag('div'); ?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('form'); ?>