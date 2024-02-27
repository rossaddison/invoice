<?php
declare(strict_types=1);

use App\Invoice\Helpers\ClientHelper;

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;

/**
 * @var Yiisoft\Yii\View\Csrf $csrf 
 */

$client_helper = new ClientHelper($s);
?>
<?= Html::openTag('form', ['method' => 'post']); ?>
<?= Html::openTag('input', ['type' => 'hidden', 'name' => '_csrf', 'value' => $csrf]); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= Html::openTag('h1', ['class' => 'headerbar-title']); ?>
        <?= $translator->translate('i.assign_client'); ?>
    <?= Html::closeTag('h1'); ?>
    <?= $button::back_save($translator); ?>
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div', ['id' => 'content']); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div', ['class' => 'col-xs-12 col-md-6 col-md-offset-3']); ?>
            <?= Html::openTag('input', ['type' => 'hidden', 'name' => 'user_id', 'id' => 'user_id', 'value' => $userinv->getUser_id() ]); ?>
                <?= Html::openTag('div', ['class' => 'panel panel-default']); ?>
                    <?= Html::openTag('div', ['class' => 'panel-heading']); ?>
                        <?= Html::encode($userinv->getName()); ?>
                    <?= Html::closeTag('div'); ?>
                    <?= Html::openTag('div', ['class' => 'panel-body']); ?>
                        <?= Html::openTag('div', ['class' => 'alert alert-info']); ?>
                            <?= Field::errorSummary($form)
                                ->errors($errors)
                                ->header($translator->translate('invoice.client.error.summary'))
                                ->onlyProperties(...['client_name', 'client_surname', 'client_email', 'client_age'])    
                                ->onlyCommonErrors()
                            ?>
                            
                            <?= Field::checkbox($form, 'user_all_clients')
                                ->inputLabelAttributes([
                                    'class' => 'form-check-label'
                                ])    
                                ->enclosedByLabel(true)
                                ->inputClass('form-check-input')
                                ->ariaDescribedBy($translator->translate('i.user_all_clients'))
                            ?>    
                            <?= Html::openTag('div'); ?>
                                <?= $translator->translate('i.user_all_clients_text') ?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('div'); ?>

                        <?= Html::openTag('div', ['id' => 'list_client']); ?>
                            <?php if ($clients) { 
                               
                                    $optionsDataClient = [];
                                    foreach ($clients as $client) { 
                                        $optionsDataClient[$client->getClient_id()] = Html::encode($client_helper->format_client($client));                    
                                    }
                                    echo Field::select($form, 'client_id')
                                    ->label($translator->translate('i.client'))
                                    ->addInputAttributes([
                                        'id' => 'client_id', 
                                        'class' => 'form-control',
                                        'autofocus' => 'autofocus',
                                        'selected' => $s->check_select(Html::encode($body['client_id'] ?? ''), $client->getClient_id())
                                    ])    
                                    ->optionsData($optionsDataClient); 
                                
                               } else { 
                                
                                    $optionsDataClient[0] = $translator->translate('i.none');
                                    echo Field::select($form, 'client_id')
                                    ->label($translator->translate('i.client'))
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