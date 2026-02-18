<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Related logic: see src\Invoice\InvoiceController function dashboard
 * Related logic: see App\Invoice\Inv\InvRepository function getStatuses
 *
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Inv\InvRepository $iR
 * @var App\Invoice\InvAmount\InvAmountRepository $iaR
 * @var App\Invoice\InvRecurring\InvRecurringRepository $irR
 * @var App\Invoice\Quote\QuoteRepository $qR
 * @var App\Invoice\QuoteAmount\QuoteAmountRepository $qaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Invoice\Task\TaskRepository $taskR
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var array $invoice_status_totals
 * @var array $invoices
 * @var array $projects
 * @var array $quotes
 * @var array $quote_status_totals
 * @var array $overdueInvoices
 * @var array $tasks
 * @var array $task_statuses
 * @var int $client_count
 * @var string $alerts
 * @var string $modal_create_client
 * @var string $invoice_status_period
 * @var string $quote_status_period
 */
?>

<div id="content">

<?= $alerts; ?>

    <div class="row <?= ($s->getSetting('disable_quickactions') == '1' ?
        'hidden' : ''); ?>">
        <div class="col-xs-12">

            <div id="panel-quick-actions"
                 class="panel panel-default quick-actions">
                <div class="panel-heading">
                    <b><?= $translator->translate('quick.actions'); ?></b>
                </div>
                <div class="btn-group btn-group-justified no-margin">
                    <?php
                        echo $modal_create_client;
?>
                    <?php if ($client_count === 0) { ?>
                    <a href="#create-client"
                       class="btn btn-success"
                       data-bs-toggle="modal"
                       disabled data-bs-toggle = "tooltip"
                       title="<?= $translator->translate('add.client'); ?>"
                       style="text-decoration:none">
                        <i class="fa fa-plus"></i>
                        <?= $translator->translate('client'); ?>
                    </a>
                    <?php } else { ?>
                    <a href="<?= $urlGenerator->generate('client/add',
                            ['origin' => 'dashboard']); ?>"
                        class="btn btn-success"
                        style="text-decoration:none">
                        <i class="fa fa-plus"></i>
                        <?= $translator->translate('client'); ?>
                    </a>
                    <?php } ?>
                    <?php if ($client_count === 0) { ?>
                    <a href="#create-quote"
                       class="btn btn-success"
                       data-bs-toggle="modal"
                       disabled data-bs-toggle = "tooltip"
                       title="<?= $translator->translate('add.client'); ?>"
                       style="text-decoration:none">
                       <i class="fa fa-plus"></i>
                       <?= $translator->translate('quote'); ?>
                    </a>
                    <?php } else { ?>
                    <a href="<?= $urlGenerator->generate('quote/add',
                            ['origin' => 'dashboard']);?>"
                       class="btn btn-success"
                       style="text-decoration:none">
                       <i class="fa fa-plus"></i>
                       <?= $translator->translate('quote'); ?>
                    </a>
                    <?php } ?>
                    <?php if ($client_count === 0) { ?>
                    <a href="#create-inv"
                       class="btn btn-success"
                       data-bs-toggle="modal"
                       disabled data-bs-toggle = "tooltip"
                       title="<?= $translator->translate('add.client'); ?>"
                       style="text-decoration:none">
                       <i class="fa fa-plus"></i>
                       <?= $translator->translate('invoice'); ?>
                    </a>
                    <?php } else { ?>
                    <a href="<?= $urlGenerator->generate('inv/add',
                            ['origin' => 'dashboard']);?>"
                       class="btn btn-success"
                       style="text-decoration:none">
                       <i class="fa fa-plus"></i>
                       <?= $translator->translate('invoice'); ?>
                    </a>
                    <?php } ?>
                    <a href="<?= $urlGenerator->generate('payment/add') ; ?>"
                       class="btn btn-default" style="text-decoration:none">
                       <i class="fa fa-credit-card fa-margin"></i>
                       <span class="hidden-xs">
                            <?= $translator->translate('enter.payment'); ?>
                       </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php
    // Quote Overview
