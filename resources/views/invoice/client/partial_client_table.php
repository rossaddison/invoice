<?php
    declare(strict_types=1); 
    
    use Yiisoft\Html\Html;
?>   

<div class="table-responsive">
    <table class="table table-hover table-striped">
        <thead>
        <tr>
            <th><?= $s->trans('active'); ?></th>
            <th><?= $s->trans('client_name'); ?></th>
            <th><?= $s->trans('email_address'); ?></th>
            <th><?= $s->trans('phone_number'); ?></th>
            <th class="amount"><?= $s->trans('balance'); ?></th>
            <th><?= $s->trans('options'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($records as $client) : ?>
            <tr>
                <td>
                        <?= ($client->getClient_active()) ? '<span class="label active">' . $s->trans('yes') . '</span>' : '<span class="label inactive">' . trans('no') . '</span>'; ?>
                </td>
                <td><a href ="<?=  $urlGenerator->generate('client/view', ['id' => $client->getClient_id()]); ?>"><?= Html::encode($clienthelper->format_client($client)); ?></td>
                <td><?= Html::encode($client->getClient_email()); ?></td>
                <td><?= Html::encode($client->getClient_phone() ? $client->getClient_phone() : ($client->getClient_mobile() ? $client->getClient_mobile() : '')); ?></td>
                <td class="amount"><?= $s->format_currency($client_invoice_balance); ?></td>
                <td>
                    <div class="options btn-group">
                        <a class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" href="#">
                            <i class="fa fa-cog"></i> <?= $s->trans('options'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="<?= $urlGenerator->generate('client/view', ['id' =>$client->getClient_id()]); ?>">
                                    <i class="fa fa-eye fa-margin"></i><?= $s->trans('view'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?= $urlGenerator->generate('client/edit', ['id' =>$client->getClient_id()]); ?>">
                                    <i class="fa fa-edit fa-margin"></i> <?= $s->trans('edit'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="client-create-quote"
                                   data-client-id="<?= $client->getClient_id(); ?>">
                                    <i class="fa fa-file fa-margin"></i> <?= $s->trans('create_quote'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="client-create-invoice"
                                   data-client-id="<?= $client->getClient_id(); ?>">
                                    <i class="fa fa-file-text fa-margin"></i><?= $s->trans('create_invoice'); ?>
                                </a>
                            </li>
                            <li>
                                <form action="<?= $urlGenerator->generate(...$deleteAction); ?>" method="POST">
                                    <input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>"> 
                                    <button type="submit" class="dropdown-button"
                                            onclick="return confirm('<?= $s->trans('delete_client_warning'); ?>');">
                                        <i class="fa fa-trash-o fa-margin"></i> <?= $s->trans('delete'); ?>
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
