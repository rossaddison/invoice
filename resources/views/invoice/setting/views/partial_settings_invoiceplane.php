<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
?>
<?= Html::openTag('div', ['class' => 'row']); ?>
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('invoice.invoice.invoiceplane.tables'); ?>
            </div>
            <div class="panel-body">
                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-8 col-md-4">
                        <div class="form-group">
                            <label for="settings[invoiceplane_database_name]"><?= $translator->translate('invoice.invoice.invoiceplane.database.name'); ?></label>
                            <?php $body['settings[invoiceplane_database_name]'] = $s->get_setting('invoiceplane_database_name'); ?>
                            <input type="text" name="settings[invoiceplane_database_name]" id="invoiceplane_database_name_id"
                                   class="form-control" required
                                   value="<?= $body['settings[invoiceplane_database_name]']; ?>">
                        </div>
                    </div>
                    <div class="col-xs-8 col-md-4">
                        <div class="form-group">
                            <label for="settings[invoiceplane_database_username]"><?= $translator->translate('invoice.invoice.invoiceplane.database.username'); ?></label>
                            <?php $body['settings[invoiceplane_database_username]'] = $s->get_setting('invoiceplane_database_username'); ?>
                            <input type="text" name="settings[invoiceplane_database_username]" id="invoiceplane_database_username_id"
                                   class="form-control" required
                                   value="<?= $body['settings[invoiceplane_database_username]']; ?>">
                        </div>
                    </div>
                    <div class="col-xs-8 col-md-4">
                        <div class="form-group">
                            <label for="settings[invoiceplane_database_password]"><?= $translator->translate('invoice.invoice.invoiceplane.database.password'); ?></label>
                            <?php $body['settings[invoiceplane_database_password]'] = $s->get_setting('invoiceplane_database_password'); ?>
                            <input type="password" name="settings[invoiceplane_database_password]" id="invoiceplane_database_password_id"
                                   class="form-control" required
                                   value="<?= $body['settings[invoiceplane_database_password]']; ?>">
                        </div>
                    </div>
                    <div>
                        <?= A::tag()
                            ->href($urlGenerator->generate(...$actionTestConnection))
                            ->id('btn-reset')
                            ->addAttributes(['type' => 'reset'])
                            ->addClass('btn btn-primary me-1')                   
                            ->content($translator->translate('invoice.invoice.invoiceplane.import'))
                            ->render();
                        ?>
                    </div>
                    <br>
                    <br>
                    <div>
                        <?= A::tag()
                            ->href($urlGenerator->generate(...$actionImport))
                            ->id('btn-reset')
                            ->addAttributes([
                                'type' => 'submit',
                                'onclick' => 'return confirm("'. $translator->translate('invoice.invoice.invoiceplane.import.proceed.alert'). '")',
                            ])
                            ->addClass('btn btn-success me-1')                   
                            ->content($translator->translate('invoice.invoice.invoiceplane.import.proceed'))
                            ->render();
                        ?>
                    </div>
                <?= Html::closeTag('div', ['class' => 'row']); ?>
            </div>
        </div>
    </div>
</div>