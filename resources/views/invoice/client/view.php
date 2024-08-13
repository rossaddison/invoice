<?php

    declare(strict_types=1);
    
    use App\Invoice\ClientCustom\ClientCustomForm;
    use App\Invoice\Entity\ClientCustom;
    use Yiisoft\Html\Html;
    
    /**
     * @var App\Invoice\ClientPeppol\ClientPeppolRepository $cpR
     * @var App\Invoice\ClientCustom\ClientCustomForm $clientCustomForm
     * @var App\Invoice\Entity\Client $client
     * @var App\Invoice\Helpers\ClientHelper $clientHelper
     * @var App\Invoice\Helpers\CustomValuesHelper $cvH
     * @var App\Invoice\Helpers\DateHelper $dateHelper 
     * @var App\Invoice\Inv\InvRepository $iR
     * @var App\Invoice\InvAmount\InvAmountRepository $iaR
     * @var App\Invoice\Setting\SettingRepository $s
     * @var Yiisoft\Translator\TranslatorInterface $translator
     * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
     * @var array $clientCustomValues
     * @var array $customValues
     * @var array $custom_fields
     * @var string $alert
     * @var string $partial_client_address
     * @var string $client_modal_layout_inv
     * @var string $client_modal_layout_quote
     * @var string $delivery_locations
     * @var string $quote_table
     * @var string $quote_draft_table
     * @var string $quote_sent_table
     * @var string $quote_viewed_table
     * @var string $quote_approved_table
     * @var string $quote_rejected_table
     * @var string $quote_cancelled_table
     * @var string $invoice_table
     * @var string $invoice_draft_table
     * @var string $invoice_sent_table
     * @var string $invoice_viewed_table
     * @var string $invoice_paid_table
     * @var string $invoice_overdue_table
     * @var string $invoice_unpaid_table
     * @var string $invoice_reminder_sent_table
     * @var string $invoice_seven_day_table
     * @var string $invoice_legal_claim_table
     * @var string $invoice_judgement_table
     * @var string $invoice_officer_table
     * @var string $invoice_credit_table
     * @var string $invoice_written_off_table 
     * @var string $payment_table   
     * @var string $partial_notes
     * @var string $title
     */

    $locations = [];
    
    /**
     * @var App\Invoice\Entity\CustomField $custom_field
     */    
    foreach ($custom_fields as $custom_field) {
        $customFieldLocation = $custom_field->getLocation();
        if (null!==$customFieldLocation) {
            if (array_key_exists($customFieldLocation, $locations)) {
                $locations[$customFieldLocation] += 1;
            } else {
                $locations[$customFieldLocation] = 1;
            }
        }
    }
?>

<h1><?= Html::encode($title)?></h1>

