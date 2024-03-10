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

use Yiisoft\Html\Html;

echo $alert;

$this->setTitle($translator->translate('i.clients'));

?>

<div>
    <?php 
        echo $modal_create_client;
    ?>
</div>

<div>
    <h5><?= Html::encode($this->getTitle()); ?></h5>
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
            <?php foreach ($paginator->read() as $client) { ?>
            <tr>
		<td>
		    <?= ($client->getClient_active()) ? '<span class="label active">' . $translator->translate('i.yes') . '</span>' : '<span class="label inactive">' . $translator->translate('i.no') . '</span>'; ?>
		</td>
                <td>
		    <?= ($cpR->repoClientCount((string)$client->getClient_id()) !== 0 ) ? '<span class="label active">' . $translator->translate('i.yes') . '</span>' : '<span class="label inactive">' . $translator->translate('i.no') . '</span>'; ?>
		</td>
                <td><?= Html::a($client->getClient_name()." ".$client->getClient_surname(),$urlGenerator->generate('client/view',['id' => $client->getClient_id()]),['class' => 'btn btn-warning ms-2']);?></td>
                <td><?= Html::encode($client->getClient_email()); ?></td>
                <td><?= Html::encode($client->getClient_phone() ? $client->getClient_phone() : ($client->getClient_mobile() ? $client->getClient_mobile() : '')); ?></td>
                <td class="amount"><?= $s->format_currency($iR->with_total($client->getClient_id(), $iaR)); ?></td>
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
        
</div>
</div>