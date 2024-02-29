<?php

declare(strict_types=1);

/**
 * @var \App\Invoice\Entity\Client $client
 * @var \App\Invoice\Setting\SettingRepository $s
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator 
 * @var \Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var \Yiisoft\Session\Flash\FlashInterface $flash 
 * @var \Yiisoft\View\WebView $this
 */

use App\Widget\OffsetPagination;
use Yiisoft\Html\Html;

echo $alert;

$this->setTitle($translator->translate('i.clients'));

$pagination = OffsetPagination::widget()
  ->paginator($paginator)
  ->urlGenerator(fn ($page) => $urlGenerator->generate('client/index', ['page' => $page, 'active'=>$active]));
?>

<div>
    <?php 
        echo $modal_create_client;
    ?>
</div>

<div>
    <h5><?= Html::encode($this->getTitle()); ?></h5>
    <div class="btn-group">
        <a href="<?= $urlGenerator->generate('client/add', ['origin' => 'add']);?>" class="btn btn-success" style="text-decoration:none"><i class="fa fa-plus"></i> <?= $translator->translate('i.new'); ?></a>
    </div>
    <br>
    <br>
    <div class="submenu-row">
            <div class="btn-group index-options">
                <a href="<?= $urlGenerator->generate('client/index',['page'=>1, 'active'=>2]); ?>"
                   class="btn <?= $active == 2 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('i.all'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('client/index',['page'=>1, 'active'=>1]); ?>" style="text-decoration:none"
                   class="btn  <?= $active == 1 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('i.active'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('client/index',['page'=>1, 'active'=>0]); ?>" style="text-decoration:none"
                   class="btn  <?= $active == 0 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('i.inactive'); ?>
                </a>    
            </div>
    </div>
</div>
<div id="content" class="table-content">
    <?php 
                if ($pagination->isPaginationRequired()) {
                   echo $pagination;
                }
    ?>  
        <div class="table-responsive">
        <table class="table table-hover table-striped">
        <thead>
        <tr>
        <th><?= $translator->translate('i.active'); ?></th>
        <th>Peppol</th>
        <th><?= $translator->translate('invoice.client.has.user.account'); ?></th>
        <th><?= $translator->translate('i.client_name'); ?></th>
         <th><?= $translator->translate('i.birthdate'); ?></th>
        <th><?= $translator->translate('i.email_address'); ?></th>
        <th><?= $translator->translate('i.phone_number'); ?></th>
        <th class="amount"><?= $translator->translate('i.balance'); ?></th>
        <th><?= $translator->translate('i.options'); ?></th>
        </tr>
        </thead>
        <tbody>
            <?php foreach ($paginator->read() as $client) { ?>
            <tr>
            <td>
                <?= ($client->getClient_active()) ? '<span class="label active">' . $translator->translate('i.yes') . '</span>' : '<span class="label inactive">' . $translator->translate('i.no') . '</span>'; ?>
            </td>
            <td>
		    <?= ($cpR->repoClientCount((string)$client->getClient_id()) !== 0 ) ? '<span class="label active">' . $translator->translate('i.yes') . '</span>' : '<span class="label inactive">' . $translator->translate('i.no') . '</span>'; ?>
            </td>
            <td>
            <?= ($ucR->repoUserqueryCount((string)$client->getClient_id()) !== 0 
                 && $canEdit) 
             ? '<span class="label active">' . 
                  $translator->translate('i.yes') . 
               '</span>' 
             : '<span class="label inactive">' .  
                  Html::a('', $urlGenerator->generate('userinv/add'),
                  ['class'=>'fa fa-plus',
                   'style'=>'text-decoration:none',
                   'tooltip'=>'data-bs-toggle',
                   'title'=>$translator->translate('invoice.client.has.not.user.account') 
                  ]).
               '</span>'; 
            ?>  
            </td>    
            <td><?= Html::a($client->getClient_name()." ".$client->getClient_surname(),$urlGenerator->generate('client/view',['id' => $client->getClient_id()]),['class' => 'btn btn-warning ms-2']);?></td>
            <td><?= Html::encode(($client->getClient_birthdate())->format($datehelper->style())); ?></td>
            <td><?= Html::encode($client->getClient_email()); ?></td>
            <td><?= Html::encode($client->getClient_phone() ? $client->getClient_phone() : ($client->getClient_mobile() ? $client->getClient_mobile() : '')); ?></td>
            <td class="amount"><?= $s->format_currency($iR->with_total_balance($client->getClient_id(), $iaR)); ?></td>
              <td>
                    <div class="options btn-group">
                        <a class="btn btn-default dropdown-toggle" data-toggle="dropdown" href="#" style="text-decoration:none">
                            <i class="fa fa-cog"></i> <?= $translator->translate('i.options'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="<?= $urlGenerator->generate('client/view',['id' => $client->getClient_id()]); ?>" style="text-decoration:none">
                                    <i class="fa fa-eye fa-margin"></i> <?= $translator->translate('i.view'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?= $urlGenerator->generate('client/edit', ['id' => $client->getClient_id(), 'origin' => 'edit']); ?>" style="text-decoration:none">
                                    <i class="fa fa-edit fa-margin"></i> <?= $translator->translate('i.edit'); ?>
                                </a>
                            </li>
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
                            <li>
                                <a href="<?= $urlGenerator->generate('client/delete',['id' => $client->getClient_id()]); ?>" style="text-decoration:none" onclick="return confirm('<?= $translator->translate('i.delete_client_warning').'?'; ?>');">
                                    <i class="fa fa-trash fa-margin"></i><?= $translator->translate('i.delete'); ?>                                    
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
            <?php } ?>            
        </tbody>
    </table>
         
    <?php
        $pageSize = $paginator->getCurrentPageSize();
        if ($pageSize > 0) {
            echo Html::p(
                sprintf($translator->translate('invoice.index.showing').' clients', $pageSize, $paginator->getTotalItems()),
                ['class' => 'text-muted']
            );
        } else {
            echo Html::p($translator->translate('invoice.records.no'));
        }
    ?>
        
</div>
</div>