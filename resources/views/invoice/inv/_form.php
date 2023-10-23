<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;
use App\Invoice\Helpers\DateHelper;
use DateTimeImmutable;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 */
$datehelper = new DateHelper($s);

if (!empty($errors)) {
    foreach ($errors as $field => $error) {
        echo Alert::widget()->options(['class' => 'alert-danger'])->body(Html::encode($field . ':' . $error));
    }
}


$vat = $s->get_setting('enable_vat_registration') === '1' ? true : false;
echo $note_on_tax_point;
?>
<form class="row" id="InvForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div id="headerbar">
        <h1 class="headerbar-title"><?= $s->trans('invoices_form'); ?></h1>
        <?php
        $response = $head->renderPartial('invoice/layout/header_buttons', ['s' => $s, 'hide_submit_button' => false, 'hide_cancel_button' => false]);
        echo (string) $response->getBody();
        ?>
    </div>
    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label for="number"><?= $s->trans('invoice'); ?></label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <input type="text" name="number" id="number" class="form-control" required disabled value="<?= Html::encode($body['number'] ?? ''); ?>">
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label for="client_id"><?= $s->trans('client'); ?></label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <select name="client_id" id="client_id" class="form-control" required>
                    <option value=""><?= $s->trans('client'); ?></option>
                    <?php foreach ($clients as $client) { ?>
                        <option value="<?= $client->getClient_id(); ?>"
                        <?php $s->check_select(Html::encode($body['client_id'] ?? ''), $client->getClient_id()) ?>
                                ><?= $client->getClient_name(); ?></option>
                            <?php } ?>
                </select>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label for="group_id"><?= $s->trans('invoice_group'); ?>: </label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <select name="group_id" id="group_id"
                        class="form-control">
                            <?php foreach ($groups as $group) { ?>
                        <option value="<?php echo $group->getId(); ?>"
                                <?php $s->check_select(Html::encode($body['group_id'] ?? $s->get_setting('default_invoice_group')), $group->getId()); ?>>
                                    <?= $group->getName(); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs"
             <label for="delivery_id"><?= $translator->translate('invoice.invoice.delivery'); ?>: </label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <?php if ($delivery_count > 0) { ?>
                    <select name="delivery_id" id="delivery_id"
                            class="form-control">
                                <?php foreach ($deliverys as $delivery) { ?>
                            <option value="<?php echo $delivery->getId(); ?>"
                                    <?php echo $s->check_select(Html::encode($body['delivery_id'] ?? $delivery->getId()), $delivery->getId()); ?>>
                                        <?php echo ($delivery->getStart_date())->format($datehelper->style()) . ' ----- ' . ($delivery->getEnd_date())->format($datehelper->style()) . ' ---- ' . $s->get_setting('stand_in_code') . ' ---- ' . $current_stand_in_code_value; ?>
                            </option>
                        <?php } ?>
                    </select>
                    <?php if (null!==$delivery->getId()) { ?>
                    <span class="input-group-text">
                        <a href="<?= $urlGenerator->generate('delivery/edit', ['id'=> $delivery->getId()]); ?>"><i class="fa fa-pencil fa-fw"></i></a>
                    </span>  
                    <?php } ?>
                    <span class="input-group-text">
                        <a href="<?= $s->href('stand_in_code'); ?>" <?= $s->where('stand_in_code'); ?>><i class="fa fa-question fa-fw"></i></a>
                    </span>
                    <?php
                } else {
                    echo Html::a($translator->translate('invoice.invoice.delivery.add'), $urlGenerator->generate('delivery/add', ['inv_id' => $inv->getId()]), ['class' => 'btn btn-danger btn-lg mt-3']);
                }
                ?>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label for="delivery_location_id"><?= $translator->translate('invoice.invoice.delivery.location'); ?>: </label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <?php if ($del_count > 0) { ?>
                    <select name="delivery_location_id" id="delivery_location_id"
                            class="form-control">
                                <?php foreach ($dels as $del) { ?>
                            <option value="<?php echo $del->getId(); ?>"
                                    <?php echo $s->check_select(Html::encode($body['delivery_location_id'] ?? $del->getId()), $del->getId()); ?>>
                                        <?php echo $del->getAddress_1() . ', ' . $del->getAddress_2() . ', ' . $del->getCity() . ', ' . $del->getZip(); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <?php if (null!==$del->getId()) { ?>
                    <span class="input-group-text">
                        <a href="<?= $urlGenerator->generate('del/edit', ['id'=> $del->getId()]); ?>"><i class="fa fa-pencil fa-fw"></i></a>
                    </span>  
                    <?php } ?>
                    <?php
                } else {
                    echo Html::a($translator->translate('invoice.invoice.delivery.location.add'), $urlGenerator->generate('del/add', ['client_id' => $inv->getClient_id()]), ['class' => 'btn btn-danger btn-lg mt-3']);
                }
                ?>
            </div>
        </div>
    </div>


    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label for="contract_id"><?= $inv->getContract_id() === null ? $translator->translate('invoice.invoice.contract.none') : $translator->translate('invoice.invoice.contract'); ?>: </label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <?php if ($contract_count > 0) { ?>
                    <select name="contract_id" id="contract_id"
                            class="form-control">
                                <?php foreach ($contracts as $contract) { ?>
                            <option value="<?php echo $contract->getId(); ?>"
                                    <?php echo $s->check_select(Html::encode($body['contract_id'] ?? $contract->getId()), $contract->getId()); ?>>
                                        <?php echo $contract->getName() . " " . $contract->getReference(); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <?php
                } else {
                    echo Html::a($translator->translate('invoice.invoice.contract.add'), $urlGenerator->generate('contract/add', ['client_id' => $inv->getClient_id()]), ['class' => 'btn btn-info btn-lg mt-3']);
                }
                ?>
            </div>
        </div>
    </div>


    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label for="postaladdress_id"><?= $translator->translate('invoice.client.postaladdress.available'); ?>: </label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <?php if ($postal_address_count > 0) { ?>
                    <select name="postaladdress_id" id="postaladdress_id"
                            class="form-control">
                                <?php foreach ($postaladdresses as $postaladdress) { ?>
                            <option value="<?php echo $postaladdress->getId(); ?>"
                                    <?php echo $s->check_select(Html::encode($body['postaladdress_id'] ?? $postaladdress->getId()), $postaladdress->getId()); ?>>
                                        <?php echo $postaladdress->getStreet_name() . ', ' . $postaladdress->getAdditional_street_name() . ', ' . $postaladdress->getBuilding_number() . ', ' . $postaladdress->getCity_name(); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <?php
                } else {
                    echo Html::a($translator->translate('invoice.client.postaladdress.add'), $urlGenerator->generate('postaladdress/add', ['client_id' => $inv->getClient_id()]), ['class' => 'btn btn-warning btn-lg mt-3']);
                }
                ?>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label for="creditinvoice_parent_id"></label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <input type="text" name="creditinvoice_parent_id" id="creditinvoice_parent_id" class="form-control" hidden value="<?= Html::encode($body['creditinvoice_parent_id'] ?? 0); ?>">
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label form-label for="date_created"><?= $translator->translate('invoice.invoice.date.issued') . " (" . $datehelper->display() . ") "; ?></label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <input type="text" name="date_created" id="date_created" placeholder="<?= $datehelper->display(); ?>"
                       class="form-control input-sm datepicker"
                       value="<?= Html::encode($datehelper->date_from_mysql($body['date_created'] ?? new DateTimeImmutable('now'))); ?>">
                <span class="input-group-text">
                    <i class="fa fa-calendar fa-fw"></i>
                </span>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label form-label for="date_supplied"><?= $translator->translate('invoice.invoice.date.supplied') . " (" . $datehelper->display() . ") "; ?></label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <input type="text" name="date_supplied" id="date_supplied" placeholder="<?= $datehelper->display(); ?>"
                       class="form-control input-sm datepicker"
                       value="<?= Html::encode($datehelper->date_from_mysql($body['date_supplied'] ?? new DateTimeImmutable('now'))); ?>">
                <span class="input-group-text">
                    <i class="fa fa-calendar fa-fw"></i>
                </span>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label form-label for="date_tax_point"><?= $translator->translate('invoice.invoice.tax.point') . " (" . $datehelper->display() . ") "; ?></label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <input type="text" name="date_tax_point" id="date_tax_point" placeholder="<?= $datehelper->display(); ?>" disabled
                       class="form-control input-sm datepicker"
                       value="<?= Html::encode($datehelper->date_from_mysql($body['date_tax_point'] ?? new DateTimeImmutable('now'))); ?>">
                <span class="input-group-text">
                    <i class="fa fa-calendar fa-fw"></i>
                </span>
                <span class="input-group-text">
                    <a href="<?= $s->href('date_tax_point'); ?>" <?= $s->where('date_tax_point'); ?>><i class="fa fa-question fa-fw"></i></a>
                </span>
            </div>
        </div>
        
    </div>

    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label for="password"><?= $s->trans('password'); ?></label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <input type="text" name="password" id="password" class="form-control" value="<?= Html::encode($body['password'] ?? ''); ?>">
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label for="status_id">
                <?php echo $s->trans('status'); ?>
            </label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <select name="status_id" id="status_id" class="form-control">
                    <?php foreach ($inv_statuses as $key => $status) { ?>
                        <option value="<?php echo $key; ?>" <?php $s->check_select(Html::encode($body['status_id'] ?? ''), $key) ?>>
                            <?php echo $status['label']; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label for="payment_method">
                <?php echo $s->trans('payment_method'); ?>
            </label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <?php echo $body['is_read_only']; ?>
                <select name="payment_method" id="status_id" class="form-control"
                <?php
                if ($body['is_read_only'] == true && $body['status_id'] == 4) {
                    echo 'disabled="disabled"';
                }
                ?>>
                    <?php foreach ($payment_methods as $payment_method) { ?>
                        <option <?php
                        $s->check_select((string) $body['payment_method'] ?? '1',
                                $payment_method->getId())
                        ?>
                            value="<?= $payment_method->getId(); ?>">
                        <?= $payment_method->getName(); ?>
                        </option>
<?php } ?>
                </select>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label for="url_key">
<?= ($body['status_id'] ?? 1) > 1 ? $s->trans('guest_url') : ''; ?>
            </label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <input type="text" name="url_key" id="url_key" class="form-control" readonly value="<?= Html::encode($body['url_key'] ?? ''); ?>" <?= ($body['status_id'] ?? 1) == 1 ? 'hidden' : ''; ?>>
            </div>
        </div>
    </div>
<?php if ($vat === false) { ?>
        <div class="form-group">
            <div class="col-xs-12 col-sm-2 text-right text-left-xs">
                <label for="discount_amount"><?= $s->trans('discount'); ?></label>
            </div>
            <div class="col-xs-12 col-sm-6">
                <div class="input-group">
                    <input type="number" name="discount_amount" id="discount_amount" class="form-control"
                           value="<?= $s->format_amount($body['discount_amount'] ?? ''); ?>">
                    <span class="input-group-text"><?= $s->get_setting('currency_symbol'); ?></span>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="col-xs-12 col-sm-2 text-right text-left-xs">
                <label for="discount_percent"><?= $s->trans('discount'); ?></label>
            </div>
            <div class="col-xs-12 col-sm-6">
                <div class="input-group">
                    <input type="number" name="discount_percent" id="discount_percent" class="form-control"
                           value="<?= $s->format_amount($body['discount_percent'] ?? ''); ?>">
                    <span class="input-group-text">&percnt;</span>
                </div>
            </div>
        </div>
<?php } ?>
    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label for="terms"><?= $s->trans('terms'); ?></label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <select name="terms" id="terms" class="form-control">
                    <?php foreach ($payment_term_array as $payment_term) { ?>
                        <option <?php
                    $s->check_select((string) $body['terms'] ?? $payment_term,
                            $payment_term)
                    ?>
                            value="<?= $payment_term; ?>">
    <?= $payment_term; ?>
                        </option>
<?php } ?>
                </select>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label for="note">
<?= $translator->translate('invoice.invoice.note'); ?>
            </label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <input type="text" name="note" id="note" class="form-control" value="<?= Html::encode($body['note'] ?? ''); ?>">
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label for="document_description">
<?= $translator->translate('invoice.invoice.document.description'); ?>
            </label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <input type="text" name="document_description" id="document_description" class="form-control" value="<?= Html::encode($body['document_description'] ?? ''); ?>">
            </div>
        </div>
    </div>

        <?php foreach ($custom_fields as $custom_field): ?>
        <div class="form-group">
            <?=
            $cvH->print_field_for_form($inv_custom_values,
                    $custom_field,
                    // Custom values to fill drop down list if a dropdown box has been created
                    $custom_values,
                    // Class for div surrounding input
                    'col-xs-12 col-sm-6',
                    // Class surrounding above div
                    'form-group',
                    // Label class similar to above
                    'control-label');
            ?>
        </div>
<?php endforeach; ?>


    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label for="id" class="control-label"></label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <input type="hidden" name="id" id="id" class="form-control" value="<?= Html::encode($body['id'] ?? ''); ?>">
            </div>
        </div>
    </div>
</form>