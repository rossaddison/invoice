<?php
declare(strict_types=1);

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 * @var array $invoice_groups
 * @var array $payment_methods
 * @var array $pdf_invoice_templates
 * @var array $public_invoice_templates
 * @var array $public_pdf_templates
 * @var array $email_templates_invoice
 * @var array $roles
 * @var array $places
 * @var array $cantons
 */
?>
<div class="row">
    <div class="col-xs-12 col-md-8 col-md-offset-2">

        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('i.invoices'); ?>
            </div>
            <div class="panel-body">

                <div class="row">
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[default_invoice_group]" <?= $s->where('default_invoice_group'); ?>>
                                <?= $translator->translate('i.default_invoice_group'); ?>
                            </label>
                            <?php $body['settings[default_invoice_group]'] = $s->getSetting('default_invoice_group');?>
                            <select name="settings[default_invoice_group]" id="settings[default_invoice_group]"
                                class="form-control" >
                                <option value=""><?= $translator->translate('i.none'); ?></option>
                                <?php
                                /**
                                 * @var App\Invoice\Entity\Group $invoice_group
                                 */
                                foreach ($invoice_groups as $invoice_group) { ?>
                                    <option value="<?= $invoice_group->getId(); ?>"
                                        <?php $s->check_select($body['settings[default_invoice_group]'], $invoice_group->getId()); ?>>
                                        <?= $invoice_group->getName(); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[default_invoice_terms]" <?= $s->where('default_terms'); ?>>
                                <?= $translator->translate('i.default_terms'); ?>
                            </label>
                            <?php $body['settings[default_invoice_terms]'] = $s->getSetting('default_invoice_terms');?>
                            <textarea name="settings[default_invoice_terms]" id="settings[default_invoice_terms]"
                                class="form-control" rows="4"
                                ><?= $body['settings[default_invoice_terms]']; ?></textarea>
                        </div>

                    </div>
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[invoice_default_payment_method]" <?= $s->where('invoice_default_payment_method'); ?>>
                                <?= $translator->translate('i.default_payment_method'); ?>
                            </label>
                            <?php $body['settings[invoice_default_payment_method]'] = $s->getSetting('invoice_default_payment_method');?>
                            <select name="settings[invoice_default_payment_method]" class="form-control"
                                id="settings[invoice_default_payment_method]" >
                                <?php
                                /**
                                 * @var App\Invoice\Entity\PaymentMethod $payment_method
                                 */
                                foreach ($payment_methods as $payment_method) { ?>
                                    <option value="<?= $payment_method->getId(); ?>"
                                        <?php $s->check_select($payment_method->getId(), $body['settings[invoice_default_payment_method]']) ?>>
                                        <?= $payment_method->getName(); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[invoices_due_after]" <?= $s->where('invoices_due_after'); ?>>
                                <?= $translator->translate('i.invoices_due_after'); ?>
                            </label>
                            <?php $body['settings[invoices_due_after]'] = $s->getSetting('invoices_due_after');?>
                            <input type="number" name="settings[invoices_due_after]" id="settings[invoices_due_after]"
                                class="form-control" value="<?= $body['settings[invoices_due_after]']; ?>">
                        </div>

                        <div class="form-group">
                            <label for="settings[generate_invoice_number_for_draft]" <?= $s->where('generate_invoice_number_for_draft'); ?>>
                                <?= $translator->translate('i.generate_invoice_number_for_draft'); ?>
                            </label>
                            <?php $body['settings[generate_invoice_number_for_draft]'] = $s->getSetting('generate_invoice_number_for_draft');?>
                            <select name="settings[generate_invoice_number_for_draft]" class="form-control"
                                id="settings[generate_invoice_number_for_draft]" >
                                <option value="0">
                                    <?= $translator->translate('i.no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[generate_invoice_number_for_draft]'], '1'); ?>>
                                    <?= $translator->translate('i.yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('i.pdf_settings'); ?>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[mark_invoices_sent_pdf]" <?= $s->where('mark_invoices_sent_pdf'); ?>>
                                <?= $translator->translate('i.mark_invoices_sent_pdf'); ?>
                            </label>
                            <?php $body['settings[mark_invoices_sent_pdf]'] = $s->getSetting('mark_invoices_sent_pdf');?>
                            <select name="settings[mark_invoices_sent_pdf]" id="settings[mark_invoices_sent_pdf]" class="form-control" >
                                <option value="0">
                                    <?= $translator->translate('i.no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[mark_invoices_sent_pdf]'], '1'); ?>>
                                    <?= $translator->translate('i.yes'); ?>
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[invoice_pre_password]">
                                <?= $translator->translate('i.invoice_pre_password'); ?>
                            </label>
                            <?php $body['settings[invoice_pre_password]'] = $s->getSetting('invoice_pre_password');?>
                            <input type="text" name="settings[invoice_pre_password]" id="settings[invoice_pre_password]"
                                class="form-control"
                                value="<?= $body['settings[invoice_pre_password]']; ?>">
                        </div>

                        <div class="form-group">
                            <label for="settings[include_zugferd]" <?= $s->where('include_zugferd'); ?>>
                                <?= $translator->translate('i.invoice_pdf_include_zugferd'); ?>
                            </label>                            
                            <?php $body['settings[include_zugferd]'] = $s->getSetting('include_zugferd');?>
                            <select name="settings[include_zugferd]" id="settings[include_zugferd]"
                                class="form-control" >
                                <option value="0">
                                    <?= $translator->translate('i.no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[include_zugferd]'], '1'); ?>>
                                    <?= $translator->translate('i.yes'); ?>
                                </option>
                            </select>
                            <p class="help-block"><?= $translator->translate('i.invoice_pdf_include_zugferd_help'); ?></p>
                        </div>

                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[pdf_watermark]" <?= $s->where('pdf_watermark'); ?>>
                                <?= $translator->translate('i.pdf_watermark'); ?>
                            </label>                                                        
                            <?php $body['settings[pdf_watermark]'] = $s->getSetting('pdf_watermark');?>
                            <select name="settings[pdf_watermark]" id="settings[pdf_watermark]"
                                class="form-control" >
                                <option value="0">
                                    <?= $translator->translate('i.no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[pdf_watermark]'], '1'); ?>>
                                    <?= $translator->translate('i.yes'); ?>
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[pdf_stream_inv]" <?= $s->where('pdf_stream_inv'); ?>>
                                <i class="fa fa-brands fa-google"></i><?= $translator->translate('invoice.invoice.stream'); ?>
                                <?php $body['settings[pdf_stream_inv]'] = $s->getSetting('pdf_stream_inv');?>
                            </label>
                            <select name="settings[pdf_stream_inv]" id="settings[pdf_stream_inv]"
                                class="form-control" >
                                <option value="0">
                                    <?= $translator->translate('i.no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[pdf_stream_inv]'], '1'); ?>>
                                    <?= $translator->translate('i.yes'); ?>
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[pdf_archive_inv]" <?= $s->where('pdf_archive_inv'); ?>>
                                <i class="fa fa-folder"></i><?= $translator->translate('invoice.invoice.archive'); ?>
                                <?php $body['settings[pdf_archive_inv]'] = $s->getSetting('pdf_archive_inv');?>
                            </label>
                            <select name="settings[pdf_archive_inv]" id="settings[pdf_archive_inv]"
                                class="form-control" >
                                <option value="0">
                                    <?= $translator->translate('i.no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[pdf_archive_inv]'], '1'); ?>>
                                    <?= $translator->translate('i.yes'); ?>
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[pdf_html_inv]" <?= $s->where('pdf_html_inv'); ?>>
                                <i class="fa fa-solid fa-code"></i>
                                <?php $body['settings[pdf_html_inv]'] = $s->getSetting('pdf_html_inv');?>
                            </label>
                            <select name="settings[pdf_html_inv]" id="settings[pdf_html_inv]"
                                class="form-control" >
                                <option value="0">
                                    <?= $translator->translate('i.no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[pdf_html_inv]'], '1'); ?>>
                                    <?= $translator->translate('i.yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('i.invoice_templates'); ?>
            </div>
            <div class="panel-body">

                <div class="row">
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[pdf_invoice_template]"<?= $s->where('pdf_invoice_template'); ?>>
                                <?= $translator->translate('i.default_pdf_template'); ?>
                            </label>                                                                                    
                            <?php $body['settings[pdf_invoice_template]'] = $s->getSetting('pdf_invoice_template');?>
                            <select name="settings[pdf_invoice_template]" id="settings[pdf_invoice_template]"
                                class="form-control">
                                <option value=""><?= $translator->translate('i.none'); ?></option>
                                <?php
                                    /**
                                     * @var string $invoice_template
                                     */
                                    foreach ($pdf_invoice_templates as $invoice_template) { ?>
                                        <option value="<?= $invoice_template; ?>"
                                        <?php $s->check_select($body['settings[pdf_invoice_template]'], $invoice_template); ?>>
                                        <?= ucfirst($invoice_template); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[pdf_invoice_template_paid]"<?= $s->where('pdf_invoice_template_paid'); ?>>
                                <?= $translator->translate('i.pdf_template_paid'); ?>
                            </label>                                                                                                                
                            <?php $body['settings[pdf_invoice_template_paid]'] = $s->getSetting('pdf_invoice_template_paid');?>
                            <select name="settings[pdf_invoice_template_paid]" id="settings[pdf_invoice_template_paid]"
                                class="form-control">
                                <option value=""><?= $translator->translate('i.none'); ?></option>
                                <?php
                                    /**
                                     * @var string $invoice_template
                                     */
                                    foreach ($pdf_invoice_templates as $invoice_template) { ?>
                                    <option value="<?= $invoice_template; ?>"
                                        <?php $s->check_select($body['settings[pdf_invoice_template_paid]'], $invoice_template); ?>>
                                        <?= ucfirst($invoice_template); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[pdf_invoice_template_overdue]"<?= $s->where('pdf_invoice_template_overdue'); ?>>
                                <?= $translator->translate('i.pdf_template_overdue'); ?>
                            </label>
                            <?php $body['settings[pdf_invoice_template_overdue]'] = $s->getSetting('pdf_invoice_template_overdue');?>
                            <select name="settings[pdf_invoice_template_overdue]" class="form-control"
                                id="settings[pdf_invoice_template_overdue]" >
                                <option value=""><?= $translator->translate('i.none'); ?></option>
                                <?php
                                    /**
                                     * @var string $invoice_template
                                     */
                                    foreach ($pdf_invoice_templates as $invoice_template) { ?>
                                    <option value="<?= $invoice_template; ?>"
                                        <?php $s->check_select($body['settings[pdf_invoice_template_overdue]'], $invoice_template); ?>>
                                        <?= ucfirst($invoice_template); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[public_invoice_template]"<?= $s->where('default_public_template'); ?>>
                                <?= $translator->translate('i.default_public_template'); ?>
                            </label>                            
                            <?php $body['settings[public_invoice_template]'] = $s->getSetting('public_invoice_template');?>
                            <select name="settings[public_invoice_template]" id="settings[public_invoice_template]"
                                class="form-control">
                                <option value=""><?= $translator->translate('i.none'); ?></option>
                                <?php
                                    /**
                                     * @var string $invoice_template
                                     */
                                    foreach ($public_invoice_templates as $invoice_template) { ?>
                                    <option value="<?= $invoice_template; ?>"
                                        <?php $s->check_select($body['settings[public_invoice_template]'], $invoice_template); ?>>
                                        <?= ucfirst($invoice_template); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                    </div>
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[email_invoice_template]" <?= $s->where('default_email_template'); ?>>
                                <?= $translator->translate('i.default_email_template'); ?>
                            </label>                                                        
                            <?php $body['settings[email_invoice_template]'] = $s->getSetting('email_invoice_template');?>
                            <select name="settings[email_invoice_template]" id="settings[email_invoice_template]"
                                class="form-control">
                                <option value=""><?= $translator->translate('i.none'); ?></option>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\EmailTemplate $email_template
                                     */
                                    foreach ($email_templates_invoice as $email_template) { ?>
                                    <option value="<?= $email_template->GetEmail_template_id(); ?>"
                                        <?php $s->check_select($body['settings[email_invoice_template]'], $email_template->getEmail_template_id()); ?>>
                                        <?= $email_template->getEmail_template_title(); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[email_invoice_template_paid]">
                                <?= $translator->translate('i.email_template_paid'); ?>
                            </label>                                                                                    
                            <?php $body['settings[email_invoice_template_paid]'] = $s->getSetting('email_invoice_template_paid');?>
                            <select name="settings[email_invoice_template_paid]" id="settings[email_invoice_template_paid]"
                                class="form-control">
                                <option value=""><?= $translator->translate('i.none'); ?></option>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\EmailTemplate $email_template
                                     */
                                    foreach ($email_templates_invoice as $email_template) { ?>
                                    <option value="<?= $email_template->getEmail_template_id(); ?>"
                                        <?php $s->check_select($body['settings[email_invoice_template_paid]'], $email_template->getEmail_template_id()); ?>>
                                        <?= $email_template->getEmail_template_title(); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[email_invoice_template_overdue]">
                                <?= $translator->translate('i.email_template_overdue'); ?>
                            </label>                                       
                            <?php $body['settings[email_invoice_template_overdue]'] = $s->getSetting('email_invoice_template_overdue');?>
                            <select name="settings[email_invoice_template_overdue]" class="form-control"
                                id="settings[email_invoice_template_overdue]">
                                <option value=""><?= $translator->translate('i.none'); ?></option>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\EmailTemplate $email_template
                                     */
                                    foreach ($email_templates_invoice as $email_template) { ?>
                                    <option value="<?= $email_template->getEmail_template_id(); ?>"
                                        <?php $s->check_select($body['settings[email_invoice_template_overdue]'], $email_template->getEmail_template_id()); ?>>
                                        <?= $email_template->getEmail_template_title(); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                    </div>
                </div>

                <div class="row">
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[pdf_invoice_footer]">
                                <?= $translator->translate('i.pdf_invoice_footer'); ?>
                            </label>                                                                   
                            <?php $body['settings[pdf_invoice_footer]'] = $s->getSetting('pdf_invoice_footer');?>
                            <textarea name="settings[pdf_invoice_footer]" id="settings[pdf_invoice_footer]"
                                class="form-control no-margin"><?= $body['settings[pdf_invoice_footer]']; ?></textarea>
                            <p class="help-block"><?= $translator->translate('i.pdf_invoice_footer_hint'); ?></p>
                        </div>

                    </div>
                </div>

            </div>
        </div>

                        <div class="panel panel-default">
                            <div class="panel-heading">
                <?= $translator->translate('i.email_settings'); ?>
            </div>
            <div class="panel-body">

                <div class="row">
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[automatic_email_on_recur]">
                                <?= $translator->translate('i.automatic_email_on_recur'); ?>
                            </label>                                                                                               
                            <?php $body['settings[automatic_email_on_recur]'] = $s->getSetting('automatic_email_on_recur');?>
                            <select name="settings[automatic_email_on_recur]" id="settings[automatic_email_on_recur]"
                                class="form-control">
                                <option value="0">
                                    <?= $translator->translate('i.no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[automatic_email_on_recur]'], '1'); ?>>
                                    <?= $translator->translate('i.yes'); ?>
                                </option>
                            </select>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('i.other_settings'); ?>
            </div>
            <div class="panel-body">

                <div class="row">
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[read_only_toggle]" <?= $s->where('read_only_toggle'); ?>>
                                <?= $translator->translate('i.set_to_read_only'); ?>
                            </label>                                                                                                                           
                            <?php $body['settings[read_only_toggle]'] = $s->getSetting('read_only_toggle');?>
                            <select name="settings[read_only_toggle]" id="settings[read_only_toggle]"
                                class="form-control">
                                <option value="2" <?php $s->check_select($body['settings[read_only_toggle]'], '2'); ?>>
                                    <?= $translator->translate('i.sent'); ?>
                                </option>
                                <option value="3" <?php $s->check_select($body['settings[read_only_toggle]'], '3'); ?>>
                                    <?= $translator->translate('i.viewed'); ?>
                                </option>
                                <option value="4" <?php $s->check_select($body['settings[read_only_toggle]'], '4'); ?>>
                                    <?= $translator->translate('i.paid'); ?>
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="settings[mark_invoices_sent_copy]" <?= $s->where('mark_invoices_sent_copy'); ?>>
                                <?= $translator->translate('i.mark_invoices_sent_copy'); ?>
                            </label>
                            <?php $body['settings[mark_invoices_sent_copy]'] = $s->getSetting('mark_invoices_sent_copy');?>
                            <select name="settings[mark_invoices_sent_copy]" id="settings[mark_invoices_sent_copy]"
                                class="form-control" >
                                <option value="0">
                                    <?= $translator->translate('i.no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[mark_invoices_sent_copy]'], '1'); ?>>
                                    <?= $translator->translate('i.yes'); ?>
                                </option>
                            </select>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('i.sumex_settings'); ?>
            </div>
            <div class="panel-body">

                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[sumex]">
                                <?= $translator->translate('i.invoice_sumex'); ?>
                            </label>                                                                                                                                                       
                            <?php $body['settings[sumex]'] = $s->getSetting('sumex');?>
                            <select name="settings[sumex]" id="settings[sumex]"
                                class="form-control">
                                <option value="0">
                                    <?= $translator->translate('i.no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[sumex]'], '1'); ?>>
                                    <?= $translator->translate('i.yes'); ?>
                                </option>
                            </select>
                            <p class="help-block"><?= $translator->translate('i.invoice_sumex_help'); ?></p>
                        </div>

                        <div class="form-group">
                            <label for="settings[sumex_sliptype]">
                                <?= $translator->translate('i.invoice_sumex_sliptype'); ?>
                            </label>                                                                                                                                                                                   
                            <?php $body['settings[sumex_sliptype]'] = $s->getSetting('sumex_sliptype');?>
                            <select name="settings[sumex_sliptype]" id="settings[sumex_sliptype]"
                                class="form-control">
                                <?php
                                $slipTypes = array("esr9", "esrRed");
/**
 * @var string $k
 * @var string $v
 */
foreach ($slipTypes as $k => $v): ?>
                                    <option value="<?= $k; ?>" <?php $s->check_select($body['settings[sumex_sliptype]'], $k) ?>>
                                        <?= $translator->translate('i.invoice_sumex_sliptype-' . $v); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="help-block"><?= $translator->translate('i.invoice_sumex_sliptype_help'); ?></p>
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[sumex_role]">
                                <?= $translator->translate('i.invoice_sumex_role'); ?>
                            </label>                                                                                                                                                                                                               
                            <?php $body['settings[sumex_role]'] = $s->getSetting('sumex_role');?>
                            <select name="settings[sumex_role]" id="settings[sumex_role]"
                                class="form-control">
                                <?php
    /**
     * @var string $k
     * @var string $v
     */
    foreach ($roles as $k => $v): ?>
                                    <option value="<?= $k; ?>" <?php $s->check_select($body['settings[sumex_role]'], $k) ?>>
                                        <?= $translator->translate('i.invoice_sumex_role_' . $v); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[sumex_place]">
                                <?= $translator->translate('i.invoice_sumex_place'); ?>
                            </label>                                                                                                                                                                                                                                           
                            <?php $body['settings[sumex_place]'] = $s->getSetting('sumex_place');?>
                            <select name="settings[sumex_place]" id="settings[sumex_place]"
                                class="form-control">
                                <?php
    /**
     * @var string $k
     * @var string $v
     */
    foreach ($places as $k => $v): ?>
                                    <option value="<?= $k; ?>" <?php $s->check_select($body['settings[sumex_place]'], $k); ?>>
                                        <?= $translator->translate('i.invoice_sumex_place_' . $v); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[sumex_canton]">
                                <?= $translator->translate('i.invoice_sumex_canton'); ?>
                            </label>                                                                                                                                                                                                                                                                       
                            <?php $body['settings[sumex_canton]'] = $s->getSetting('sumex_canton');?>
                            <select name="settings[sumex_canton]" id="settings[sumex_canton]"
                                class="form-control">
                                <?php
    /**
     * @var string $k
     * @var string $v
     */
    foreach ($cantons as $k => $v): ?>
                                    <option value="<?= $k; ?>" <?php $s->check_select($body['settings[sumex_canton]'], $k); ?>>
                                        <?= $v; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
