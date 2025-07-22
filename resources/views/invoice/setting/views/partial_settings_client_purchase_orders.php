<?php
declare(strict_types=1);

use Yiisoft\Html\Html;

/*
 * @var App\Invoice\Group\GroupRepository $gR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 */
?>
<?php echo Html::openTag('div', ['class' => 'row']); ?>
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?php echo $translator->translate('salesorders'); ?>
            </div>
            <div class="panel-body">
                <?php echo Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[default_client_purchase_order_group]" <?php echo $s->where('default_invoice_group'); ?>>
                                <?php echo $translator->translate('salesorder.default.group'); ?>
                            </label>
                            <?php $body['settings[default_client_purchase_order_group]'] = $s->getSetting('default_client_purchase_order_group'); ?>
                            <select name="settings[default_client_purchase_order_group]" id="settings[default_client_purchase_order_group]"
                                class="form-control" >
                                <option value=""><?php echo $translator->translate('none'); ?></option>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\Group $invoice_group
                                     */
                                    foreach ($gR->findAllPreloaded() as $invoice_group) { ?>
                                    <option value="<?php echo $invoice_group->getId(); ?>"
                                        <?php $s->check_select($body['settings[default_client_purchase_order_group]'], $invoice_group->getId()); ?>>
                                        <?php echo $invoice_group->getName(); ?>
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