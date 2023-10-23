<?php
    declare(strict_types=1);
    
    use Yiisoft\Html\Html;
?>
<div class="row">
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $s->trans('email'); ?>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[email_pdf_attachment]" <?= $s->where('email_pdf_attachment'); ?>>
                                <?= $s->trans('email_pdf_attachment'); ?>
                            </label>
                            <?php $body['settings[email_pdf_attachment]'] = $s->get_setting('email_pdf_attachment'); ?>
                            <select name="settings[email_pdf_attachment]" id="settings[email_pdf_attachment]"
                                class="form-control" data-minimum-results-for-search="Infinity">
                                <option value="0" <?php $s->check_select($body['settings[email_pdf_attachment]'], '0'); ?>><?= $s->trans('no'); ?></option>
                                <option value="1" <?php $s->check_select($body['settings[email_pdf_attachment]'], '1'); ?>>
                                    <?= $s->trans('yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div>            
                </div>
            </div>
            <div class="panel-heading">
                <label for="email_send_method" <?= $s->where('email_send_method'); ?>>
                    <?= $s->trans('email_send_method'); ?>
                </label>
                <!-- symfony mailer ie. yiimail has superceded phpmailer ie. replace phpmail with yiimail -->
                <!-- see MailerHelper mailer_configured function -->
                <select name="settings[email_send_method]" id="email_send_method" class="form-control">
                    <option value=""><?= $s->trans('none'); ?></option>
                    <option value="symfony" <?= $s->check_select($s->get_setting('email_send_method'), 'symfony'); ?>>
                        <!-- Technically we are still using php to email so retain the following translation -->
                        <!-- The settings below are configured in the config/params.php file -->
                        <?= 'eSmtp: Symfony'; ?>
                    </option>
                </select>
            </div>            
            <div class="panel-body">
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <div class="form-group"><?= Html::tag('label', 'eSMTP Host: '.$s->config_params()['esmtp_host']); ?></div>    
                            <div class="form-group"><?= Html::tag('label', 'eSMTP Port: '.$s->config_params()['esmtp_port']); ?></div>
                            <div class="form-group"><?= Html::tag('label', 'eSMTP Schema: '. ucfirst($s->config_params()['esmtp_scheme'])); ?></div>
                            <div class="form-group"><?= Html::tag('label', 'Use SendMail: '. $s->config_params()['use_send_mail']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