?>
    <div class = 'row'>
        <div class="col-xs-12 col-md-6">
            <div id="panel-quote-overview"
                 class="panel panel-default overview">
                <div class="panel-heading">
                    <b>
                        <i class="fa fa-bar-chart fa-margin"></i>
                        <?= $translator->translate('quote.overview'); ?>
                    </b>
                    <span class="pull-right text-muted">
                        <?= $s->lang($quote_status_period); ?>
                    </span>
                </div>
                <table class="table table-hover table-bordered
                       table-condensed no-margin">
                    <thead>
                        <tr>
                            <th style="min-width: 1%;">
                                <?= $translator->translate('status'); ?>
                            </th>
                            <th style="min-width: 1%;">
                                <?= $translator->translate('amount'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        /**
                         * @var array $total
                         */
                        foreach ($quote_status_totals as $total) { ?>
                        <tr>
                            <td>
                                <a href="<?= $urlGenerator->generate('quote/index',
                                        ['page' => 1,
                                            'status' => (int) $total['href']]); ?>">
                                    <?php echo (string) $total['label']; ?>
                                </a>
                            </td>
                            <td class="amount">
                            <span class="<?= (string) $total['class']; ?>">
                                <?= $s->format_currency($total['sum_total']); ?>
                            </span>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>

        </div>
<?php
    // Invoice Overview
?>        
        <div class="col-xs-12 col-md-6">

            <div id="panel-invoice-overview" class="panel panel-default overview">

                <div class="panel-heading">
                    <b>
                        <i class="fa fa-bar-chart fa-margin"></i>
                            <?= $translator->translate('overview'); ?>
                    </b>
                    <span class="pull-right text-muted">
                        <?= $s->lang($invoice_status_period); ?>
                    </span>
                </div>

                <table class="table table-hover table-bordered
                       table-condensed no-margin">
                    <thead>
                        <tr>
                            <th style="min-width: 1%;">
                                <?= $translator->translate('status'); ?>
                            </th>
                            <th style="min-width: 1%;">
                                <?= $translator->translate('amount'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        $i = 1;
                        /**
                         * @var array $total
                         */
                        foreach ($invoice_status_totals as $total) { ?>
                        <tr>
                            <td>
                                <a href="<?= $urlGenerator->generate('inv/index',
                                        ['page' => 1,
                                            'status' => (int) $total['href']]); ?>">
                                    <?= (string) $total['label']; ?>
                                </a>
                            </td>
                            <td class="amount">
                                <span class="<?= (string) $total['class']; ?>">
                                    <?= $s->format_currency($total['sum_total']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php $i = $i + 1;
} ?>                </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class = 'row'>
        <div class="col-xs-12 col-md-6">
            <div id="panel-recent-quotes" class="panel panel-default">
                <div class="panel-heading">
                    <b>
                        <i class="fa fa-history fa-margin"></i>
                        <?= $translator->translate('recent.quotes'); ?>
                    </b>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped
                           table-condensed no-margin">
                        <thead>
                        <tr>
                            <th>
                                <?= $translator->translate('status'); ?>
                            </th>
                            <th style="min-width: 15%;">
                                <?= $translator->translate('date'); ?>
                            </th>
                            <th style="min-width: 15%;">
                                <?= $translator->translate('quote'); ?>
                            </th>
                            <th style="min-width: 35%;">
                                <?= $translator->translate('client'); ?>
                            </th>
                            <th style="text-align: right;">
                                <?= $translator->translate('balance'); ?>
                            </th>
                            <th style="text-align: right;">
                                <?= $translator->translate('custom.fields'); ?>
                            </th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            /**
                             * @var App\Invoice\Entity\Quote $quote
                             */
                            foreach ($quotes as $quote) { ?>
                            <tr>
                                <td>
                                <?php if (
                                null !== $statusId = $quote->getStatus_id()) { ?>    
                                    <span class="label label-
                                    <?= $qR->getSpecificStatusArrayClass(
                                            (string) $statusId); ?>">
                                        <?= $qR->getSpecificStatusArrayLabel(
                                                (string) $statusId); ?>
                                    </span>
                                <?php } ?>
                                </td>
                                <td>
                                    <?= $quote->getDate_created()->format('Y-m-d'); ?>
                                </td>
                                <td>
                                    <a href="<?= $urlGenerator->generate('quote/view',
                                            ['id' => $quote->getId()]); ?>"
                                       title="<?=  (($quote->getNumber() ?? '#') ?:
                                                    ($quote->getId() ?? '#')); ?>"
                                       class="btn btn-default"
                                       style="text-decoration:none">
                                            <?= (($quote->getNumber() ?? '#') ?:
                                                    ($quote->getId() ?? '#')); ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="<?= $urlGenerator->generate('client/view',
                                            ['id' => $quote->getClient_id()]); ?>"
                                       title="<?=  (($quote->getNumber() ?? '#') ?:
                                            ($quote->getId() ?? '#')); ?>"
                                       class="btn btn-default"
                                       style="text-decoration:none">
            <?= Html::encode($clientHelper->format_client($quote->getClient())); ?>
                                    </a>                                   
                                </td>
                                <td class="amount">
<?php $quote_amount = (($qaR->repoQuoteAmountCount((string) $quote->getId()) > 0) ?
        $qaR->repoQuotequery((string) $quote->getId()) : null) ?>
