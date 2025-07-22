<?php
declare(strict_types=1);

/**
 * @var App\Invoice\Setting\SettingRepository  $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array                                  $body
 * @var array                                  $invoice_groups
 * @var array                                  $public_quote_templates
 * @var array                                  $pdf_quote_templates
 * @var array                                  $email_templates_quote
 */
?>
<div class='row'>
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?php echo $translator->translate('quote'); ?>
            </div>
            <div class="panel-body">
                <div class='row'>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[default_quote_group]">
                                <?php echo $translator->translate('default.quote.group'); ?>
                            </label>
                            <?php $body['settings[default_quote_group]'] = $s->getSetting('default_quote_group'); ?>
                            <select name="settings[default_quote_group]" id="settings[default_quote_group]"
                                class="form-control" data-minimum-results-for-search="Infinity">
                                <option value=""><?php echo $translator->translate('none'); ?></option>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\Group $invoice_group
                                     */
                                    foreach ($invoice_groups as $invoice_group) { ?>
                                    <option value="<?php echo $invoice_group->getId(); ?>"
                                        <?php $s->check_select($body['settings[default_quote_group]'], $invoice_group->getId()); ?>>
                                        <?php echo $invoice_group->getName(); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[default_quote_notes]">
                                <?php echo $translator->translate('default.notes'); ?>
                            </label>
                            <?php $body['settings[default_quote_notes]'] = $s->getSetting('default_quote_notes'); ?>
                            <textarea name="settings[default_quote_notes]" id="settings[default_quote_notes]" rows="3"
                                class="form-control"><?php echo $body['settings[default_quote_notes]']; ?></textarea>
                        </div>

                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[quotes_expire_after]">
                                <?php echo $translator->translate('quotes.expire.after'); ?>
                            </label>
                            <?php $body['settings[quotes_expire_after]'] = $s->getSetting('quotes_expire_after'); ?>
                            <input type="number" name="settings[quotes_expire_after]" id="settings[quotes_expire_after]"
                                class="form-control"
                                value="<?php echo $body['settings[quotes_expire_after]']; ?>">
                        </div>
                        <div class="form-group">
                            <label for="settings[generate_quote_number_for_draft]">
                                <?php echo $translator->translate('generate.quote.number.for.draft'); ?>
                            </label>                            
                            <?php $body['settings[generate_quote_number_for_draft]'] = $s->getSetting('generate_quote_number_for_draft'); ?>
                            <select name="settings[generate_quote_number_for_draft]" class="form-control"
                                id="settings[generate_quote_number_for_draft]" data-minimum-results-for-search="Infinity">
                                <option value="0">
                                    <?php echo $translator->translate('no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[generate_quote_number_for_draft]'], '1'); ?>>
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
                <div class='row'>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[mark_quotes_sent_pdf]">
                                <?php echo $translator->translate('mark.quotes.sent.pdf'); ?>
                            </label>
                            <?php $body['settings[mark_quotes_sent_pdf]'] = $s->getSetting('mark_quotes_sent_pdf'); ?>
                            <select name="settings[mark_quotes_sent_pdf]" id="settings[mark_quotes_sent_pdf]"
                                class="form-control" data-minimum-results-for-search="Infinity">
                                <option value="0">
                                    <?php echo $translator->translate('no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[mark_quotes_sent_pdf]'], '1'); ?>>
                                    <?php echo $translator->translate('yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[quote_pre_password]">
                                <?php echo $translator->translate('quote.pre.password'); ?>
                            </label>
                            <?php $body['settings[quote_pre_password]'] = $s->getSetting('quote_pre_password'); ?>
                            <input type="text" name="settings[quote_pre_password]" id="settings[quote_pre_password]"
                                class="form-control" value="<?php echo $body['settings[quote_pre_password]']; ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <?php echo $translator->translate('quote.templates'); ?>
            </div>
            <div class="panel-body">
                <div class='div'>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[pdf_quote_template]">
                                <?php echo $translator->translate('default.pdf.template'); ?>
                            </label>                            
                            <?php $body['settings[pdf_quote_template]'] = $s->getSetting('pdf_quote_template'); ?>
                            <select name="settings[pdf_quote_template]" id="settings[pdf_quote_template]"
                                class="form-control" data-minimum-results-for-search="Infinity">
                                <option value=""><?php echo $translator->translate('none'); ?></option>
                                <?php
                                    /**
                                     * @var string $quote_template
                                     */
                                    foreach ($pdf_quote_templates as $quote_template) { ?>
                                    <option value="<?php echo $quote_template; ?>"
                                        <?php $s->check_select($body['settings[pdf_quote_template]'], $quote_template); ?>>
                                        <?php echo ucfirst($quote_template); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[public_quote_template]">
                                <?php echo $translator->translate('default.public.template'); ?>
                            </label>                            
                            <?php $body['settings[public_quote_template]'] = $s->getSetting('public_quote_template'); ?>
                            <select name="settings[public_quote_template]" id="settings[public_quote_template]"
                                class="form-control" data-minimum-results-for-search="Infinity">
                                <option value=""><?php echo $translator->translate('none'); ?></option>
                                <?php
                                    /**
                                     * @var string $quote_template
                                     */
                                    foreach ($public_quote_templates as $quote_template) { ?>
                                    <option value="<?php echo $quote_template; ?>"
                                        <?php $s->check_select($body['settings[public_quote_template]'], $quote_template); ?>>
                                        <?php echo ucfirst($quote_template); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                    </div>
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[email_quote_template]">
                                <?php echo $translator->translate('default.email.template'); ?>
                            </label>                                                        
                            <?php $body['settings[email_quote_template]'] = $s->getSetting('email_quote_template'); ?>
                            <select name="settings[email_quote_template]" id="settings[email_quote_template]"
                                class="form-control" data-minimum-results-for-search="Infinity">
                                <option value=""><?php echo $translator->translate('none'); ?></option>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\EmailTemplate $email_template
                                     */
                                    foreach ($email_templates_quote as $email_template) { ?>
                                    <option value="<?php echo $email_template->getEmail_template_id(); ?>"
                                        <?php $s->check_select($body['settings[email_quote_template]'], $email_template->getEmail_template_id()); ?>>
                                        <?php echo $email_template->getEmail_template_title(); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class='row'>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[pdf_quote_footer]">
                                <?php echo $translator->translate('pdf.quote.footer'); ?>
                            </label>                                                                                    
                            <?php $body['settings[pdf_quote_footer]'] = $s->getSetting('pdf_quote_footer'); ?>
                            <textarea name="settings[pdf_quote_footer]" id="settings[pdf_quote_footer]"
                                class="form-control no-margin"><?php echo $body['settings[pdf_quote_footer]']; ?></textarea>
                            <p class="help-block"><?php echo $translator->translate('pdf.quote.footer.hint'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
