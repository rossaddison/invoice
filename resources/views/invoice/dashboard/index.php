<?php

    declare(strict_types=1);
    
    use Yiisoft\Html\Html;
    
    /**
     * @see src\Invoice\InvoiceController function dashboard
     * @see App\Invoice\Inv\InvRepository function getStatuses
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

    <div class="row <?= ($s->get_setting('disable_quickactions') == '1' ? 'hidden' : ''); ?>">
        <div class="col-xs-12">

            <div id="panel-quick-actions" class="panel panel-default quick-actions">
                <div class="panel-heading">
                    <b><?= $translator->translate('i.quick_actions'); ?></b>
                </div>
                <div class="btn-group btn-group-justified no-margin">
                    <?php
                        echo $modal_create_client;
                    ?>
                    <?php if ($client_count === 0) { ?>
                    <a href="#create-client" class="btn btn-success" data-toggle="modal" disabled data-bs-toggle = "tooltip" title="<?= $translator->translate('i.add_client'); ?>" style="text-decoration:none">
                        <i class="fa fa-plus"></i><?= $translator->translate('i.client'); ?>
                    </a>
                    <?php } else { ?>
                    <a href="<?= $urlGenerator->generate('client/add', ['origin' => 'dashboard']); ?>" class="btn btn-success" style="text-decoration:none">
                        <i class="fa fa-plus"></i><?= $translator->translate('i.client'); ?>
                    </a>
                    <?php } ?>
                    <?php if ($client_count === 0) { ?>
                    <a href="#create-quote" class="btn btn-success" data-toggle="modal" disabled data-bs-toggle = "tooltip" title="<?= $translator->translate('i.add_client'); ?>" style="text-decoration:none">
                        <i class="fa fa-plus"></i><?= $translator->translate('i.quote'); ?>
                    </a>
                    <?php } else { ?>
                    <a href="<?= $urlGenerator->generate('quote/add', ['origin' => 'dashboard']);?>" class="btn btn-success" style="text-decoration:none">
                        <i class="fa fa-plus"></i><?= $translator->translate('i.quote'); ?>
                    </a>
                    <?php } ?>
                    <?php if ($client_count === 0) { ?>
                    <a href="#create-inv" class="btn btn-success" data-toggle="modal" disabled data-bs-toggle = "tooltip" title="<?= $translator->translate('i.add_client'); ?>" style="text-decoration:none">
                        <i class="fa fa-plus"></i><?= $translator->translate('i.invoice'); ?>
                    </a>
                    <?php } else { ?>
                    <a href="<?= $urlGenerator->generate('inv/add', ['origin' => 'dashboard']);?>" class="btn btn-success" data-toggle="modal" style="text-decoration:none">
                        <i class="fa fa-plus"></i><?= $translator->translate('i.invoice'); ?>
                    </a>
                    <?php } ?>
                    <a href="<?= $urlGenerator->generate('payment/add') ; ?>" class="btn btn-default" style="text-decoration:none">
                        <i class="fa fa-credit-card fa-margin"></i>
                        <span class="hidden-xs"><?= $translator->translate('i.enter_payment'); ?></span>
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
            <div id="panel-quote-overview" class="panel panel-default overview">
                <div class="panel-heading">
                    <b><i class="fa fa-bar-chart fa-margin"></i> <?= $translator->translate('i.quote_overview'); ?></b>
                    <span class="pull-right text-muted"><?= $s->lang($quote_status_period); ?></span>
                </div>
                <table class="table table-hover table-bordered table-condensed no-margin">
                    <?php
                        /**
                         * @var array $total
                         */
                        foreach ($quote_status_totals as $total) { ?>
                        <tr>
                             <td>
                                <a href="<?= $urlGenerator->generate('quote/index', ['page'=>1, 'status'=> (int)$total['href']]); ?>">
                                    <?php echo (string)$total['label']; ?>
                                </a>
                            </td>
                            <td class="amount">
                        <span class="<?php echo (string)$total['class']; ?>">
                            <?php echo $s->format_currency($total['sum_total']); ?>
                        </span>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </div>

        </div>
<?php 
    // Invoice Overview 
?>        
        <div class="col-xs-12 col-md-6">

            <div id="panel-invoice-overview" class="panel panel-default overview">

                <div class="panel-heading">
                    <b><i class="fa fa-bar-chart fa-margin"></i> <?= $translator->translate('i.invoice_overview'); ?></b>
                    <span class="pull-right text-muted"><?= $s->lang($invoice_status_period); ?></span>
                </div>

                <table class="table table-hover table-bordered table-condensed no-margin">
                    <?php
                        /**
                         * @var array $total
                         */
                        foreach ($invoice_status_totals as $total) { ?>
                        <tr>
                            <td>
                                <a href="<?= $urlGenerator->generate('inv/index', ['page'=>1, 'status' => (int)$total['href']]); ?>">
                                    <?php echo (string)$total['label']; ?>
                                </a>
                            </td>
                            <td class="amount">
                        <span class="<?php echo (string)$total['class']; ?>">
                            <?= $s->format_currency($total['sum_total']); ?>
                        </span>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
    </div>

    <div class = 'row'>
        <div class="col-xs-12 col-md-6">
            <div id="panel-recent-quotes" class="panel panel-default">
                <div class="panel-heading">
                    <b><i class="fa fa-history fa-margin"></i> <?= $translator->translate('i.recent_quotes'); ?></b>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-condensed no-margin">
                        <thead>
                        <tr>
                            <th><?= $translator->translate('i.status'); ?></th>
                            <th style="min-width: 15%;"><?= $translator->translate('i.date'); ?></th>
                            <th style="min-width: 15%;"><?= $translator->translate('i.quote'); ?></th>
                            <th style="min-width: 35%;"><?= $translator->translate('i.client'); ?></th>
                            <th style="text-align: right;"><?= $translator->translate('i.balance'); ?></th>
                            <th style="text-align: right;"><?= $translator->translate('i.custom_fields'); ?></th>
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
                                <?php if (null!==$statusId = $quote->getStatus_id()) { ?>    
                                    <span class="label label-
                                    <?= $qR->getSpecificStatusArrayClass((string)$statusId); ?>">
                                        <?= $qR->getSpecificStatusArrayLabel((string)$statusId); ?>
                                    </span>
                                <?php } ?>    
                                </td>
                                <td>
                                    <?= $quote->getDate_created()->format($dateHelper->style()); ?>
                                </td>
                                <td>
                                    <a href="<?= $urlGenerator->generate('quote/view', ['id'=>$quote->getId()]); ?>" title="<?=  (($quote->getNumber() ?? '#') ? : ($quote->getId() ?? '#')); ?>" class="btn btn-default" style="text-decoration:none"><?= (($quote->getNumber() ?? '#') ? : ($quote->getId() ?? '#')); ?></a>
                                </td>
                                <td>
                                    <a href="<?= $urlGenerator->generate('client/view', ['id'=>$quote->getClient_id()]); ?>" title="<?=  (($quote->getNumber() ?? '#') ? : ($quote->getId() ?? '#')); ?>" class="btn btn-default" style="text-decoration:none"><?= Html::encode($clientHelper->format_client($quote->getClient())); ?></a>                                   
                                </td>
                                <td class="amount">
<?php $quote_amount = (($qaR->repoQuoteAmountCount((string)$quote->getId()) > 0) ? $qaR->repoQuotequery((string)$quote->getId()) : null) ?>
<?= $s->format_currency(null!==$quote_amount ? $quote_amount->getTotal() : 0.00) ?>                                    
                                </td>
                                <td style="text-align: center;">
                                    <a href="<?= $urlGenerator->generate('quote/pdf_dashboard_include_cf',['id'=>$quote->getId()]); ?>"
                                       title="<?= $translator->translate('i.download_pdf'); ?>" class="btn btn-default" style="text-decoration:none">
                                        <i class="fa fa-file-pdf-o"></i>
                                    </a>
                                </td>
                                <td style="text-align: center;">
                                    <a href="<?= $urlGenerator->generate('quote/pdf_dashboard_exclude_cf',['id'=>$quote->getId()]); ?>"
                                       title="<?= $translator->translate('i.download_pdf'); ?>" class="btn btn-default" style="text-decoration:none">
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

            <div id="panel-recent-invoices" class="panel panel-default">

                <div class="panel-heading">
                    <b><i class="fa fa-history fa-margin"></i> <?= $translator->translate('i.recent_invoices'); ?></b>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped table-condensed no-margin">
                        <thead>
                        <tr>
                            <th><?= $translator->translate('i.status'); ?></th>
                            <th style="min-width: 15%;"><?= $translator->translate('i.due_date'); ?></th>
                            <th style="min-width: 15%;"><?= $translator->translate('i.invoice'); ?></th>
                            <th style="min-width: 35%;"><?= $translator->translate('i.client'); ?></th>
                            <th style="text-align: right;"><?= $translator->translate('i.balance'); ?></th>
                            <th style="text-align: right;"><?= $translator->translate('i.custom_fields'); ?></th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>

                        <?php
                            /**
                             * @var App\Invoice\Entity\Inv $invoice
                             */
                            foreach ($invoices as $invoice) {
                                if ($s->get_setting('disable_read_only') == '1') {
                                    $invoice->setIs_read_only(false);
                                } ?>
                            <tr>
                                <td>
                                    <?php if (null!==($statusId = $invoice->getStatus_id())) { ?>
                                        <span class="label label-<?= $iR->getSpecificStatusArrayClass($statusId); ?>">

                                            <?= $iR->getSpecificStatusArrayLabel((string)$statusId);
                                            if (null!==$iaR->repoCreditInvoicequery((string)$invoice->getId())) { ?>
                                                &nbsp;<i class="fa fa-credit-invoice" title="<?= $translator->translate('i.credit_invoice') ?>"></i>
                                            <?php } ?>

                                            <?php if ($invoice->getIs_read_only()) { ?>
                                                &nbsp;<i class="fa fa-read-only" title="<?= $translator->translate('i.read_only') ?>"></i>
                                            <?php } ?>
                                            <?php if (($irR->repoCount((string)$invoice->getId()) > 0)) { ?>
                                                &nbsp;<i class="fa fa-refresh" title="<? $translator->translate('i.recurring') ?>"></i>
                                            <?php } ?>
                                        </span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <span class="<?= $invoice->isOverdue() ? 'font-overdue' : ''; ?>">
                                        <?= $invoice->getDate_due()->format($dateHelper->style()); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= $urlGenerator->generate('inv/view',['id'=>$invoice->getId()]); ?>" class="btn btn-default" style="text-decoration:none">
                                        <?= ($invoice->getNumber() ?? '#'.($invoice->getId() ?? '#')) ;?>
                                    </a>                
                                </td>
                                <td>
                                    <a href="<?= $urlGenerator->generate('client/view',['id'=>$invoice->getClient_id()]); ?>" class="btn btn-default" style="text-decoration:none">
                                        <?= (Html::encode($clientHelper->format_client($invoice->getClient()))); ?>
                                    </a>
                                </td>
                                <td class="amount">
                                    <?php $inv_amount = (($iaR->repoInvAmountCount((int)$invoice->getId()) > 0) ? $iaR->repoInvquery((int)$invoice->getId()) : null) ?>
                                    <?= $s->format_currency(null!==$inv_amount ? $inv_amount->getBalance() : 0.00) ?> 
                                    <?php //= $s->format_currency($iaR->repoInvQuery((int)$invoice->getId())->getBalance() * $iaR->repoInvQuery((int)$invoice->getId())->getSign()); ?>
                                </td>                               
                                <td style="text-align: center;">
                                    <a href="<?= $urlGenerator->generate('inv/pdf_dashboard_include_cf',['id'=>$invoice->getId()]); ?>"
                                       title="<?= $translator->translate('i.download_pdf'); ?>" class="btn btn-default" style="text-decoration:none">
                                        <i class="fa fa-file-pdf-o"></i>
                                    </a>
                                </td>
                                <td style="text-align: center;">
                                    <a href="<?= $urlGenerator->generate('quote/pdf_dashboard_exclude_cf',['id'=>$invoice->getId()]); ?>"
                                       title="<?= $translator->translate('i.download_pdf'); ?>" class="btn btn-default" style="text-decoration:none">
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
    <?php if ($s->get_setting('projects_enabled') == 1) : ?>
        <div class = 'row'>
            <div class="col-xs-12 col-md-6">

                <div id="panel-projects" class="panel panel-default">

                    <div class="panel-heading">
                        <b><i class="fa fa-list fa-margin"></i> <?= $translator->translate('i.projects'); ?></b>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-condensed no-margin">
                            <thead>
                            <tr>
                                <th><?= $translator->translate('i.project_name'); ?></th>
                                <th><?= $translator->translate('i.client_name'); ?></th>
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
                                        <a href="<?= $urlGenerator->generate('project/view', ['id'=> $project->getId()]); ?>">
                                            <?= Html::encode($project->getName()); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="<?= $urlGenerator->generate('client/view', ['id'=> $project->getClient_id()]); ?>">
                                            <?= Html::encode($clientHelper->format_client($project->getClient())); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                                <tr>
                                    <td colspan="6" class="text-right small">
                                        <a href="<?= $urlGenerator->generate('project/index'); ?>">
                                            <?= $translator->translate('i.view_all'); ?>
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

                <div id="panel-recent-invoices" class="panel panel-default">

                    <div class="panel-heading">
                        <b><i class="fa fa-check-square-o fa-margin"></i> <?= $translator->translate('i.tasks'); ?></b>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-condensed no-margin">

                            <thead>
                            <tr>
                                <th><?= $translator->translate('i.status'); ?></th>
                                <th><?= $translator->translate('i.task_name'); ?></th>
                                <th><?= $translator->translate('i.task_finish_date'); ?></th>
                                <th><?= $translator->translate('i.project'); ?></th>
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
                                    <span class="label <?= $taskR->getSpecificStatusArrayClass($task->getStatus() ?? 1); ?>">
                                        <?= $taskR->getSpecificStatusArrayLabel((string)($task->getStatus() ?? 1)); ?>
                                    </span>
                                    </td>
                                    <td>
                                        <a href="<?= $urlGenerator->generate('task/edit', ['id'=>$task->getId()]); ?>">
                                         <?= Html::encode($task->getName()); ?>   
                                        </a>
                                    </td>
                                    <td>
                                    <span class="<?php if ($task->Is_overdue()) { ?>font-overdue<?php } ?>">
                                        <?= !is_string($taskFinishDate = $task->getFinish_date()) ? $taskFinishDate->format($dateHelper->style()) : ''; ?>
                                    </span>
                                    </td>
                                    <td>
                                        <?php  if (!empty($task->getProject_id())) { ?>
                                            <a href="<?= $urlGenerator->generate('project/view',['id'=>$task->getProject_id()]); ?>"><?= Html::encode($task->getName()); ?></a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                                    <tr>
                                    <td colspan="6" class="text-right small">
                                        <a href="<?= $urlGenerator->generate('task/index'); ?>"><?= Html::encode($translator->translate('i.view_all')); ?></a>                                        
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
