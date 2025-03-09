<?php
declare(strict_types=1);

use Yiisoft\Html\Tag\A;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $actionTestConnectionName
 * @var string $actionImportName
 * @var array $body
 * @psalm-var array<string, Stringable|null|scalar> $actionTestConnectionArguments
 * @psalm-var array<string, Stringable|null|scalar> $actionImportArguments
 */
?>
<div class ="row">
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('invoice.invoice.invoiceplane.tables'); ?>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-xs-8 col-md-4">
                        <div class="form-group">
                            <label for="settings[invoiceplane_database_name]"><?= $translator->translate('invoice.invoice.invoiceplane.database.name'); ?></label>
                            <?php $body['settings[invoiceplane_database_name]'] = $s->getSetting('invoiceplane_database_name'); ?>
                            <input type="text" name="settings[invoiceplane_database_name]" id="settings[invoiceplane_database_name]"
                                   class="form-control" required
                                   value="<?= $body['settings[invoiceplane_database_name]']; ?>">
                        </div>
                    </div>
                    <div class="col-xs-8 col-md-4">
                        <div class="form-group">
                            <label for="settings[invoiceplane_database_username]"><?= $translator->translate('invoice.invoice.invoiceplane.database.username'); ?></label>
                            <?php $body['settings[invoiceplane_database_username]'] = $s->getSetting('invoiceplane_database_username'); ?>
                            <input type="text" name="settings[invoiceplane_database_username]" id="settings[invoiceplane_database_username]"
                                   class="form-control" required
                                   value="<?= $body['settings[invoiceplane_database_username]']; ?>">
                        </div>
                    </div>
                    <div class="col-xs-8 col-md-4">
                        <div class="form-group">
                            <label for="settings[invoiceplane_database_password]"><?= $translator->translate('invoice.invoice.invoiceplane.database.password'); ?></label>
                            <?php $body['settings[invoiceplane_database_password]'] = $s->getSetting('invoiceplane_database_password'); ?>
                            <input type="password" name="settings[invoiceplane_database_password]" id="settings[invoiceplane_database_password]"
                                   class="form-control" required
                                   value="<?= $body['settings[invoiceplane_database_password]']; ?>">
                        </div>
                    </div>
                    <div>
                        <?= A::tag()
                            ->href($urlGenerator->generate($actionTestConnectionName, $actionTestConnectionArguments))
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
    ->href($urlGenerator->generate($actionImportName, $actionImportArguments))
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
                </div>
            </div>
        </div>
    </div>
</div>