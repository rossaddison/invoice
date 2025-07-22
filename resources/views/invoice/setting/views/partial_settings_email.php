<?php
declare(strict_types=1);

use Yiisoft\Html\Html;

/*
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 */
?>
<?php echo Html::openTag('div', ['class' => 'row']); ?>
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?php echo $translator->translate('email'); ?>
            </div>
            <div class="panel-body">
                <?php echo Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[email_pdf_attachment]" <?php echo $s->where('email_pdf_attachment'); ?>>
                                <?php echo $translator->translate('email.pdf.attachment'); ?>
                            </label>
                            <?php $body['settings[email_pdf_attachment]'] = $s->getSetting('email_pdf_attachment'); ?>
                            <select name="settings[email_pdf_attachment]" id="settings[email_pdf_attachment]"
                                class="form-control" data-minimum-results-for-search="Infinity">
                                <option value="0" <?php $s->check_select($body['settings[email_pdf_attachment]'], '0'); ?>><?php echo $translator->translate('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[email_pdf_attachment]'], '1'); ?>>
                                    <?php echo $translator->translate('yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div>            
                </div>
            </div>
            <div class="panel-heading">
                <label for="email_send_method" <?php echo $s->where('email_send_method'); ?>>
                    <?php echo $translator->translate('email.send.method'); ?>
                </label>
                <!-- symfony mailer ie. yiimail has superceded phpmailer ie. replace phpmail with yiimail -->
                <!-- see MailerHelper mailer_configured function -->
                <select name="settings[email_send_method]" id="email_send_method" class="form-control">
                    <option value=""><?php echo $translator->translate('none'); ?></option>
                    <option value="symfony" <?php $s->check_select($s->getSetting('email_send_method'), 'symfony'); ?>>
                        <!-- Technically we are still using php to email so retain the following translation -->
                        <!-- The settings below are configured in the config/params.php file -->
                        <?php echo 'eSmtp: Symfony'; ?>
                    </option>
                </select>
            </div>            
            <div class="panel-body">
                <?php echo Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <div class="form-group"><?php echo Html::tag('h6', 'eSMTP Host: '.(string) $s->config_params()['esmtp_host']); ?></div>    
                            <div class="form-group"><?php echo Html::tag('h6', 'eSMTP Port: '.(string) $s->config_params()['esmtp_port']); ?></div>
                            <div class="form-group"><?php echo Html::tag('h6', 'eSMTP Schema: '.ucfirst((string) $s->config_params()['esmtp_scheme'])); ?></div>
                            <div class="form-group"><?php echo Html::tag('h6', 'Use SendMail: '.$s->config_params()['use_send_mail']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
