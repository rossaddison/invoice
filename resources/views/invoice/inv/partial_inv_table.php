<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Related logic: see App\Invoice\Client\ClientController view function
 *      $parameters['invoice_draft_table']
 *      $parameters['invoice_sent_table']
 *      $parameters['invoice_viewed_table']
 *      $parameters['invoice_paid_table'] ... unpaid to written_off
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\InvAmount\InvAmountRepository $iaR
 * @var App\Invoice\Inv\InvRepository $iR
 * @var App\Invoice\InvRecurring\InvRecurringRepository $irR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Session\SessionInterface $session
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $invoices
 * @var int $invoice_count
 * @var string $csrf
 * @psalm-var array<string, Stringable|null|scalar> $actionDeleteArguments
 * @psalm-var array<string, Stringable|null|scalar> $actionEmailArguments
 * @psalm-var array<string, Stringable|null|scalar> $actionPdfArguments
 * @psalm-var array<string, Stringable|null|scalar> $actionClientViewArguments
 * @psalm-var array<string, Stringable|null|scalar> $actionViewArguments
 */
?>

<div class="table-responsive">
    <table class="table table-hover table-striped">

        <thead>
        <tr>
            <th><?= $translator->translate('status'); ?></th>
            <th><?= $translator->translate('invoice'); ?></th>
            <th><?= $translator->translate('created'); ?></th>
            <th><?= $translator->translate('due.date'); ?></th>
            <th><?= $translator->translate('client.name'); ?></th>
            <th style="text-align: right;"><?= $translator->translate('amount'); ?></th>
            <th style="text-align: right;"><?= $translator->translate('balance'); ?></th>
            <th><?= $translator->translate('options'); ?></th>
        </tr>
        </thead>

        <tbody>
        <?php
        $invoice_idx = 1;
$invoice_list_split = $invoice_count > 3 ? $invoice_count / 2 : 9999;
/**
 * @var App\Invoice\Entity\Inv $invoice
 */
