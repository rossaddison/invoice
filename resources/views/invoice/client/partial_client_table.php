<?php
    declare(strict_types=1); 
    
    use Yiisoft\Html\Html;
?>   

<div class="table-responsive">
    <table class="table table-hover table-striped">
        <thead>
        <tr>
            <th><?= $translator->translate('i.active'); ?></th>
            <th><?= $translator->translate('i.client_name'); ?></th>
            <th><?= $translator->translate('i.email_address'); ?></th>
            <th><?= $translator->translate('i.phone_number'); ?></th>
            <th class="amount"><?= $translator->translate('i.balance'); ?></th>
            <th><?= $translator->translate('i.options'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($records as $client) : ?>
            <tr>
                <td>
                        <?= ($client->getClient_active()) ? '<span class="label active">' . $translator->translate('i.yes') . '</span>' : '<span class="label inactive">' . trans('no') . '</span>'; ?>
                </td>
                <td><a href ="<?=  $urlGenerator->generate('client/view', ['id' => $client->getClient_id()]); ?>"><?= Html::encode($clienthelper->format_client($client)); ?></td>
                <td><?= Html::encode($client->getClient_email()); ?></td>
                <td><?= Html::encode($client->getClient_phone() ? $client->getClient_phone() : ($client->getClient_mobile() ? $client->getClient_mobile() : '')); ?></td>
                <td class="amount"><?= $s->format_currency($client_invoice_balance); ?></td>
                <td>
                    <div class="options btn-group">
                        <a class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" href="#">
                            <i class="fa fa-cog"></i> <?= $translator->translate('i.options'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="<?= $urlGenerator->generate('client/view', ['id' =>$client->getClient_id()]); ?>">
                                    <i class="fa fa-eye fa-margin"></i><?= $translator->translate('i.view'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?= $urlGenerator->generate('client/edit', ['id' =>$client->getClient_id()]); ?>">
                                    <i class="fa fa-edit fa-margin"></i> <?= $translator->translate('i.edit'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="client-create-quote"
                                   data-client-id="<?= $client->getClient_id(); ?>">
                                    <i class="fa fa-file fa-margin"></i> <?= $translator->translate('i.create_quote'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="client-create-invoice"
                                   data-client-id="<?= $client->getClient_id(); ?>">
                                    <i class="fa fa-file-text fa-margin"></i><?= $translator->translate('i.create_invoice'); ?>
                                </a>
                            </li>
                            <li>
                                <form action="<?= $urlGenerator->generate(...$deleteAction); ?>" method="POST">
                                    <input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>"> 
                                    <button type="submit" class="dropdown-button"
                                            onclick="return confirm('<?= $translator->translate('i.delete_client_warning'); ?>');">
                                        <i class="fa fa-trash-o fa-margin"></i> <?= $translator->translate('i.delete'); ?>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