<?= $s->format_currency(null !== $quote_amount ? $quote_amount->getTotal() : 0.00) ?>                                    
                                </td>
                                <td style="text-align: center;">
                                    <a href="<?= $urlGenerator->generate(
                                            'quote/pdf_dashboard_include_cf',
                                            ['id' => $quote->getId()]); ?>"
                                       title="<?= $translator->translate('download.pdf'); ?>"
                                       class="btn btn-default"
                                       style="text-decoration:none">
                                       <i class="fa fa-file-pdf-o"></i>
                                    </a>
                                </td>
                                <td style="text-align: center;">
                                    <a href="<?= $urlGenerator->generate(
                                            'quote/pdf_dashboard_exclude_cf',
                                            ['id' => $quote->getId()]); ?>"
                                       title="<?= $translator->translate(
                                               'download.pdf'); ?>"
                                       class="btn btn-default"
                                       style="text-decoration:none">
                                       <i class="fa fa-file-pdf-o"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>           
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
<?php
    // Recent Invoices
?>        
        <div class="col-xs-12 col-md-6">

            <div id="panel-recent-invoices-1" class="panel panel-default">

                <div class="panel-heading">
                    <b>
                        <i class="fa fa-history fa-margin"></i>
                        <?= $translator->translate('recent.invoices'); ?>
                    </b>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped
                           table-condensed no-margin">
                        <thead>
                        <tr>
                            <th>
                                <?= $translator->translate('status'); ?>
                            </th>
                            <th style="min-width: 15%;">
                                <?= $translator->translate('due.date'); ?>
                            </th>
                            <th style="min-width: 15%;">
                                <?= $translator->translate('invoice'); ?>
                            </th>
                            <th style="min-width: 35%;">
                                <?= $translator->translate('client'); ?>
                            </th>
                            <th style="text-align: right;">
                                <?= $translator->translate('balance'); ?>
                            </th>
                            <th style="text-align: right;">
                                <?= $translator->translate('custom.fields'); ?>
                            </th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>

                        <?php
                            /**
                             * @var App\Invoice\Entity\Inv $invoice
                             */
                            foreach ($invoices as $invoice) {
                                if ($s->getSetting('disable_read_only') == '1') {
                                    $invoice->setIs_read_only(false);
                                } ?>
                            <tr>
                                <td>
                 <?php if (null !== ($statusId = $invoice->getStatus_id())) { ?>
                                    <span class="label label-<?=
                                $iR->getSpecificStatusArrayClass($statusId); ?>">
                        <?= $iR->getSpecificStatusArrayLabel((string) $statusId);
     if (null !== $iaR->repoCreditInvoicequery((string) $invoice->getId())) { ?>
                                        &nbsp;
                                        <i class="fa fa-credit-invoice"
                                           title="<?= $translator->translate(
                                                    'credit.invoice') ?>"></i>
                                            <?php } ?>

                                            <?php if ($invoice->getIs_read_only()) { ?>
                                        &nbsp;
                                        <i class="fa fa-read-only"
                                           title="<?= $translator->translate(
                                                   'read.only') ?>"></i>
                                            <?php } ?>
               <?php if (($irR->repoCount((string) $invoice->getId()) > 0)) { ?>
                                        &nbsp;
                                        <i class="fa fa-refresh"
                                           title="<?php $translator->translate(
                                                   'recurring') ?>"></i>
                                            <?php } ?>
                                    </span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <span class="<?= $invoice->isOverdue() ?
                                            'font-overdue' : ''; ?>">
                                <?= $invoice->getDate_due()->format('Y-m-d'); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= $urlGenerator->generate('inv/view',
                                            ['id' => $invoice->getId()]); ?>"
                                       class="btn btn-default"
                                       style="text-decoration:none">
                                        <?= ($invoice->getNumber() ?? '#'
                                               . ($invoice->getId() ?? '#')); ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="<?= $urlGenerator->generate(
                                            'client/view',
                                            ['id' => $invoice->getClient_id()]); ?>"
                                        class="btn btn-default"
                                        style="text-decoration:none">
     <?= (Html::encode($clientHelper->format_client($invoice->getClient()))); ?>
                                    </a>
                                </td>
                                <td class="amount">
    <?php $inv_amount = (($iaR->repoInvAmountCount((int) $invoice->getId()) > 0)
                        ? $iaR->repoInvquery((int) $invoice->getId()) : null) ?>
