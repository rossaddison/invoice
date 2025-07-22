<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/*
 * Related logic: see App\Invoice\Client\ClientController function view
 *
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 * @var App\Invoice\Quote\QuoteRepository $qR
 * @var App\Invoice\QuoteAmount\QuoteAmountRepository $qaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Session\SessionInterface $session
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $quotes
 * @var int $quote_count
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
            <th><?php echo $translator->translate('status'); ?></th>
            <th><?php echo $translator->translate('quote'); ?></th>
            <th><?php echo $translator->translate('created'); ?></th>
            <th><?php echo $translator->translate('due.date'); ?></th>
            <th><?php echo $translator->translate('client.name'); ?></th>
            <th style="text-align: right; padding-right: 25px;"><?php echo $translator->translate('amount'); ?></th>
            <th><?php echo $translator->translate('options'); ?></th>
        </tr>
        </thead>

        <tbody>
        <?php
        $quote_idx = 1;
$quote_list_split  = $quote_count > 3 ? $quote_count / 2 : 9999;

/**
 * @var App\Invoice\Entity\Quote $quote
 */
foreach ($quotes as $quote) {
    // Convert the dropdown menu to a dropup if quote is after the invoice split
    $dropup                    = $quote_idx > $quote_list_split ? true : false;
    $actionDeleteArguments     = ['_language' => (string) $session->get('_language'), 'id' => $quote->getId()];
    $actionEmailArguments      = ['_language' => (string) $session->get('_language'), 'id' => $quote->getId()];
    $actionPdfArguments        = ['_language' => (string) $session->get('_language'), 'include' => true, 'quote_id' => $quote->getId()];
    $actionClientViewArguments = ['_language' => (string) $session->get('_language'), 'id' => $quote->getClient_id()];
    $actionViewArguments       = ['_language' => (string) $session->get('_language'), 'id' => $quote->getId()];
    ?>
            <tr>
                <td>
                    <span class="label <?php echo $qR->getSpecificStatusArrayClass((string) $quote->getStatus_id()); ?>">
                        <?php echo $qR->getSpecificStatusArrayLabel((string) $quote->getStatus_id()); ?>
                    </span>
                </td>
                <td>
                    <a href="<?php echo $urlGenerator->generate('quote/view', $actionViewArguments); ?>"
                       title="<?php echo $translator->translate('edit'); ?>" style="text-decoration:none">
                        <?php echo null !== ($quote->getNumber()) ? $quote->getNumber() : $quote->getId(); ?>
                    </a>
                </td>
                <td>
                    <?php echo $quote->getDate_created()->format('Y-m-d'); ?>
                </td>
                <td>
                    <?php echo $quote->getDate_expires()->format('Y-m-d'); ?>
                </td>
                <td>
                    <a href="<?php echo $urlGenerator->generate('client/view', $actionClientViewArguments); ?>"
                       title="<?php echo $translator->translate('view.client'); ?>" style="text-decoration:none">
                        <?php echo Html::encode($clientHelper->format_client($quote->getClient())); ?>
                    </a>
                </td>
                <td style="text-align: right; padding-right: 25px;">
                    <?php $quote_amount = (($qaR->repoQuoteAmountCount((string) $quote->getId()) > 0) ? $qaR->repoQuotequery((string) $quote->getId()) : null); ?>
                    <?php echo $s->format_currency(null !== $quote_amount ? $quote_amount->getTotal() : 0.00); ?>
                </td>
                <td>
                    <div class="options btn-group<?php echo $dropup ? ' dropup' : ''; ?>">
                        <a class="btn btn-sm btn-default dropdown-toggle" data-bs-toggle="dropdown"
                           href="#" style="text-decoration:none">
                            <i class="fa fa-cog"></i> <?php echo $translator->translate('options'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="<?php echo $urlGenerator->generate('quote/view', $actionViewArguments); ?>" style="text-decoration:none">
                                    <i class="fa fa-edit fa-margin"></i> <?php echo $translator->translate('edit'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $urlGenerator->generate('quote/pdf', $actionPdfArguments); ?>"
                                   target="_blank" style="text-decoration:none">
                                    <i class="fa fa-print fa-margin"></i> <?php echo $translator->translate('download.pdf'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $urlGenerator->generate('quote/email_stage_0', $actionEmailArguments); ?>" style="text-decoration:none">
                                    <i class="fa fa-send fa-margin"></i> <?php echo $translator->translate('send.email'); ?>
                                </a>
                            </li>
                            <li>
                                <form action="<?php echo $urlGenerator->generate('quote/delete', $actionDeleteArguments); ?>" method="POST">
                                    <input type="hidden" id="_csrf" name="_csrf" value="<?php echo $csrf; ?>"> 
                                    <button type="submit" class="dropdown-button"
                                            onclick="return confirm('<?php echo $translator->translate('delete.quote.warning'); ?>');">
                                        <i class="fa fa-trash-o fa-margin"></i> <?php echo $translator->translate('delete'); ?>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
            <?php
    ++$quote_idx;
} ?>
        </tbody>
    </table>
</div>
