<?php
    declare(strict_types=1); 
    
    /**
     * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
     * @var string $csrf
     */
    
    use Yiisoft\Html\Html;
?>      

<div class="table-responsive">
    <table class="table table-hover table-striped">

        <thead>
        <tr>
            <th><?= $s->trans('payment_date'); ?></th>
            <th><?= $s->trans('invoice_date'); ?></th>
            <th><?= $s->trans('invoice'); ?></th>
            <th><?= $s->trans('client'); ?></th>
            <th><?= $s->trans('amount'); ?></th>
            <th><?= $s->trans('payment_method'); ?></th>
            <th><?= $s->trans('note'); ?></th>
            <th><?= $s->trans('options'); ?></th>
        </tr>
        </thead>

        <tbody>
        <?php foreach ($payments as $payment) { ?>
            <?php if ($payment->getInv()?->getClient_id() === $client->getClient_id()) { ?>
            <tr>
                <td><?=  $s->date_from_mysql($payment->getPayment_date()); ?></td>
                <td><?=  $s->date_from_mysql($payment->getInv()?->getDate_created()); ?></td>                
                <td>
                    <a href="<?=  $urlGenerator->generate('inv/view',['id'=>$payment->getInv_id()]); ?>">
                        <?= Html::encode($payment->getInv()?->getNumber()); ?>
                    </a>
                </td>
                <td>
                    <a href="<?=  $urlGenerator->generate('client/view',['id'=>$payment->getInv()->getClient_id()]); ?>"
                       title="<?= $s->trans('view_client'); ?>">
                       <?= Html::encode($clienthelper->format_client($payment->getInv()->getClient())); ?>
                    </a>
                </td>
                <td class="amount"><?= $s->format_currency($payment->getAmount()); ?></td>
                <td><?= Html::encode($payment->getPayment_method()->getName()); ?></td>
                <td><?= Html::encode($payment->getNote()); ?></td>
                <td>
                    <div class="options btn-group">
                        <a class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" href="#">
                            <i class="fa fa-cog"></i> <?= $s->trans('options'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="<?=  $urlGenerator->generate('client/view',['id'=>$payment->getInv()->getClient_id()]); ?>"
                                    title="<?= $s->trans('view_client'); ?>">
                                    <?= Html::encode($clienthelper->format_client($payment->getInv()->getClient())); ?>
                                </a>
                                <a href="<?=  $urlGenerator->generate('payment/edit',['id'=>$payment->getId()]); ?>">
                                    <i class="fa fa-edit fa-margin"></i>
                                    <?= $s->trans('edit'); ?>
                                </a>
                            </li>
                            <li>
                                <form action="<?= $urlGenerator->generate('payment/delete',['id'=> $payment->getId()]); ?>" method="POST">
                                    <input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>"> 
                                    <button type="submit" class="dropdown-button"
                                            onclick="return confirm('<?= $s->trans('delete_record_warning'); ?>');">
                                        <i class="fa fa-trash-o fa-margin"></i> <?= $s->trans('delete'); ?>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
           <?php } ?> 
        <?php } ?>
        </tbody>

    </table>
</div>