foreach ($invoices as $invoice) {
    // Disable read-only if not applicable
    if ($s->getSetting('disable_read_only') === (string) 1) {
        $invoice->setIs_read_only(false);
    }
    // Convert the dropdown menu to a dropup if invoice is after the invoice split
    $dropup = $invoice_idx > $invoice_list_split ? true : false;
    $actionDeleteArguments = ['_language' => (string) $session->get('_language'), 'id' => $invoice->getId()];
    $actionEmailArguments = ['_language' => (string) $session->get('_language'), 'id' => $invoice->getId()];
    $actionPdfArguments = ['_language' => (string) $session->get('_language'), 'include' => true, 'inv_id' => $invoice->getId()];
    $actionClientViewArguments = ['_language' => (string) $session->get('_language'), 'id' => $invoice->getClient_id()];
    $actionViewArguments = ['_language' => (string) $session->get('_language'), 'id' => $invoice->getId()];
    $statusId = (string) $invoice->getStatus_id();
    ?>
            <tr>
                <td>
                    <span class="label label-<?= $iR->getSpecificStatusArrayClass((int) $statusId); ?>">
                        <?= $iR->getSpecificStatusArrayLabel($statusId); ?>
                        <?php
                    $invoiceId = (int) $invoice->getId();
    if (!empty($invoiceId)) {
        $invAmount = $iaR->repoInvquery($invoiceId);
        if (null !== $invAmount) {
            $count = $iaR->repoInvAmountCount($invoiceId);
            if ($count > 0) {
                if ($invAmount->getSign() === -1) { ?>
                                            &nbsp;<i class="fa fa-credit-invoice" title="<?= $translator->translate('credit.invoice') ?>"></i>
                                        <?php
                }
            }
        }
    }
    ?>
                        <?php if ($invoice->getIs_read_only()) { ?>
                            &nbsp;<i class="fa fa-read-only" title="<?= $translator->translate('read.only') ?>"></i>
                        <?php } ?>
                        <?php if ($irR->repoCount((string) $invoice->getId()) > 0) { ?>
                            &nbsp;<i class="fa fa-refresh" title="<?= $translator->translate('recurring') ?>"></i>
                        <?php } ?>
                    </span>
                </td>

                <td>
                    <a href="<?= $urlGenerator->generate('inv/view', $actionViewArguments); ?>"
                       title="<?= $translator->translate('edit'); ?>" style="text-decoration:none">
                        <?= (null !== ($invoice->getNumber()) ? $invoice->getNumber() : $invoice->getId()); ?>
                    </a>
                </td>

                <td>
                    <?= $invoice->getDate_created()->format('Y-m-d'); ?>
                </td>

                <td>
                    <span class="<?php if ($invoice->isOverdue()) { ?>font-overdue<?php } ?>">
                        <?= $invoice->getDate_due()->format('Y-m-d'); ?>
                    </span>
                </td>

                <td>
                    <a href="<?= $urlGenerator->generate('client/view', $actionClientViewArguments); ?>"
                       title="<?= $translator->translate('view.client'); ?>" style="text-decoration:none">
                        <?= Html::encode($clientHelper->format_client($invoice->getClient())); ?>
                    </a>
                </td>

                <td class="amount 
                <?php
                    $inv_amount = $iaR->repoInvAmountCount((int) $invoice->getId()) > 0 ? $iaR->repoInvquery((int) $invoice->getId()) : null;
    if ((null !== $inv_amount) && ($inv_amount->getSign() === -1)) {
        echo 'text-danger';
    } ?>">  
                    
                    <?= null !== $inv_amount ? $s->format_currency($inv_amount->getTotal()) : 0.00; ?>
                </td>

                <td class="amount">
                    <?= null != $inv_amount ? $s->format_currency($inv_amount->getBalance()) : 0.00; ?>
                </td>

                <td>
                    <div class="options btn-group<?= $dropup ? ' dropup' : ''; ?>">
                        <a class="btn btn-default btn-sm dropdown-toggle" data-bs-toggle="dropdown" href="#" style="text-decoration:none">
                            <i class="fa fa-cog"></i> <?= $translator->translate('options'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if ($invoice->getIs_read_only() !== true) { ?>
                                <li>
                                    <a href="<?= $urlGenerator->generate('inv/view', $actionViewArguments); ?>" style="text-decoration:none">
                                        <i class="fa fa-edit fa-margin"></i> <?= $translator->translate('edit'); ?>
                                    </a>
                                </li>
                            <?php } ?>
                            <li>
                                <a href="<?= $urlGenerator->generate('inv/pdf', $actionPdfArguments); ?>"
                                   target="_blank" style="text-decoration:none">
                                    <i class="fa fa-print fa-margin"></i> <?= $translator->translate('download.pdf'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?= $urlGenerator->generate('inv/email_stage_0', $actionEmailArguments); ?>" style="text-decoration:none">
                                    <i class="fa fa-send fa-margin"></i> <?= $translator->translate('send.email'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="invoice-add-payment"
                                   data-invoice-id="<?= $invoice->getId(); ?>"
                                   data-invoice-balance="<?=  null !== $inv_amount ? $inv_amount->getBalance() : 0.00; ?>"
                                   data-invoice-payment-method="<?= $invoice->getPayment_method(); ?>">
                                    <i class="fa fa-money fa-margin"></i>
                                    <?= $translator->translate('enter.payment'); ?>
                                </a>
                            </li>
                            <?php if (
                                $invoice->getStatus_id() === 1
                                || ($s->getSetting('enable_invoice_deletion') == 1  && $invoice->getIs_read_only() !== true)
                            ) { ?>
                                <li>
                                    <form action="<?= $urlGenerator->generate('inv/delete', $actionDeleteArguments); ?>" method="POST">
                                        <input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>">
                                        <button type="submit" class="dropdown-button"
                                                onclick="return confirm('<?= $translator->translate('delete.invoice.warning'); ?>');">
                                            <i class="fa fa-trash-o fa-margin"></i> <?= $translator->translate('delete'); ?>
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
