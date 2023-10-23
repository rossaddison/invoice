<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 */
if (!empty($errors)) {
  foreach ($errors as $field => $error) {
    echo Alert::widget()->options(['class' => 'alert-danger'])->body(Html::encode($field . ':' . $error));
  }
}
?>
<h1><?= Html::encode($title) ?></h1>
<form id="DeliveryLocationForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div id="headerbar">
        <h1 class="headerbar-title"><?= $s->trans('dels_form'); ?></h1>
        <?php $response = $head->renderPartial('/invoice/layout/header_buttons', ['s' => $s, 'hide_submit_button' => false, 'hide_cancel_button' => false]); ?>
        <?php echo (string) $response->getBody(); ?><div id="content">
            <div class="row">
                <div class="mb3 form-group">
                    <input type="text" name="client_id" id="client_id" class="form-control" hidden
                           value="<?= Html::encode($body['client_id'] ?? $client_id); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="name"><?= $s->trans('name'); ?></label>
                    <input type="text" name="name" id="name" class="form-control"
                           value="<?= Html::encode($body['name'] ?? ''); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="address_1"><?= $s->trans('street_address'); ?></label>
                    <input type="text" name="address_1" id="address_1" class="form-control"
                           value="<?= Html::encode($body['address_1'] ?? ''); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="address_2"><?= $s->trans('street_address_2'); ?></label>
                    <input type="text" name="address_2" id="address_2" class="form-control"
                           value="<?= Html::encode($body['address_2'] ?? ''); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="city"><?= $s->trans('city'); ?></label>
                    <input type="text" name="city" id="city" class="form-control"
                           value="<?= Html::encode($body['city'] ?? ''); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="state"><?= $s->trans('state'); ?></label>
                    <input type="text" name="state" id="state" class="form-control"
                           value="<?= Html::encode($body['state'] ?? ''); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="zip"><?= $s->trans('zip'); ?></label>
                    <input type="text" name="zip" id="zip" class="form-control"
                           value="<?= Html::encode($body['zip'] ?? ''); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="country"><?= $s->trans('country'); ?></label>
                    <input type="text" name="country" id="country" class="form-control"
                           value="<?= Html::encode($body['country'] ?? ''); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="global_location_number"><?= Html::a($translator->translate('invoice.delivery.location.global.location.number'),'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-Delivery/cac-DeliveryLocation/cbc-ID/',['style'=>'text-decoration:none']); ?></label>
                    <input type="global_location_number" name="global_location_number" id="global_location_numbert" class="form-control"
                           value="<?= Html::encode($body['global_location_number'] ?? ''); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="electronic_address_scheme"><?= Html::a($translator->translate('invoice.delivery.location.electronic.address.scheme'), 'https://docs.peppol.eu/poacc/upgrade-3/codelist/eas',['style'=>'text-decoration:none']); ?></label>
                    <select name="electronic_address_scheme" id="electronic_address_scheme" class="form-control">
                        <?php
                        /**
                         * @see src/Invoice/Helpers/Peppol/PeppolArrays.php function electronic_address_scheme
                         * @see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-Delivery/cac-DeliveryLocation/cbc-ID/
                         * @var int $key
                         * @var array $value
                         */
                        foreach ($electronic_address_scheme as $key => $value) {
                          ?>
                          <option value="<?= $value['code']; ?>" <?php $s->check_select(($body['electronic_address_scheme'] ?? '0088'), $value['code']) ?>>
                              <?= $value['code'] . str_repeat("-", 10) . $value['description'] ?>
                          </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
</form>
