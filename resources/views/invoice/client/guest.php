<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Entity\Client $client
 * @var App\Invoice\Entity\UserInv $userInv
 * @var App\Invoice\ClientPeppol\ClientPeppolRepository $cpR
 * @var App\Invoice\Inv\InvRepository $iR
 * @var App\Invoice\InvAmount\InvAmountRepository $iaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Invoice\UserClient\UserClientRepository $ucR
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter 
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $invoices
 * @var bool $editInv
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $active
 * @var string $alert
 * @var string $csrf
 * @var string $modal_create_client
 */


echo $alert;

?>

<div>
    <?php 
        echo $modal_create_client;
    ?>
</div>

<div>
    <h5><?= Html::encode($translator->translate('i.clients')); ?></h5>
    <br>
</div>
<div id="content" class="table-content">
    
        <div class="table-responsive">
        <table class="table table-hover table-striped">
        <thead>
        <tr>
        <th><?= $translator->translate('i.active'); ?></th>
        <th>Peppol</th>
        <th><?= $translator->translate('i.client_name'); ?></th>
        <th data-bs-toggle="tooltip" title="<?= $translator->translate('invoice.client.detail.changes'); ?>"><?= $translator->translate('i.email_address'); ?></th>
        <th data-bs-toggle="tooltip" title="<?= $translator->translate('invoice.client.detail.changes'); ?>"><?= $translator->translate('i.phone_number'); ?></th>
        <th class="amount"><?= $translator->translate('i.balance'); ?></th>
        <th><?= $translator->translate('i.options'); ?></th>
        </tr>
        </thead>
        <tbody>
            <?php
            /**
             * @var App\Invoice\Entity\Client $client
             */
            foreach ($paginator->read() as $client) { ?>
            <tr>
		<td>
		    <?= ($client->getClient_active()) ? '<span class="label active">' . $translator->translate('i.yes') . '</span>' : '<span class="label inactive">' . $translator->translate('i.no') . '</span>'; ?>
		</td>
                <td>
		    <?= ($cpR->repoClientCount((string)$client->getClient_id()) !== 0 ) ? '<span class="label active">' . $translator->translate('i.yes') . '</span>' : '<span class="label inactive">' . $translator->translate('i.no') . '</span>'; ?>
		</td>
                <td><?= Html::a($client->getClient_name()." ".($client->getClient_surname() ?? '#'),$urlGenerator->generate('client/view',['id' => $client->getClient_id()]),['class' => 'btn btn-warning ms-2']);?></td>
                <td><?= Html::encode($client->getClient_email()); ?></td>
                <td><?= Html::encode(strlen($client->getClient_phone() ?? '') > 0 ? $client->getClient_phone() : (strlen($clientMobile = $client->getClient_mobile() ?? '' ) > 0  ? $clientMobile : '')); ?></td>
                <td class="amount"><?php (null!==($clientId = $client->getClient_id())) ? 
                    Html::encode($s->format_currency($iR->with_total_balance($clientId, $iaR))) : ''; ?></td>
                <td>
                    <div class="options btn-group">
                        <a class="btn btn-default dropdown-toggle" data-toggle="dropdown" href="#" style="text-decoration:none">
                            <i class="fa fa-cog"></i> <?= $translator->translate('i.options'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if ($editInv) { ?>
                            <li>
                                <a href="<?= $urlGenerator->generate('client/view',['id' => $client->getClient_id()]); ?>" style="text-decoration:none">
                                    <i class="fa fa-eye fa-margin"></i> <?= $translator->translate('i.view'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?= $urlGenerator->generate('client/edit', ['id' => $client->getClient_id()]); ?>" style="text-decoration:none">
                                    <i class="fa fa-edit fa-margin"></i> <?= $translator->translate('i.edit'); ?>
                                </a>
                            </li>
                            <?php } ?>
                            <?php if ($cpR->repoClientCount((string)$client->getClient_id()) === 0 ) { ?>
                            <li>
                                <a href="<?= $urlGenerator->generate('clientpeppol/add', ['client_id' => $client->getClient_id()]); ?>" style="text-decoration:none">
                                    <i class="fa fa-plus fa-margin"></i> <?= $translator->translate('invoice.client.peppol.add'); ?>
                                </a>
                            </li>
                            <?php } ?>
                            <?php if ($cpR->repoClientCount((string)$client->getClient_id()) > 0 ) { ?>
                            <li>
                                <a href="<?= $urlGenerator->generate('clientpeppol/edit', ['client_id' => $client->getClient_id()]); ?>" style="text-decoration:none">
                                    <i class="fa fa-edit fa-margin"></i> <?= $translator->translate('invoice.client.peppol.edit'); ?>
                                </a>
                            </li>
                            <?php } ?>
                            <?php if ($editInv) { ?>
                            <li>
                                <a href="<?= $urlGenerator->generate('client/delete',['id' => $client->getClient_id()]); ?>" style="text-decoration:none" onclick="return confirm('<?= $translator->translate('i.delete_client_warning').'?'; ?>');">
                                    <i class="fa fa-trash fa-margin"></i><?= $translator->translate('i.delete'); ?>                                    
                                </a>
                            </li>
                            <?php } ?>
                        </ul>
                    </div>
                </td>
            </tr>
            <?php } ?>            
        </tbody>
    </table>
    <div>
        <?php 
            $grid_summary = $s->grid_summary(
                $paginator, 
                $translator, 
                10,
                $translator->translate('invoice.clients'), 
            '');
            echo $pageSizeLimiter::buttonsGuest($userInv, $urlGenerator, $translator, 'client', $defaultPageSizeOffsetPaginator).' '.$grid_summary;
            echo $gridComponents->offsetPaginationWidget($defaultPageSizeOffsetPaginator, $paginator);        
        ?>        
    </div>    
</div>
</div>