<div id="headerbar">
    <h1 class="headerbar-title"><?= Html::encode($clientHelper->format_client($client)); ?></h1>

    <div class="headerbar-item pull-right">
        <div class="btn-group btn-group-sm">
                <a href="#modal-add-quote" data-bs-toggle="modal" class="btn btn-success" style="text-decoration:none">
                    <i class="fa fa-file-text"></i><?= $translator->translate('i.create_quote'); ?>
                </a>
                <a href="#modal-add-inv" data-bs-toggle="modal" class="btn btn-success"  style="text-decoration:none">
                   <i class="fa fa-file-text"></i><?= $translator->translate('i.create_invoice'); ?>
                </a>
                <?php if ($cpR->repoClientCount($clientId = (string)$client->getClient_id()) === 0 && strlen($clientId) > 0) { ?>
                <a href="<?= $urlGenerator->generate('clientpeppol/add', ['client_id' => $client->getClient_id()]); ?>" 
                   class="btn btn-info" style="text-decoration:none">
                     <i class="fa fa-plus"></i> <?= $translator->translate('invoice.client.peppol.add'); ?>
                </a>
                <?php } ?>
                <?php if ($cpR->repoClientCount($clientId = (string)$client->getClient_id()) > 0 && strlen($clientId) > 0) { ?>
                <a href="<?= $urlGenerator->generate('clientpeppol/edit', ['client_id' => $client->getClient_id()]); ?>" 
                   class="btn btn-warning" style="text-decoration:none">
                     <i class="fa fa-edit"></i> <?= $translator->translate('invoice.client.peppol.edit'); ?>
                </a>
                <?php } ?>
                <a href="<?= null!==($clientIdEdit = $client->getClient_id()) ? $urlGenerator->generate('client/edit', ['id' => $clientIdEdit, 'origin' => 'edit']) : ''; ?>"
                   class="btn btn-danger" style="text-decoration:none">
                    <i class="fa fa-edit"></i><?= $translator->translate('i.edit'); ?>
                </a>
                <a href="<?= null!==($clientIdPostalAdd = $client->getClient_id()) ? $urlGenerator->generate('postaladdress/add', ['client_id' => $clientIdPostalAdd]) : ''; ?>"
                   class="btn btn-primary" style="text-decoration:none">
                    <i class="fa fa-plus"></i><?= $translator->translate('invoice.client.postaladdress.add'); ?>
                </a>
                <a href="<?= null!==($clientIdDelAdd = $client->getClient_id()) ? $urlGenerator->generate('del/add',['client_id' => $clientIdDelAdd], ['origin' => 'client', 'origin_id' => $clientIdDelAdd, 'action' => 'view']) : ''; ?>"
                   class="btn btn-success" style="text-decoration:none">
                   <i class="fa fa-plus fa-margin"></i><?= $translator->translate('invoice.invoice.delivery.location.add'); ?>
                </a>
                <a class="btn btn-danger"
                   href="<?= $urlGenerator->generate('client/delete', ['id'=>$client->getClient_id()]); ?>"
                   onclick="return confirm('<?= $translator->translate('i.delete_client_warning'); ?>');" style="text-decoration:none">
                   <i class="fa fa-trash-o fa-margin"></i> <?= $translator->translate('i.delete'); ?>
                </a>
        </div>
    </div>

</div>

