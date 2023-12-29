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
            <th><?= $translator->translate('i.status'); ?></th>
            <th><?= $translator->translate('i.invoice'); ?></th>
            <th><?= $translator->translate('i.created'); ?></th>
            <th><?= $translator->translate('i.due_date'); ?></th>
            <th><?= $translator->translate('i.client_name'); ?></th>
            <th style="text-align: right;"><?= $translator->translate('i.amount'); ?></th>
            <th style="text-align: right;"><?= $translator->translate('i.balance'); ?></th>
            <th><?= $translator->translate('i.options'); ?></th>
        </tr>
        </thead>

        <tbody>
        <?php
        $invoice_idx = 1;
        $invoice_list_split = $invoice_count > 3 ? $invoice_count / 2 : 9999;
        foreach ($invoices as $invoice) {
            // Disable read-only if not applicable
            if ($s->get_setting('disable_read_only') === true) {
                $invoice->setIs_read_only(0);
            }
            // Convert the dropdown menu to a dropup if invoice is after the invoice split
            $dropup = $invoice_idx > $invoice_list_split ? true : false;
            ?>
            <tr>
                <td>
                    <span class="label <?= $inv_statuses[(string)$invoice->getStatus_id()]['class']; ?>">
                        <?= $inv_statuses[(string)$invoice->getStatus_id()]['label']; ?>
                        <?php if ($iaR->repoInvAmountCount((int)$invoice->getId()) > 0) { 
                            if ($iaR->repoInvquery((int)$invoice->getId())->getSign() === '-1') { ?>
                            &nbsp;<i class="fa fa-credit-invoice" title="<?= $translator->translate('i.credit_invoice') ?>"></i>
                            <?php } ?>
                        <?php } ?>
                        <?php if ($invoice->getIs_read_only()) { ?>
                            &nbsp;<i class="fa fa-read-only" title="<?= $translator->translate('i.read_only') ?>"></i>
                        <?php } ?>
                        <?php if ($irR->repoCount((string)$invoice->getId()) > 0) { ?>
                            &nbsp;<i class="fa fa-refresh" title="<?= $translator->translate('i.recurring') ?>"></i>
                        <?php } ?>
                    </span>
                </td>

                <td>
                    <a href="<?= $urlGenerator->generate('inv/view', ['_language' => $session->get('_language'), 'id'=> $invoice->getId()]); ?>"
                       title="<?= $translator->translate('i.edit'); ?>" style="text-decoration:none">
                        <?php echo($invoice->getNumber() ? $invoice->getNumber() : $invoice->getId()); ?>
                    </a>
                </td>

                <td>
                    <?= $datehelper->date_from_mysql($invoice->getDate_created()); ?>
                </td>

                <td>
                    <span class="<?php if ($invoice->isOverdue()) { ?>font-overdue<?php } ?>">
                        <?= $datehelper->date_from_mysql($invoice->getDate_due()); ?>
                    </span>
                </td>

                <td>
                    <a href="<?= $urlGenerator->generate('client/view', ['_language' => $session->get('_language'), 'id' => $invoice->getClient_id()]); ?>"
                       title="<?= $translator->translate('i.view_client'); ?>" style="text-decoration:none">
                        <?= Html::encode($clienthelper->format_client($invoice->getClient())); ?>
                    </a>
                </td>

                <td class="amount 
                <?php 
                    $inv_amount = $iaR->repoInvAmountCount((int)$invoice->getId()) > 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null;
                    if ((null!==$inv_amount) && ($inv_amount->getSign() === '-1')) {
                        echo 'text-danger';
                } ?>">  
                    
                    <?= null!==$inv_amount ? $s->format_currency($inv_amount->getTotal()) : 0.00; ?>
                </td>

                <td class="amount">
                    <?= null!=$inv_amount ? $s->format_currency($inv_amount->getBalance()) : 0.00; ?>
                </td>

                <td>
                    <div class="options btn-group<?= $dropup ? ' dropup' : ''; ?>">
                        <a class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" href="#" style="text-decoration:none">
                            <i class="fa fa-cog"></i> <?= $translator->translate('i.options'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if ($invoice->getIs_read_only() !== 1) { ?>
                                <li>
                                    <a href="<?= $urlGenerator->generate('inv/view', ['_language' => $session->get('_language'), 'id' => $invoice->getId()]); ?>" style="text-decoration:none">
                                        <i class="fa fa-edit fa-margin"></i> <?= $translator->translate('i.edit'); ?>
                                    </a>
                                </li>
                            <?php } ?>
                            <li>
                                <a href="<?= $urlGenerator->generate('inv/pdf', ['_language' => $session->get('_language'), 'include' => true]); ?>"
                                   target="_blank" style="text-decoration:none">
                                    <i class="fa fa-print fa-margin"></i> <?= $translator->translate('i.download_pdf'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?= $urlGenerator->generate('inv/email_stage_0', ['_language' => $session->get('_language'), 'id' => $invoice->getId()]); ?>" style="text-decoration:none">
                                    <i class="fa fa-send fa-margin"></i> <?= $translator->translate('i.send_email'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="invoice-add-payment"
                                   data-invoice-id="<?= $invoice->getId(); ?>"
                                   data-invoice-balance="<?=  null!== $inv_amount ? $inv_amount->getBalance() : 0.00; ?>"
                                   data-invoice-payment-method="<?= $invoice->getPayment_method(); ?>">
                                    <i class="fa fa-money fa-margin"></i>
                                    <?= $translator->translate('i.enter_payment'); ?>
                                </a>
                            </li>
                            <?php if (
                                $invoice->getStatus_id() === 1 ||
                                ($s->get_setting('enable_invoice_deletion') === true && $invoice->getIs_read_only() !== 1)
                            ) { ?>
                                <li>
                                    <form action="<?= $urlGenerator->generate('inv/delete',['_language' => $session->get('_language'), 'id'=> $invoice->getId()]); ?>" method="POST">
                                        <input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>">
                                        <button type="submit" class="dropdown-button"
                                                onclick="return confirm('<?= $translator->translate('i.delete_invoice_warning'); ?>');">
                                            <i class="fa fa-trash-o fa-margin"></i> <?= $translator->translate('i.delete'); ?>
                                        </button>
                                    </form>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </td>
            </tr>
            <?php
            $invoice_idx++;
        } ?>
        </tbody>
    </table>
</div>
