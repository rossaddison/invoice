<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @see App\Invoice\Client\ClientController function view 'payment_table'
 *
 * @var App\Invoice\Entity\Client $client
 *
 * @see There is no need to declare ClientHelper in the ClientController because it is declared in:
 * @see config\common\params 'yiisoft/view' => ['parameters' => 'clientHelper' Reference::to(ClientHelper::class),
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 *
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $payments
 * @var string $csrf
 */

?>      

<div class="table-responsive">
    <table class="table table-hover table-striped">

        <thead>
        <tr>
            <th><?= $translator->translate('payment.date'); ?></th>
            <th><?= $translator->translate('date'); ?></th>
            <th><?= $translator->translate('invoice'); ?></th>
            <th><?= $translator->translate('client'); ?></th>
            <th><?= $translator->translate('amount'); ?></th>
            <th><?= $translator->translate('payment.method'); ?></th>
            <th><?= $translator->translate('note'); ?></th>
            <th><?= $translator->translate('options'); ?></th>
        </tr>
        </thead>

        <tbody>
        <?php
            /**
             * @var App\Invoice\Entity\Payment $payment
             */
            foreach ($payments as $payment) { ?>
            <?php if ($payment->getInv()?->getClient_id() === $client->getClient_id()) { ?>
            <tr>
                <td><?= !is_string($paymentDate = $payment->getPayment_date()) ? ($paymentDate->format('Y-m-d')) : ''; ?></td>
                <td><?=  ($payment->getInv()?->getDate_created())->format('Y-m-d'); ?></td>                
                <td>
                    <a href="<?=  $urlGenerator->generate('inv/view', ['id' => $payment->getInv_id()]); ?>">
                        <?= Html::encode($payment->getInv()?->getNumber() ?? '#'); ?>
                    </a>
                </td>
                <td>
                    <a href="<?=  $urlGenerator->generate('client/view', ['id' => $payment->getInv()?->getClient_id()]); ?>"
                       title="<?= $translator->translate('view.client'); ?>">
                       <?= Html::encode($clientHelper->format_client($payment->getInv()?->getClient())); ?>
                    </a>
                </td>
                <td class="amount"><?= $s->format_currency($payment->getAmount() ?? 0.00); ?></td>
                <td><?= Html::encode($payment->getPaymentMethod()?->getName() ?? ''); ?></td>
                <td><?= Html::encode($payment->getNote() ?: ''); ?></td>
                <td>
                    <div class="options btn-group">
                        <a class="btn btn-default btn-sm dropdown-toggle" data-bs-toggle="dropdown" href="#">
                            <i class="fa fa-cog"></i> <?= $translator->translate('options'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="<?=  $urlGenerator->generate('client/view', ['id' => $payment->getInv()?->getClient_id()]); ?>"
                                    title="<?= $translator->translate('view.client'); ?>">
                                    <?= Html::encode($clientHelper->format_client($payment->getInv()?->getClient())); ?>
                                </a>
                                <a href="<?=  $urlGenerator->generate('payment/edit', ['id' => $payment->getId()]); ?>">
                                    <i class="fa fa-edit fa-margin"></i>
                                    <?= $translator->translate('edit'); ?>
                                </a>
                            </li>
                            <li>
                                <form action="<?= $urlGenerator->generate('payment/delete', ['id' => $payment->getId()]); ?>" method="POST">
                                    <input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>"> 
                                    <button type="submit" class="dropdown-button"
                                            onclick="return confirm('<?= $translator->translate('delete.record.warning'); ?>');">
                                        <i class="fa fa-trash-o fa-margin"></i> <?= $translator->translate('delete'); ?>
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