<ul id="submenu" class="nav nav-tabs nav-tabs-noborder">
    <!== https://getbootstrap.com/docs/5.0/components/navs-tabs/#using-data-attributes -->
    <li class="nav-item" role="presentation"><button class="nav-link active" id="client-details-tab" data-bs-toggle="tab" data-bs-target="#clientDetails"  style="text-decoration:none"><?= $translator->translate('i.details'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-quotes-tab" data-bs-toggle="tab" data-bs-target="#clientQuotes" style="text-decoration:none;background-color: lightgreen"><?= $translator->translate('i.quotes'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-quotes-draft-tab" data-bs-toggle="tab" data-bs-target="#clientQuotesDraft" style="text-decoration:none"><?= $translator->translate('i.draft'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-quotes-sent-tab" data-bs-toggle="tab" data-bs-target="#clientQuotesSent" style="text-decoration:none"><?= $translator->translate('i.sent'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-quotes-viewed-tab" data-bs-toggle="tab" data-bs-target="#clientQuotesViewed" style="text-decoration:none"><?= $translator->translate('i.viewed'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-quotes-approved-tab" data-bs-toggle="tab" data-bs-target="#clientQuotesApproved" style="text-decoration:none"><?= $translator->translate('i.approved'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-quotes-cancelled-tab" data-bs-toggle="tab" data-bs-target="#clientQuotesCancelled" style="text-decoration:none"><?= $translator->translate('i.canceled'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-quotes-rejected-tab" data-bs-toggle="tab" data-bs-target="#clientQuotesRejected" style="text-decoration:none"><?= $translator->translate('i.rejected'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-invoices-tab" data-bs-toggle="tab" data-bs-target="#clientInvoices" style="text-decoration:none;background-color: lightpink"><?= $translator->translate('i.invoices'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-invoices-draft-tab" data-bs-toggle="tab" data-bs-target="#clientInvoicesDraft" style="text-decoration:none"><?= $translator->translate('i.draft'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-invoices-sent-tab" data-bs-toggle="tab" data-bs-target="#clientInvoicesSent" style="text-decoration:none"><?= $translator->translate('i.sent'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-invoices-viewed-tab" data-bs-toggle="tab" data-bs-target="#clientInvoicesViewed" style="text-decoration:none"><?= $translator->translate('i.viewed'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-invoices-paid-tab" data-bs-toggle="tab" data-bs-target="#clientInvoicesPaid" style="text-decoration:none"><?= $translator->translate('i.paid'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-invoices-overdue-tab" data-bs-toggle="tab" data-bs-target="#clientInvoicesOverdue" style="text-decoration:none"><?= $translator->translate('i.overdue'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-invoices-unpaid-tab" data-bs-toggle="tab" data-bs-target="#clientInvoicesUnpaid" style="text-decoration:none"><?= $translator->translate('i.unpaid'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-invoices-reminder-tab" data-bs-toggle="tab" data-bs-target="#clientInvoicesReminderSent" style="text-decoration:none"><?= $translator->translate('i.reminder'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-invoices-seven-day-tab" data-bs-toggle="tab" data-bs-target="#clientInvoicesSevenDay" style="text-decoration:none"><?= $translator->translate('i.letter'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-invoices-legal-claim-tab" data-bs-toggle="tab" data-bs-target="#clientInvoicesLegalClaim" style="text-decoration:none"><?= $translator->translate('i.claim'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-invoices-judgement-tab" data-bs-toggle="tab" data-bs-target="#clientInvoicesJudgement" style="text-decoration:none"><?= $translator->translate('i.judgement'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-invoices-officer-tab" data-bs-toggle="tab" data-bs-target="#clientInvoicesOfficer" style="text-decoration:none"><?= $translator->translate('i.enforcement'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-invoices-credit-tab" data-bs-toggle="tab" data-bs-target="#clientInvoicesCredit" style="text-decoration:none"><?= $translator->translate('i.credit_invoice_for_invoice'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-invoices-written-off-tab" data-bs-toggle="tab" data-bs-target="#clientInvoicesWrittenOff" style="text-decoration:none"><?= $translator->translate('i.loss'); ?></button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="client-payments-tab" data-bs-toggle="tab" data-bs-target="#clientPayments" style="text-decoration:none;background-color: lightblue"><?= $translator->translate('i.payments'); ?></button></li>
</ul>

<div id="content" class="tabbable tabs-below no-padding">
    <div class="tab-content no-padding">

        <div id="clientDetails" class="tab-pane tab-rich-content active">

            <?= $alert; ?>

            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-6">

                    <h3><?= Html::encode($clientHelper->format_client($client)); ?></h3>
                    <p>
                        <?= $partial_client_address; ?>
                    </p>

                </div>
                <div class="col-xs-12 col-sm-6 col-md-6">

                    <table class="table table-bordered no-margin">
                        <tr>
                            <th>
                                <?= $translator->translate('i.language'); ?>
                            </th>
                            <td class="td-amount">
                                <?= ucfirst($client->getClient_language() ?? ''); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <?= $translator->translate('i.total_billed'); ?>
                            </th>
                            <td class="td-amount">
                                <?= null!==($clientIdTotal = $client->getClient_id()) ? $s->format_currency($iR->with_total($clientIdTotal, $iaR)) : ''; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <?= $translator->translate('i.total_paid'); ?>
                            </th>
                            <th class="td-amount">
                                <?= null!==($clientIdPaid = $client->getClient_id()) ? $s->format_currency($iR->with_total_paid($clientIdPaid, $iaR)) : ''; ?>
                            </th>
                        </tr>
                        <tr>
                            <th>
                                <?= $translator->translate('i.total_balance'); ?>
                            </th>
                            <td class="td-amount">
                                <?= null!==($clientIdBalance = $client->getClient_id()) ? $s->format_currency($iR->with_total_balance($clientIdBalance, $iaR)) : ''; ?>
                            </td>
                        </tr>
                    </table>

                </div>
            </div>

            <hr>
            
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="panel panel-default no-margin">
                        <div class="panel-heading"><?= $translator->translate('invoice.invoice.delivery.location.client'); ?></div>
                            <div class="panel-body table-content">
                                <?php echo $delivery_locations; ?>
                            </div>
                    </div>
                </div>
            </div>  
            
            <hr>

            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <div class="panel panel-default no-margin">
                        <div class="panel-heading"><?= $translator->translate('i.contact_information'); ?></div>
                        <div class="panel-body table-content">
                            <table class="table no-margin">
                                <?php if ($client->getClient_email()) : ?>
                                    <tr>
                                        <th><?= $translator->translate('i.email'); ?></th>
                                        <td><?= Html::mailto($client->getClient_email()); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (strlen(($client->getClient_phone() ?? '')) > 0) : ?>
                                    <tr>
                                        <th><?= $translator->translate('i.phone'); ?></th>
                                        <td><?= Html::encode($client->getClient_phone()); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (strlen(($client->getClient_mobile() ?? '')) > 0) : ?>
                                    <tr>
                                        <th><?= $translator->translate('i.mobile'); ?></th>
                                        <td><?= Html::encode($client->getClient_mobile()); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (strlen(($client->getClient_fax() ?? '')) > 0) : ?>
                                    <tr>
                                        <th><?= $translator->translate('i.fax'); ?></th>
                                        <td><?= Html::encode($client->getClient_fax()); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (strlen(($client->getClient_web() ?? '')) > 0) : ?>
                                    <tr>
                                        <th><?= $translator->translate('i.web'); ?></th>
                                        <td><?= Html::link($client->getClient_web()); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\CustomField $custom_field
                                     */ 
                                    foreach ($custom_fields as $custom_field) : ?>
                                    <?php if ($custom_field->getLocation() !==2) {
                                        continue;
                                    } ?>
                                    <tr>
                                        <?php
                                            $clientCustomForm = new App\Invoice\ClientCustom\ClientCustomForm(new App\Invoice\Entity\ClientCustom);
                                            $cvH->print_field_for_view($custom_field, $clientCustomForm, $clientCustomValues, $customValues);
                                        ?>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>

                </div>
                <div class="col-xs-12 col-md-6">
                    <div class="panel panel-default no-margin">

                        <div class="panel-heading"><?= $translator->translate('i.tax_information'); ?></div>
                        <div class="panel-body table-content">
                            <table class="table no-margin">
                                <?php if ($client->getClient_vat_id()) : ?>
                                    <tr>
                                        <th><?= $translator->translate('i.vat_id'); ?></th>
                                        <td><?= Html::encode($client->getClient_vat_id()); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (strlen(($clientTaxCode = $client->getClient_tax_code() ?? '')) > 0) : ?>
                                    <tr>
                                        <th><?= $translator->translate('i.tax_code'); ?></th>
                                        <td><?= Html::encode($clientTaxCode); ?></td>
                                    </tr>
                                <?php endif; ?>

                                <?php 
                                    /**
                                     * @var App\Invoice\Entity\CustomField $custom_field
                                     */
                                    foreach ($custom_fields as $custom_field) : ?>
                                    <?php if ($custom_field->getLocation() != 4) {
                                        continue;
                                    } ?>
                                    <tr>
                                        <?php
                                            $column = $custom_field->getLabel();                                        
                                            $value = $cvH->form_value($clientCustomValues, $custom_field->getId())
                                        ?>
                                        <th><?= Html::encode($column); ?></th>
                                        <td><?= Html::encode($value); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>

        </div>
    </div>
</div>

            <?php if ($client->getClient_surname() !== ""): ?>
                <hr>

                <div class="row">
                    <div class="col-xs-12 col-md-6">

                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <?= $translator->translate('i.personal_information'); ?>
                            </div>

                            <div class="panel-body table-content">
                                <table class="table no-margin">                                     
                                    <tr>
                                        <th><?= $translator->translate('i.birthdate'); ?></th>
                                        
                                        <td><?= 
                                              !is_string($clientBirthdate = $client->getClient_birthdate()) 
                                               && null!==$clientBirthdate ? 
                                                         $clientBirthdate->format($dateHelper->style()) : '';
                                            ?></td>
                                    </tr>
                                    <tr>
                                        <th><?= $translator->translate('i.gender'); ?></th>
                                        <td><?= null!==($clientGender = $client->getClient_gender()) ? 
                                                $clientHelper->format_gender($clientGender, $translator) : ''; ?></td>
                                    </tr>
                                    <?php if ($s->get_setting('sumex') == '1'): ?>
                                        <tr>
                                            <th><?= $translator->translate('i.sumex_ssn'); ?></th>
                                            <td><?= null!==($clientAvs = $client->getClient_avs()) ? $cvH->format_avs($clientAvs) : ''; ?></td>
                                        </tr>
                                        <tr>
                                            <th><?= $translator->translate('i.sumex_insurednumber'); ?></th>
                                            <td><?= Html::encode($client->getClient_insurednumber()) ?></td>
                                        </tr>
                                        <tr>
                                            <th><?= $translator->translate('i.sumex_veka'); ?></th>
                                            <td><?= Html::encode($client->getClient_veka()) ?></td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php
                                        /**
                                         * @var App\Invoice\Entity\CustomField $custom_field
                                         */
                                        foreach ($custom_fields as $custom_field) : ?>
                                        <?php if ($custom_field->getLocation() != 3) {
                                            continue;
                                        } ?>
                                        <tr>
                                            <?php
                                                $column = $custom_field->getLabel();
                                                $value = $cvH->form_value($clientCustomValues, $custom_field->getId())
                                            ?>
                                            <th><?= Html::encode($column); ?></th>
                                            <td><?= Html::encode($value); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endif; ?>

            <?php
            if ($custom_fields) : ?>
                <hr>

                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="panel panel-default no-margin">

                            <div class="panel-heading">
                                <?= $translator->translate('i.custom_fields'); ?>
                            </div>
                            <div class="panel-body table-content">
                                <table class="table no-margin">
                                    <?php
                                       /**
                                         * @var App\Invoice\Entity\CustomField $custom_field
                                         */
                                       foreach ($custom_fields as $custom_field) : ?>
                                        <?php if ($custom_field->getLocation() !== 0) {
                                            continue;
                                        } ?>
                                        <tr>
                                            <?php
                                            $clientCustomForm = new ClientCustomForm(new ClientCustom);
                                            $cvH->print_field_for_view($custom_field, $clientCustomForm, $clientCustomValues, $customValues);?>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <hr>

            <div class="row">
                <div class="col-xs-12 col-md-6">

                    <div class="panel panel-default no-margin">
                        <div class="panel-heading">
                            <?= $translator->translate('i.notes'); ?>
                        </div>
                        <div class="panel-body">
                            <div id="notes_list">
                                <?php echo $partial_notes; ?>
                            </div>
                            <input type="hidden" name="client_id" id="client_id"
                                   value="<?= $client->getClient_id(); ?>">
                            <div class="input-group">
                                <textarea id="client_note" class="form-control" rows="2" style="resize:none"></textarea>
                                <span id="save_client_note_new" class="input-text-addon btn btn-info">
                                    <?= $translator->translate('i.add_note'); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <div id="clientQuotes" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-quotes-tab">
            <?php echo $quote_table; ?>
        </div>
        
        <div id="clientQuotesDraft" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-quotes-draft-tab">
            <?php echo $quote_draft_table; ?>
        </div>
        
        <div id="clientQuotesSent" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-quotes-sent-tab">
            <?php echo $quote_sent_table; ?>
        </div>
        
        <div id="clientQuotesViewed" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-quotes-viewed-tab">
            <?php echo $quote_viewed_table; ?>
        </div>
        
        <div id="clientQuotesApproved" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-quotes-approved-tab">
            <?php echo $quote_approved_table; ?>
        </div>
        
        <div id="clientQuotesCancelled" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-quotes-cancelled-tab">
            <?php echo $quote_cancelled_table; ?>
        </div>
        
        <div id="clientQuotesRejected" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-quotes-rejected-tab">
            <?php echo $quote_rejected_table; ?>
        </div>
        
        <div id="clientInvoices" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-invoices-tab">
            <?php echo $invoice_table; ?>
        </div>
        
        <div id="clientInvoicesDraft" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-invoices-draft-tab">
            <?php echo $invoice_draft_table; ?>
        </div>
        
        <div id="clientInvoicesSent" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-invoices-sent-tab">
            <?php echo $invoice_sent_table; ?>
        </div>
        
        <div id="clientInvoicesViewed" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-invoices-viewed-tab">
            <?php echo $invoice_viewed_table; ?>
        </div>
        
        <div id="clientInvoicesPaid" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-invoices-paid-tab">
            <?php echo $invoice_paid_table; ?>
        </div>
        
        <div id="clientInvoicesOverdue" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-invoices-overdue-tab">
            <?php echo $invoice_overdue_table; ?>
        </div>
        
        <div id="clientInvoicesUnpaid" class="tab-pane table-content role="tabpanel" aria-labelledby="client-invoices-unpaid-tab"">
            <?php echo $invoice_unpaid_table; ?>
        </div>
        
        <div id="clientInvoicesReminderSent" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-invoices-reminder-sent-tab">
            <?php echo $invoice_reminder_sent_table; ?>
        </div>
        
        <div id="clientInvoicesSevenDay" class="tab-pane table-content role="tabpanel" aria-labelledby="client-invoices-seven-day-tab"">
            <?php echo $invoice_seven_day_table; ?>
        </div>
        
        <div id="clientInvoicesLegalClaim" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-invoices-legal-claim-tab">
            <?php echo $invoice_legal_claim_table; ?>
        </div>
        
        <div id="clientInvoicesJudgement" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-invoices-judgement-tab">
            <?php echo $invoice_judgement_table; ?>
        </div>
        
        <div id="clientInvoicesOfficer" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-invoices-officer-tab">
            <?php echo $invoice_officer_table; ?>
        </div>
        
        <div id="clientInvoicesCredit" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-invoices-credit-tab">
            <?php echo $invoice_credit_table; ?>
        </div>
        
        <div id="clientInvoicesWrittenOff" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-invoices-written-off-tab">
            <?php echo $invoice_written_off_table; ?>
        </div>
        
        <div id="clientPayments" class="tab-pane table-content" role="tabpanel" aria-labelledby="client-payments-tab">
            <?php echo $payment_table; ?>
        </div>
    </div>

</div>

<?php
    /**
     * Note: The quote modal is used in 3 places
     * Note: {origin} is set in QuoteController/index function ...
     *      'action' => ['quote/add', ['origin' => 'quote']],
     * Note: {origin} is set in resources/views/layout/invoice.php  ... 
     *      $urlGenerator->generate('quote/add',['origin' => 'main'])], 
     * Note: {origin} is set in ClientController/index function ... 
     *      'action' => ['quote/add', ['origin' => $client_id]], 
     * @see config/common/routes quote/add/{origin}
     * @see ClientController/view function 'client_modal_layout_quote' => [ .... ]
     * @see views\invoice\quote\modal_layout.php
     * @see views\invoice\quote\modal_add_quote_form.php contained in above file.
     * Note: 'action' is equivalent to $urlGenerator->generate('quote/add', ['origin' => $client->getClient_id() or 'quote' or 'main'])
     * Note: If origin is a client number, quote/add/{origin} route will return to url client/view/{origin}
     * Note: If origin is 'quote', quote/add/{origin} route will return to url quote/index
     * Note: If origin is 'main', quote/add/{origin} route will return to url invoice/
     */
    echo $client_modal_layout_quote;
    echo $client_modal_layout_inv;
?>