<?= $s->format_currency(null !== $inv_amount ? $inv_amount->getBalance() : 0.00) ?> 
                                </td>                               
                                <td style="text-align: center;">
                                    <a href="<?= $urlGenerator->generate(
                                            'inv/pdf_dashboard_include_cf',
                                            ['id' => $invoice->getId()]); ?>"
                                       title="<?= $translator->translate(
                                               'download.pdf'); ?>"
                                       class="btn btn-default"
                                       style="text-decoration:none">
                                       <i class="fa fa-file-pdf-o"></i>
                                    </a>
                                </td>
                                <td style="text-align: center;">
                                    <a href="<?= $urlGenerator->generate(
                                            'quote/pdf_dashboard_exclude_cf',
                                            ['id' => $invoice->getId()]); ?>"
                                       title="<?= $translator->translate(
                                               'download.pdf'); ?>"
                                       class="btn btn-default"
                                       style="text-decoration:none">
                                       <i class="fa fa-file-pdf-o"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php
    // Projects
?>
    <?php if ($s->getSetting('projects_enabled') == 1) : ?>
        <div class = 'row'>
            <div class="col-xs-12 col-md-6">
                <div id="panel-projects" class="panel panel-default">
                    <div class="panel-heading">
                        <b>
                            <i class="fa fa-list fa-margin"></i>
                            <?= $translator->translate('projects'); ?>
                        </b>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped
                               table-condensed no-margin">
                            <thead>
                            <tr>
                                <th>
                                    <?= $translator->translate('project.name'); ?>
                                </th>
                                <th>
                                    <?= $translator->translate('client.name'); ?>
                                </th>
                            </tr>
                            </thead>

                            <tbody>
                            <?php
                                /**
                                 * @var App\Invoice\Entity\Project $project
                                 */
                                foreach ($projects as $project) { ?>
                                <tr>
                                    <td>
                                        <a href="<?= $urlGenerator->generate(
                                                'project/view',
                                                ['id' => $project->getId()]); ?>">
                                            <?= Html::encode($project->getName()); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="<?= $urlGenerator->generate(
                                                'client/view',
                                                ['id' => $project->getClient_id()]); ?>">
       <?= Html::encode($clientHelper->format_client($project->getClient())); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                                <tr>
                                    <td colspan="6"
                                        class="text-right small">
                                        <a href="<?= $urlGenerator->generate(
                                                'project/index'); ?>">
                                      <?= $translator->translate('view.all'); ?>
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
<?php
    // Tasks
        ?>
            <div class="col-xs-12 col-md-6">
                <div id="panel-recent-invoices-2" class="panel panel-default">
                     <div class="panel-heading">
                        <b>
                            <i class="fa fa-check-square-o fa-margin"></i>
                            <?= $translator->translate('tasks'); ?>
                        </b>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped
                               table-condensed no-margin">
                            <thead>
                            <tr>
                                <th>
                                    <?= $translator->translate('status'); ?>
                                </th>
                                <th>
                                    <?= $translator->translate('task.name'); ?>
                                </th>
                                <th>
                                    <?= $translator->translate('task.finish.date'); ?>
                                </th>
                                <th>
                                    <?= $translator->translate('project'); ?>
                                </th>
                            </tr>
                            </thead>

                            <tbody>
                            <?php
                                        /**
                                         * @var App\Invoice\Entity\Task $task
                                         */
                                        foreach ($taskR->findAllPreloaded() as $task) { ?>
                                <tr>
                                    <td>
                                    <span class="label
 <?= $taskR->getSpecificStatusArrayClass($task->getStatus() ?? 1); ?>">
 <?= $taskR->getSpecificStatusArrayLabel((string) ($task->getStatus() ?? 1)); ?>
                                    </span>
                                    </td>
                                    <td>
                                        <a href="<?= $urlGenerator->generate(
                                                'task/edit',
                                                ['id' => $task->getId()]); ?>">
                                         <?= Html::encode($task->getName()); ?>   
                                        </a>
                                    </td>
                                    <td>
                                    <span class="<?php if ($task->Is_overdue()) { ?>
                                          font-overdue<?php } ?>">
                    <?= !is_string($taskFinishDate = $task->getFinish_date()) ?
                                       $taskFinishDate->format('Y-m-d') : ''; ?>
                                    </span>
                                    </td>
                                    <td>
                                    <?php  if (!empty($task->getProject_id())) { ?>
                                            <a href="<?= $urlGenerator->generate(
                                                    'project/view',
                                         ['id' => $task->getProject_id()]); ?>">
                                    <?= Html::encode($task->getName()); ?></a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                                    <tr>
                                    <td colspan="6"
                                        class="text-right small">
                                        <a href="<?= $urlGenerator->generate(
                                                'task/index'); ?>">
                        <?= Html::encode($translator->translate('view.all')); ?>
                                        </a>
                                    </td>
                                    </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
