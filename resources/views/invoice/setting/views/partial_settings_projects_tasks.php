<?php
    declare(strict_types=1);
    
    use Yiisoft\Html\Html;
?>
<?= Html::openTag('div', ['class' => 'row']); ?>
    <div class="col-xs-12 col-md-8 col-md-offset-2">

        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('i.projects'); ?>
            </div>
            <div class="panel-body">

                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[projects_enabled]">
                                <?= $translator->translate('i.enable_projects'); ?>
                            </label>
                            <?php $body['settings[projects_enabled]'] = $s->get_setting('projects_enabled');?>
                            <select name="settings[projects_enabled]" class="form-control" id="settings[projects_enabled]">
                                <option value="0">
                                    <?= $translator->translate('i.no'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[projects_enabled]'], '1'); ?>>
                                    <?= $translator->translate('i.yes'); ?>
                                </option>
                            </select>
                        </div>

                    </div>
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[default_hourly_rate]">
                                <?= $translator->translate('i.default_hourly_rate'); ?>
                            </label>
                            <?php $body['settings[default_hourly_rate]'] = $s->get_setting('default_hourly_rate');?>
                            <div class="input-group">
                                <input type="text" name="settings[default_hourly_rate]" id="settings[default_hourly_rate]"
                                    class="form-control amount"
                                    value="<?= $body['settings[default_hourly_rate]'] ? format_amount($body['settings[default_hourly_rate]']) : $body['settings[default_hourly_rate]']; ?>">
                                <span class="input-group-addon"><?= $s->get_setting('currency_symbol'); ?></span>
                                <input type="hidden" name="settings[default_hourly_rate_field_is_amount]" value="1">
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
