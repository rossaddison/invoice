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
            <th><?= $s->trans('status'); ?></th>
            <th><?= $s->trans('quote'); ?></th>
            <th><?= $s->trans('created'); ?></th>
            <th><?= $s->trans('due_date'); ?></th>
            <th><?= $s->trans('client_name'); ?></th>
            <th style="text-align: right; padding-right: 25px;"><?= $s->trans('amount'); ?></th>
            <th><?= $s->trans('options'); ?></th>
        </tr>
        </thead>

        <tbody>
        <?php
        $quote_idx = 1;
        $quote_list_split = $quote_count > 3 ? $quote_count / 2 : 9999;

        foreach ($quotes as $quote) {
            // Convert the dropdown menu to a dropup if quote is after the invoice split
            $dropup = $quote_idx > $quote_list_split ? true : false;
            ?>
            <tr>
                <td>
                    <span class="label <?= $quote_statuses[$quote->getStatus_id()]['class']; ?>">
                        <?= $quote_statuses[$quote->getStatus_id()]['label']; ?>
                    </span>
                </td>
                <td>
                    <a href="<?= $urlGenerator->generate('quote/view', ['_language' =>$session->get('_language'), 'id' =>$quote->getId()]); ?>"
                       title="<?= $s->trans('edit'); ?>" style="text-decoration:none">
                        <?=($quote->getNumber() ? $quote->getNumber() : $quote->getId()); ?>
                    </a>
                </td>
                <td>
                    <?= $datehelper->date_from_mysql($quote->getDate_created()); ?>
                </td>
                <td>
                    <?= $datehelper->date_from_mysql($quote->getDate_expires()); ?>
                </td>
                <td>
                    <a href="<?= $urlGenerator->generate('client/view', ['_language' =>$session->get('_language'), 'id'=>$quote->getClient_id()]); ?>"
                       title="<?= $s->trans('view_client'); ?>" style="text-decoration:none">
                        <?= Html::encode($clienthelper->format_client($quote->getClient())); ?>
                    </a>
                </td>
                <td style="text-align: right; padding-right: 25px;">
                    <?php $quote_amount = (($qaR->repoQuoteAmountCount((string)$quote->getId()) > 0) ? $qaR->repoQuotequery((string)$quote->getId()) : null) ?>
                    <?= $s->format_currency(null!==$quote_amount ? $quote_amount->getTotal() : 0.00) ?>
                </td>
                <td>
                    <div class="options btn-group<?= $dropup ? ' dropup' : ''; ?>">
                        <a class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown"
                           href="#" style="text-decoration:none">
                            <i class="fa fa-cog"></i> <?= $s->trans('options'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="<?= $urlGenerator->generate('quote/view', ['_language' =>$session->get('_language'), 'id'=>$quote->getId()]); ?>" style="text-decoration:none">
                                    <i class="fa fa-edit fa-margin"></i> <?= $s->trans('edit'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?= $urlGenerator->generate('quote/pdf', ['_language' =>$session->get('_language'), 'include'=> true]); ?>"
                                   target="_blank" style="text-decoration:none">
                                    <i class="fa fa-print fa-margin"></i> <?= $s->trans('download_pdf'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?= $urlGenerator->generate('quote/email_stage_0',['_language' =>$session->get('_language'), 'id'=> $quote->getId()]); ?>" style="text-decoration:none">
                                    <i class="fa fa-send fa-margin"></i> <?= $s->trans('send_email'); ?>
                                </a>
                            </li>
                            <li>
                                <form action="<?= $urlGenerator->generate('quote/delete',['_language' =>$session->get('_language'), 'id'=> $quote->getId()]); ?>" method="POST">
                                    <input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>"> 
                                    <button type="submit" class="dropdown-button"
                                            onclick="return confirm('<?= $s->trans('delete_quote_warning'); ?>');">
                                        <i class="fa fa-trash-o fa-margin"></i> <?= $s->trans('delete'); ?>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
            <?php
            $quote_idx++;
        } ?>
        </tbody>
    </table>
</div>
