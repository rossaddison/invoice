<?php
    declare(strict_types=1);

    /**
     * @var \Yiisoft\View\WebView $this
     * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
     * @var string $csrf
     * @var string $title 
     */
    
    use Yiisoft\Html\Html;

    $this->setTitle($clienthelper->format_client($client));

    $locations = [];
    foreach ($custom_fields as $custom_field) {
        if (array_key_exists($custom_field->getLocation(), $locations)) {
            $locations[$custom_field->getLocation()] += 1;
        } else {
            $locations[$custom_field->getLocation()] = 1;
        }
    }
?>

<h1><?= Html::encode($title)?></h1>

<div id="headerbar">
    <h1 class="headerbar-title"><?= Html::encode($this->getTitle()); ?></h1>

    <div class="headerbar-item pull-right">
        <div class="btn-group btn-group-sm">
                <a href="#modal-add-quote" data-toggle="modal" class="btn btn-success" style="text-decoration:none">
                    <i class="fa fa-file-text"></i><?= $translator->translate('i.create_quote'); ?>
                </a>
                <a href="#modal-add-inv" data-toggle="modal" class="btn btn-success"  style="text-decoration:none">
                   <i class="fa fa-file-text"></i><?= $translator->translate('i.create_invoice'); ?>
                </a>
                <?php if ($cpR->repoClientCount((string)$client->getClient_id()) === 0 ) { ?>
                <a href="<?= $urlGenerator->generate('clientpeppol/add', ['client_id' => $client->getClient_id()]); ?>" 
                   class="btn btn-info" style="text-decoration:none">
                     <i class="fa fa-plus"></i> <?= $translator->translate('invoice.client.peppol.add'); ?>
                </a>
                <?php } ?>
                <?php if ($cpR->repoClientCount((string)$client->getClient_id()) > 0 ) { ?>
                <a href="<?= $urlGenerator->generate('clientpeppol/edit', ['client_id' => $client->getClient_id()]); ?>" 
                   class="btn btn-warning" style="text-decoration:none">
                     <i class="fa fa-edit"></i> <?= $translator->translate('invoice.client.peppol.edit'); ?>
                </a>
                <?php } ?>
                <a href="<?= $urlGenerator->generate('client/edit', ['id' => $client->getClient_id()]); ?>"
                   class="btn btn-danger" style="text-decoration:none">
                    <i class="fa fa-edit"></i><?= $translator->translate('i.edit'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('postaladdress/add', ['client_id' => $client->getClient_id()]); ?>"
                   class="btn btn-primary" style="text-decoration:none">
                    <i class="fa fa-plus"></i><?= $translator->translate('invoice.client.postaladdress.add'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('del/add',['client_id' => $client->getClient_id(), 'origin' => 'client', 'origin_id' => $client->getClient_id(), 'action' => 'view']); ?>"
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
    <li class="active">
        <a data-toggle="tab" href="#clientDetails"  style="text-decoration:none"><?= $translator->translate('i.details'); ?></a>
    </li>
    <li><a data-toggle="tab" href="#clientQuotes" style="text-decoration:none;background-color: lightgreen"><?= $translator->translate('i.quotes'); ?></a></li>
    <li><a data-toggle="tab" href="#clientQuotesDraft" style="text-decoration:none"><?= $translator->translate('i.draft'); ?></a></li>
    <li><a data-toggle="tab" href="#clientQuotesSent" style="text-decoration:none"><?= $translator->translate('i.sent'); ?></a></li>
    <li><a data-toggle="tab" href="#clientQuotesViewed" style="text-decoration:none"><?= $translator->translate('i.viewed'); ?></a></li>
    <li><a data-toggle="tab" href="#clientQuotesApproved" style="text-decoration:none"><?= $translator->translate('i.approved'); ?></a></li>
    <li><a data-toggle="tab" href="#clientQuotesCancelled" style="text-decoration:none"><?= $translator->translate('i.canceled'); ?></a></li>
    <li><a data-toggle="tab" href="#clientQuotesRejected" style="text-decoration:none"><?= $translator->translate('i.rejected'); ?></a></li>
    <li><a data-toggle="tab" href="#clientInvoices" style="text-decoration:none;background-color: lightpink"><?= $translator->translate('i.invoices'); ?></a></li>
    <li><a data-toggle="tab" href="#clientInvoicesDraft" style="text-decoration:none"><?= $translator->translate('i.draft'); ?></a></li>
    <li><a data-toggle="tab" href="#clientInvoicesSent" style="text-decoration:none"><?= $translator->translate('i.sent'); ?></a></li>
    <li><a data-toggle="tab" href="#clientInvoicesViewed" style="text-decoration:none"><?= $translator->translate('i.viewed'); ?></a></li>
    <li><a data-toggle="tab" href="#clientInvoicesPaid" style="text-decoration:none"><?= $translator->translate('i.paid'); ?></a></li>
    <li><a data-toggle="tab" href="#clientPayments" style="text-decoration:none;background-color: lightblue"><?= $translator->translate('i.payments'); ?></a></li>
</ul>

<div id="content" class="tabbable tabs-below no-padding">
    <div class="tab-content no-padding">

        <div id="clientDetails" class="tab-pane tab-rich-content active">

            <?= $alert; ?>

            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-6">

                    <h3><?= Html::encode($clienthelper->format_client($client)); ?></h3>
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
                                <?= ucfirst($client->getClient_language()); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <?= $translator->translate('i.total_billed'); ?>
                            </th>
                            <td class="td-amount">
                                <?= $s->format_currency($iR->with_total($client->getClient_id(), $iaR)); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <?= $translator->translate('i.total_paid'); ?>
                            </th>
                            <th class="td-amount">
                                <?= $s->format_currency($iR->with_total_paid($client->getClient_id(), $iaR)); ?>
                            </th>
                        </tr>
                        <tr>
                            <th>
                                <?= $translator->translate('i.total_balance'); ?>
                            </th>
                            <td class="td-amount">
                                <?= $s->format_currency($iR->with_total_balance($client->getClient_id(), $iaR)); ?>
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
                                <?php if ($client->getClient_phone()) : ?>
                                    <tr>
                                        <th><?= $translator->translate('i.phone'); ?></th>
                                        <td><?= Html::encode($client->getClient_phone()); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($client->getClient_mobile()) : ?>
                                    <tr>
                                        <th><?= $translator->translate('i.mobile'); ?></th>
                                        <td><?= Html::encode($client->getClient_mobile()); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($client->getClient_fax()) : ?>
                                    <tr>
                                        <th><?= $translator->translate('i.fax'); ?></th>
                                        <td><?= Html::encode($client->getClient_fax()); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($client->getClient_web()) : ?>
                                    <tr>
                                        <th><?= $translator->translate('i.web'); ?></th>
                                        <td><?= Html::link($client->getClient_web()); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($custom_fields as $custom_field) : ?>
                                    <?php if ($custom_field->getLocation() !==2) {
                                        continue;
                                    } ?>
                                    <tr>
                                        <?php 
                                            $column = $custom_field->getLabel(); 
                                            $value = $cvH->form_value($client_custom_values, $custom_field->getId())
                                        ?>
                                        <th><?= Html::encode($column); ?></th>
                                        <td><?= Html::encode($value); ?></td>
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
                                <?php if ($client->getClient_tax_code()) : ?>
                                    <tr>
                                        <th><?= $translator->translate('i.tax_code'); ?></th>
                                        <td><?= Html::encode($client->getClient_tax_code()); ?></td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($custom_fields as $custom_field) : ?>
                                    <?php if ($custom_field->getLocation() != 4) {
                                        continue;
                                    } ?>
                                    <tr>
                                        <?php
                                            $column = $custom_field->getLabel();                                        
                                            $value = $cvH->form_value($client_custom_values, $custom_field->getId())
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
                                                // A dummy date in mysql date field ie. '0000-00-00' is presented as -0001-11-30 in cycle which if var_dumped represents the current date
                                                // therefore present as the current date.
                                                (($client->getClient_birthdate())->format($datehelper->style()))  === '-0001-11-30' 
                                                 ? '0000-00-00' 
                                                 : ($client->getClient_birthdate())->format($datehelper->style());
                                            ?></td>
                                    </tr>
                                    <tr>
                                        <th><?= $translator->translate('i.gender'); ?></th>
                                        <td><?= $clienthelper->format_gender($client->getClient_gender(), $translator); ?></td>
                                    </tr>
                                    <?php if ($s->get_setting('sumex') == '1'): ?>
                                        <tr>
                                            <th><?= $translator->translate('i.sumex_ssn'); ?></th>
                                            <td><?= $cvH->format_avs($client->getClient_avs()); ?></td>
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

                                    <?php foreach ($custom_fields as $custom_field) : ?>
                                        <?php if ($custom_field->getLocation() != 3) {
                                            continue;
                                        } ?>
                                        <tr>
                                            <?php
                                                $column = $custom_field->getLabel();
                                                $value = $cvH->form_value($client_custom_values, $custom_field->getId())
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
                                    <?php foreach ($custom_fields as $custom_field) : ?>
                                        <?php if ($custom_field->getLocation() !== 0) {
                                            continue;
                                        } ?>
                                        <tr>
                                            <?php
                                                $column = $custom_field->getLabel();
                                                $value = $cvH->form_value($client_custom_values, $custom_field->getId());
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

            <hr>

            <div class="row">
                <div class="col-xs-12 col-md-6">

                    <div class="panel panel-default no-margin">
                        <div class="panel-heading">
                            <?= $translator->translate('i.notes'); ?>
                        </div>
                        <div class="panel-body">
                            <div id="notes_list">
                                <?= $partial_notes; ?>
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

        <div id="clientQuotes" class="tab-pane table-content">
            <?= $quote_table; ?>
        </div>
        
        <div id="clientQuotesDraft" class="tab-pane table-content">
            <?= $quote_draft_table; ?>
        </div>
        
        <div id="clientQuotesSent" class="tab-pane table-content">
            <?= $quote_sent_table; ?>
        </div>
        
        <div id="clientQuotesViewed" class="tab-pane table-content">
            <?= $quote_viewed_table; ?>
        </div>
        
        <div id="clientQuotesApproved" class="tab-pane table-content">
            <?= $quote_approved_table; ?>
        </div>
        
        <div id="clientQuotesCancelled" class="tab-pane table-content">
            <?= $quote_cancelled_table; ?>
        </div>
        
        <div id="clientQuotesRejected" class="tab-pane table-content">
            <?= $quote_rejected_table; ?>
        </div>
        
        <div id="clientInvoices" class="tab-pane table-content">
            <?= $invoice_table; ?>
        </div>
        
        <div id="clientInvoicesDraft" class="tab-pane table-content">
            <?= $invoice_draft_table; ?>
        </div>
        
        <div id="clientInvoicesSent" class="tab-pane table-content">
            <?= $invoice_sent_table; ?>
        </div>
        
        <div id="clientInvoicesViewed" class="tab-pane table-content">
            <?= $invoice_viewed_table; ?>
        </div>
        
        <div id="clientInvoicesPaid" class="tab-pane table-content">
            <?= $invoice_paid_table; ?>
        </div>

        <div id="clientPayments" class="tab-pane table-content">
            <?= $payment_table; ?>
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
