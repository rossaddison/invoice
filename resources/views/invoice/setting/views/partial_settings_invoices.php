<?php
declare(strict_types=1);

/**
 * @var App\Invoice\Setting\SettingRepository  $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array                                  $body
 * @var array                                  $invoice_groups
 * @var array                                  $payment_methods
 * @var array                                  $pdf_invoice_templates
 * @var array                                  $public_invoice_templates
 * @var array                                  $public_pdf_templates
 * @var array                                  $email_templates_invoice
 * @var array                                  $roles
 * @var array                                  $places
 * @var array                                  $cantons
 */
?>
<div class="row">
    <div class="col-xs-12 col-md-8 col-md-offset-2">

        <div class="panel panel-default">
            <div class="panel-heading">
                <?php echo $translator->translate('invoices'); ?>
            </div>
            <div class="panel-body">

                <div class="row">
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[default_invoice_group]" <?php echo $s->where('default_invoice_group'); ?>>
                                <?php echo $translator->translate('default.invoice.group'); ?>
                            </label>
                            <?php $body['settings[default_invoice_group]'] = $s->getSetting('default_invoice_group'); ?>
                            <select name="settings[default_invoice_group]" id="settings[default_invoice_group]"
                                class="form-control" >
                                <option value=""><?php echo $translator->translate('none'); ?></option>
                                <?php
                                /**
                                 * @var App\Invoice\Entity\Group $invoice_group
                                 */
                                foreach ($invoice_groups as $invoice_group) { ?>
                                    <option value="<?php echo $invoice_group->getId(); ?>"
                                        <?php $s->check_select($body['settings[default_invoice_group]'], $invoice_group->getId()); ?>>
                                        <?php echo $invoice_group->getName(); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[default_invoice_terms]" <?php echo $s->where('default_terms'); ?>>
                                <?php echo $translator->translate('default.terms'); ?>
                            </label>
                            <?php $body['settings[default_invoice_terms]'] = $s->getSetting('default_invoice_terms'); ?>
                            <textarea name="settings[default_invoice_terms]" id="settings[default_invoice_terms]"
                                class="form-control" rows="4"
                                ><?php echo $body['settings[default_invoice_terms]']; ?></textarea>
                        </div>

                    </div>
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[invoice_default_payment_method]" <?php echo $s->where('invoice_default_payment_method'); ?>>
                                <?php echo $translator->translate('default.payment.method'); ?>
                            </label>
                            <?php $body['settings[invoice_default_payment_method]'] = $s->getSetting('invoice_default_payment_method'); ?>
                            <select name="settings[invoice_default_payment_method]" class="form-control"
                                id="settings[invoice_default_payment_method]" >
                                <?php
                                /**
                                 * @var App\Invoice\Entity\PaymentMethod $payment_method
                                 */
                                foreach ($payment_methods as $payment_method) { ?>
                                    <option value="<?php echo $payment_method->getId(); ?>"
                                        <?php $s->check_select($payment_method->getId(), $body['settings[invoice_default_payment_method]']); ?>>
                                        <?php echo $payment_method->getName(); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[invoices_due_after]" <?php echo $s->where('invoices_due_after'); ?>>
                                <?php echo $translator->translate('invoices.due.after'); ?>
                            </label>
                            <?php $body['settings[invoices_due_after]'] = $s->getSetting('invoices_due_after'); ?>
                            <input type="number" name="settings[invoices_due_after]" id="settings[invoices_due_after]"
                                class="form-control" value="<?php echo $body['settings[invoices_due_after]']; ?>">
                        </div>

                        <div class="form-group">
                            <label for="settings[generate_invoice_number_for_draft]" <?php echo $s->where('generate_invoice_number_for_draft'); ?>>
                                <?php echo $translator->translate('generate.invoice.number.for.draft'); ?>
                            </label>
                            <?php $body['settings[generate_invoice_number_for_draft]'] = $s->getSetting('generate_invoice_number_for_draft'); ?>
                            <select name="settings[generate_invoice_number_for_draft]" class="form-control"
                                id="settings[generate_invoice_number_for_draft]" >
                                <option value="0">
                                    <?php echo $translator->translate('no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[generate_invoice_number_for_draft]'], '1'); ?>>
                                    <?php echo $translator->translate('yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <?php echo $translator->translate('pdf.settings'); ?>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[mark_invoices_sent_pdf]" <?php echo $s->where('mark_invoices_sent_pdf'); ?>>
                                <?php echo $translator->translate('mark.invoices.sent.pdf'); ?>
                            </label>
                            <?php $body['settings[mark_invoices_sent_pdf]'] = $s->getSetting('mark_invoices_sent_pdf'); ?>
                            <select name="settings[mark_invoices_sent_pdf]" id="settings[mark_invoices_sent_pdf]" class="form-control" >
                                <option value="0">
                                    <?php echo $translator->translate('no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[mark_invoices_sent_pdf]'], '1'); ?>>
                                    <?php echo $translator->translate('yes'); ?>
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[invoice_pre_password]">
                                <?php echo $translator->translate('pre.password'); ?>
                            </label>
                            <?php $body['settings[invoice_pre_password]'] = $s->getSetting('invoice_pre_password'); ?>
                            <input type="text" name="settings[invoice_pre_password]" id="settings[invoice_pre_password]"
                                class="form-control"
                                value="<?php echo $body['settings[invoice_pre_password]']; ?>">
                        </div>

                        <div class="form-group">
                            <label for="settings[include_zugferd]" <?php echo $s->where('include_zugferd'); ?>>
                                <?php echo $translator->translate('pdf.include.zugferd'); ?>
                            </label>                            
                            <?php $body['settings[include_zugferd]'] = $s->getSetting('include_zugferd'); ?>
                            <select name="settings[include_zugferd]" id="settings[include_zugferd]"
                                class="form-control" >
                                <option value="0">
                                    <?php echo $translator->translate('no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[include_zugferd]'], '1'); ?>>
                                    <?php echo $translator->translate('yes'); ?>
                                </option>
                            </select>
                            <p class="help-block"><?php echo $translator->translate('pdf.include.zugferd.help'); ?></p>
                        </div>

                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[pdf_watermark]" <?php echo $s->where('pdf_watermark'); ?>>
                                <?php echo $translator->translate('pdf.watermark'); ?>
                            </label>                                                        
                            <?php $body['settings[pdf_watermark]'] = $s->getSetting('pdf_watermark'); ?>
                            <select name="settings[pdf_watermark]" id="settings[pdf_watermark]"
                                class="form-control" >
                                <option value="0">
                                    <?php echo $translator->translate('no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[pdf_watermark]'], '1'); ?>>
                                    <?php echo $translator->translate('yes'); ?>
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[pdf_stream_inv]" <?php echo $s->where('pdf_stream_inv'); ?>>
                                <i class="fa fa-brands fa-google"></i><?php echo $translator->translate('stream'); ?>
                                <?php $body['settings[pdf_stream_inv]'] = $s->getSetting('pdf_stream_inv'); ?>
                            </label>
                            <select name="settings[pdf_stream_inv]" id="settings[pdf_stream_inv]"
                                class="form-control" >
                                <option value="0">
                                    <?php echo $translator->translate('no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[pdf_stream_inv]'], '1'); ?>>
                                    <?php echo $translator->translate('yes'); ?>
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[pdf_archive_inv]" <?php echo $s->where('pdf_archive_inv'); ?>>
                                <i class="fa fa-folder"></i><?php echo $translator->translate('archive'); ?>
                                <?php $body['settings[pdf_archive_inv]'] = $s->getSetting('pdf_archive_inv'); ?>
                            </label>
                            <select name="settings[pdf_archive_inv]" id="settings[pdf_archive_inv]"
                                class="form-control" >
                                <option value="0">
                                    <?php echo $translator->translate('no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[pdf_archive_inv]'], '1'); ?>>
                                    <?php echo $translator->translate('yes'); ?>
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[pdf_html_inv]" <?php echo $s->where('pdf_html_inv'); ?>>
                                <i class="fa fa-solid fa-code"></i>
                                <?php $body['settings[pdf_html_inv]'] = $s->getSetting('pdf_html_inv'); ?>
                            </label>
                            <select name="settings[pdf_html_inv]" id="settings[pdf_html_inv]"
                                class="form-control" >
                                <option value="0">
                                    <?php echo $translator->translate('no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[pdf_html_inv]'], '1'); ?>>
                                    <?php echo $translator->translate('yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <?php echo $translator->translate('templates'); ?>
            </div>
            <div class="panel-body">

                <div class="row">
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[pdf_invoice_template]"<?php echo $s->where('pdf_invoice_template'); ?>>
                                <?php echo $translator->translate('default.pdf.template'); ?>
                            </label>                                                                                    
                            <?php $body['settings[pdf_invoice_template]'] = $s->getSetting('pdf_invoice_template'); ?>
                            <select name="settings[pdf_invoice_template]" id="settings[pdf_invoice_template]"
                                class="form-control">
                                <option value=""><?php echo $translator->translate('none'); ?></option>
                                <?php
                                    /**
                                     * @var string $invoice_template
                                     */
                                    foreach ($pdf_invoice_templates as $invoice_template) { ?>
                                        <option value="<?php echo $invoice_template; ?>"
                                        <?php $s->check_select($body['settings[pdf_invoice_template]'], $invoice_template); ?>>
                                        <?php echo ucfirst($invoice_template); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[pdf_invoice_template_paid]"<?php echo $s->where('pdf_invoice_template_paid'); ?>>
                                <?php echo $translator->translate('pdf.template.paid'); ?>
                            </label>                                                                                                                
                            <?php $body['settings[pdf_invoice_template_paid]'] = $s->getSetting('pdf_invoice_template_paid'); ?>
                            <select name="settings[pdf_invoice_template_paid]" id="settings[pdf_invoice_template_paid]"
                                class="form-control">
                                <option value=""><?php echo $translator->translate('none'); ?></option>
                                <?php
                                    /**
                                     * @var string $invoice_template
                                     */
                                    foreach ($pdf_invoice_templates as $invoice_template) { ?>
                                    <option value="<?php echo $invoice_template; ?>"
                                        <?php $s->check_select($body['settings[pdf_invoice_template_paid]'], $invoice_template); ?>>
                                        <?php echo ucfirst($invoice_template); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[pdf_invoice_template_overdue]"<?php echo $s->where('pdf_invoice_template_overdue'); ?>>
                                <?php echo $translator->translate('pdf.template.overdue'); ?>
                            </label>
                            <?php $body['settings[pdf_invoice_template_overdue]'] = $s->getSetting('pdf_invoice_template_overdue'); ?>
                            <select name="settings[pdf_invoice_template_overdue]" class="form-control"
                                id="settings[pdf_invoice_template_overdue]" >
                                <option value=""><?php echo $translator->translate('none'); ?></option>
                                <?php
                                    /**
                                     * @var string $invoice_template
                                     */
                                    foreach ($pdf_invoice_templates as $invoice_template) { ?>
                                    <option value="<?php echo $invoice_template; ?>"
                                        <?php $s->check_select($body['settings[pdf_invoice_template_overdue]'], $invoice_template); ?>>
                                        <?php echo ucfirst($invoice_template); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[public_invoice_template]"<?php echo $s->where('default_public_template'); ?>>
                                <?php echo $translator->translate('default.public.template'); ?>
                            </label>                            
                            <?php $body['settings[public_invoice_template]'] = $s->getSetting('public_invoice_template'); ?>
                            <select name="settings[public_invoice_template]" id="settings[public_invoice_template]"
                                class="form-control">
                                <option value=""><?php echo $translator->translate('none'); ?></option>
                                <?php
                                    /**
                                     * @var string $invoice_template
                                     */
                                    foreach ($public_invoice_templates as $invoice_template) { ?>
                                    <option value="<?php echo $invoice_template; ?>"
                                        <?php $s->check_select($body['settings[public_invoice_template]'], $invoice_template); ?>>
                                        <?php echo ucfirst($invoice_template); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                    </div>
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[email_invoice_template]" <?php echo $s->where('default_email_template'); ?>>
                                <?php echo $translator->translate('default.email.template'); ?>
                            </label>                                                        
                            <?php $body['settings[email_invoice_template]'] = $s->getSetting('email_invoice_template'); ?>
                            <select name="settings[email_invoice_template]" id="settings[email_invoice_template]"
                                class="form-control">
                                <option value=""><?php echo $translator->translate('none'); ?></option>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\EmailTemplate $email_template
                                     */
                                    foreach ($email_templates_invoice as $email_template) { ?>
                                    <option value="<?php echo $email_template->GetEmail_template_id(); ?>"
                                        <?php $s->check_select($body['settings[email_invoice_template]'], $email_template->getEmail_template_id()); ?>>
                                        <?php echo $email_template->getEmail_template_title(); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[email_invoice_template_paid]">
                                <?php echo $translator->translate('email.template.paid'); ?>
                            </label>                                                                                    
                            <?php $body['settings[email_invoice_template_paid]'] = $s->getSetting('email_invoice_template_paid'); ?>
                            <select name="settings[email_invoice_template_paid]" id="settings[email_invoice_template_paid]"
                                class="form-control">
                                <option value=""><?php echo $translator->translate('none'); ?></option>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\EmailTemplate $email_template
                                     */
                                    foreach ($email_templates_invoice as $email_template) { ?>
                                    <option value="<?php echo $email_template->getEmail_template_id(); ?>"
                                        <?php $s->check_select($body['settings[email_invoice_template_paid]'], $email_template->getEmail_template_id()); ?>>
                                        <?php echo $email_template->getEmail_template_title(); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[email_invoice_template_overdue]">
                                <?php echo $translator->translate('email.template.overdue'); ?>
                            </label>                                       
                            <?php $body['settings[email_invoice_template_overdue]'] = $s->getSetting('email_invoice_template_overdue'); ?>
                            <select name="settings[email_invoice_template_overdue]" class="form-control"
                                id="settings[email_invoice_template_overdue]">
                                <option value=""><?php echo $translator->translate('none'); ?></option>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\EmailTemplate $email_template
                                     */
                                    foreach ($email_templates_invoice as $email_template) { ?>
                                    <option value="<?php echo $email_template->getEmail_template_id(); ?>"
                                        <?php $s->check_select($body['settings[email_invoice_template_overdue]'], $email_template->getEmail_template_id()); ?>>
                                        <?php echo $email_template->getEmail_template_title(); ?>
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
                                <?php echo $translator->translate('pdf.invoice.footer'); ?>
                            </label>                                                                   
                            <?php $body['settings[pdf_invoice_footer]'] = $s->getSetting('pdf_invoice_footer'); ?>
                            <textarea name="settings[pdf_invoice_footer]" id="settings[pdf_invoice_footer]"
                                class="form-control no-margin"><?php echo $body['settings[pdf_invoice_footer]']; ?></textarea>
                            <p class="help-block"><?php echo $translator->translate('pdf.invoice.footer.hint'); ?></p>
                        </div>

                    </div>
                </div>

            </div>
        </div>

                        <div class="panel panel-default">
                            <div class="panel-heading">
                <?php echo $translator->translate('email.settings'); ?>
            </div>
            <div class="panel-body">

                <div class="row">
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[automatic_email_on_recur]">
                                <?php echo $translator->translate('automatic.email.on.recur'); ?>
                            </label>                                                                                               
                            <?php $body['settings[automatic_email_on_recur]'] = $s->getSetting('automatic_email_on_recur'); ?>
                            <select name="settings[automatic_email_on_recur]" id="settings[automatic_email_on_recur]"
                                class="form-control">
                                <option value="0">
                                    <?php echo $translator->translate('no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[automatic_email_on_recur]'], '1'); ?>>
                                    <?php echo $translator->translate('yes'); ?>
                                </option>
                            </select>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <?php echo $translator->translate('other.settings'); ?>
            </div>
            <div class="panel-body">

                <div class="row">
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[read_only_toggle]" <?php echo $s->where('read_only_toggle'); ?>>
                                <?php echo $translator->translate('set.to.read.only'); ?>
                            </label>                                                                                                                           
                            <?php $body['settings[read_only_toggle]'] = $s->getSetting('read_only_toggle'); ?>
                            <select name="settings[read_only_toggle]" id="settings[read_only_toggle]"
                                class="form-control">
                                <option value="2" <?php $s->check_select($body['settings[read_only_toggle]'], '2'); ?>>
                                    <?php echo $translator->translate('sent'); ?>
                                </option>
                                <option value="3" <?php $s->check_select($body['settings[read_only_toggle]'], '3'); ?>>
                                    <?php echo $translator->translate('viewed'); ?>
                                </option>
                                <option value="4" <?php $s->check_select($body['settings[read_only_toggle]'], '4'); ?>>
                                    <?php echo $translator->translate('paid'); ?>
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="settings[mark_invoices_sent_copy]" <?php echo $s->where('mark_invoices_sent_copy'); ?>>
                                <?php echo $translator->translate('mark.invoices.sent.copy'); ?>
                            </label>
                            <?php $body['settings[mark_invoices_sent_copy]'] = $s->getSetting('mark_invoices_sent_copy'); ?>
                            <select name="settings[mark_invoices_sent_copy]" id="settings[mark_invoices_sent_copy]"
                                class="form-control" >
                                <option value="0">
                                    <?php echo $translator->translate('no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[mark_invoices_sent_copy]'], '1'); ?>>
                                    <?php echo $translator->translate('yes'); ?>
                                </option>
                            </select>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <?php echo $translator->translate('sumex.settings'); ?>
            </div>
            <div class="panel-body">

                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[sumex]">
                                <?php echo $translator->translate('sumex'); ?>
                            </label>                                                                                                                                                       
                            <?php $body['settings[sumex]'] = $s->getSetting('sumex'); ?>
                            <select name="settings[sumex]" id="settings[sumex]"
                                class="form-control">
                                <option value="0">
                                    <?php echo $translator->translate('no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[sumex]'], '1'); ?>>
                                    <?php echo $translator->translate('yes'); ?>
                                </option>
                            </select>
                            <p class="help-block"><?php echo $translator->translate('sumex.help'); ?></p>
                        </div>

                        <div class="form-group">
                            <label for="settings[sumex_sliptype]">
                                <?php echo $translator->translate('sumex.sliptype'); ?>
                            </label>                                                                                                                                                                                   
                            <?php $body['settings[sumex_sliptype]'] = $s->getSetting('sumex_sliptype'); ?>
                            <select name="settings[sumex_sliptype]" id="settings[sumex_sliptype]"
                                class="form-control">
                                <?php
                                $slipTypes = ['esr9', 'esrRed'];
/**
 * @var string $k
 * @var string $v
 */
foreach ($slipTypes as $k => $v) { ?>
                                    <option value="<?php echo $k; ?>" <?php $s->check_select($body['settings[sumex_sliptype]'], $k); ?>>
                                        <?php echo $translator->translate('sumex.sliptype-'.$v); ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <p class="help-block"><?php echo $translator->translate('sumex.sliptype.help'); ?></p>
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[sumex_role]">
                                <?php echo $translator->translate('sumex.role'); ?>
                            </label>                                                                                                                                                                                                               
                            <?php $body['settings[sumex_role]'] = $s->getSetting('sumex_role'); ?>
                            <select name="settings[sumex_role]" id="settings[sumex_role]"
                                class="form-control">
                                <?php
    /**
     * @var string $k
     * @var string $v
     */
    foreach ($roles as $k => $v) { ?>
                                    <option value="<?php echo $k; ?>" <?php $s->check_select($body['settings[sumex_role]'], $k); ?>>
                                        <?php echo $translator->translate('sumex.role.'.$v); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[sumex_place]">
                                <?php echo $translator->translate('sumex.place'); ?>
                            </label>                                                                                                                                                                                                                                           
                            <?php $body['settings[sumex_place]'] = $s->getSetting('sumex_place'); ?>
                            <select name="settings[sumex_place]" id="settings[sumex_place]"
                                class="form-control">
                                <?php
    /**
     * @var string $k
     * @var string $v
     */
    foreach ($places as $k => $v) { ?>
                                    <option value="<?php echo $k; ?>" <?php $s->check_select($body['settings[sumex_place]'], $k); ?>>
                                        <?php echo $translator->translate('sumex.place.'.$v); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[sumex_canton]">
                                <?php echo $translator->translate('sumex.canton'); ?>
                            </label>                                                                                                                                                                                                                                                                       
                            <?php $body['settings[sumex_canton]'] = $s->getSetting('sumex_canton'); ?>
                            <select name="settings[sumex_canton]" id="settings[sumex_canton]"
                                class="form-control">
                                <?php
    /**
     * @var string $k
     * @var string $v
     */
    foreach ($cantons as $k => $v) { ?>
                                    <option value="<?php echo $k; ?>" <?php $s->check_select($body['settings[sumex_canton]'], $k); ?>>
                                        <?php echo $v; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
