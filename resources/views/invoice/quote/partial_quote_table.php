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
            <th><?= $translator->translate('i.quote'); ?></th>
            <th><?= $translator->translate('i.created'); ?></th>
            <th><?= $translator->translate('i.due_date'); ?></th>
            <th><?= $translator->translate('i.client_name'); ?></th>
            <th style="text-align: right; padding-right: 25px;"><?= $translator->translate('i.amount'); ?></th>
            <th><?= $translator->translate('i.options'); ?></th>
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
                       title="<?= $translator->translate('i.edit'); ?>" style="text-decoration:none">
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
                       title="<?= $translator->translate('i.view_client'); ?>" style="text-decoration:none">
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
                            <i class="fa fa-cog"></i> <?= $translator->translate('i.options'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="<?= $urlGenerator->generate('quote/view', ['_language' =>$session->get('_language'), 'id'=>$quote->getId()]); ?>" style="text-decoration:none">
                                    <i class="fa fa-edit fa-margin"></i> <?= $translator->translate('i.edit'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?= $urlGenerator->generate('quote/pdf', ['_language' =>$session->get('_language'), 'include'=> true, 'quote_id' => $quote->getId() ]); ?>"
                                   target="_blank" style="text-decoration:none">
                                    <i class="fa fa-print fa-margin"></i> <?= $translator->translate('i.download_pdf'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?= $urlGenerator->generate('quote/email_stage_0',['_language' =>$session->get('_language'), 'id'=> $quote->getId()]); ?>" style="text-decoration:none">
                                    <i class="fa fa-send fa-margin"></i> <?= $translator->translate('i.send_email'); ?>
                                </a>
                            </li>
                            <li>
                                <form action="<?= $urlGenerator->generate('quote/delete',['_language' =>$session->get('_language'), 'id'=> $quote->getId()]); ?>" method="POST">
                                    <input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>"> 
                                    <button type="submit" class="dropdown-button"
                                            onclick="return confirm('<?= $translator->translate('i.delete_quote_warning'); ?>');">
                                        <i class="fa fa-trash-o fa-margin"></i> <?= $translator->translate('i.delete'); ?>
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
