<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;
use App\Widget\LabelSwitch;

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
<form id="ClientPeppolForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div id="headerbar">
        <h1 class="headerbar-title"><?= Html::a($translator->translate('invoice.client.peppol.clientpeppols_form'), 'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-AccountingCustomerParty/'); ?></h1>
        <?php echo $buttons; ?><div id="content">
        <?=
        LabelSwitch::checkbox(
          'client-peppol-label-switch',
          $setting,
          $translator->translate('invoice.peppol.label.switch.on'),
          $translator->translate('invoice.peppol.label.switch.off'),
          'client-peppol-label-switch-id',
          '16'
        );
        ?>
            <div class="row">
                <div class="mb3 form-group">
                    <input type="text" name="client_id" id="client_id" class="form-control" hidden
                           value="<?= Html::encode($body['client_id'] ?? $client_id); ?>">
                </div>
                <div class="mb3 form-group">
                    <input type="text" name="id" id="id" class="form-control" hidden
                           value="<?= Html::encode($body['id'] ?? ''); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="endpointid"><?= $translator->translate('invoice.client.peppol.endpointid'); ?></label>
                    <input type="text" name="endpointid" id="endpointid" class="form-control" required
                           value="<?= Html::encode($body['endpointid'] ?? ($defaults ? $pep['endpointid']['eg'] : '')); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="endpointid_schemeid"><?= $translator->translate('invoice.client.peppol.endpointid_schemeid') . $translator->translate('invoice.peppol.optional'); ?></label>
                    <select name="endpointid_schemeid" id="endpointid_schemeid" class="form-control" required>
                        <?php
                        /**
                         * Search $customer_endpointID_schemeID = $party['EndPointID']['schemeID'] ?? ''; in PeppolHelper.php
                         * @see src/Invoice/Helpers/Peppol/PeppolArrays.php function electronic_address_scheme
                         * @see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-AccountingCustomerParty/cac-Party/cbc-EndpointID/schemeID/
                         * @var int $key
                         * @var array $value
                         */
                        foreach ($electronic_address_scheme as $key => $value) {
                          ?>
                          <option value="<?= $value['code']; ?>" <?php $s->check_select($body['endpointid_schemeid'] ?? ($defaults ? $pep['endpointid_schemeid']['eg'] : '0088'), $value['code']) ?>>
                              <?= $value['code'] . str_repeat("-", 10) . $value['description'] ?>
                          </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="mb3 form-group">
                    <label for="identificationid"><?= $translator->translate('invoice.client.peppol.identificationid'); ?></label>
                    <input type="text" name="identificationid" id="identificationid" class="form-control" required
                           value="<?= Html::encode($body['identificationid'] ?? ($defaults ? $pep['identificationid']['eg'] : '')); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="identificationid_schemeid"><?= $translator->translate('invoice.client.peppol.identificationid_schemeid') . $translator->translate('invoice.peppol.optional'); ?></label>
                    <input type="text" name="identificationid_schemeid" id="identificationid_schemeid" class="form-control" required
                           value="<?= Html::encode($body['identificationid_schemeid'] ?? ($defaults ? $pep['identificationid_schemeid']['eg'] : '')); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="taxschemecompanyid"><?= $translator->translate('invoice.client.peppol.taxschemecompanyid'); ?></label>
                    <input type="text" name="taxschemecompanyid" id="taxschemecompanyid" class="form-control" required
                           value="<?= Html::encode($body['taxschemecompanyid'] ?? ($defaults ? $pep['taxschemecompanyid']['eg'] : '')); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="taxschemeid"><?= $translator->translate('invoice.client.peppol.taxschemeid'); ?>
                    </label>
                    <select name="taxschemeid" id="taxschemeid" class="form-control" required>
                        <?php
                        /**
                         * @var int $key
                         * @var array $value
                         */
                        foreach ($receiver_identifier_array as $key => $value) {
                          ?>

                          <option value="<?= $key; ?>" <?php $s->check_select($body['taxschemeid'] ?? ($defaults ? $pep['taxschemeid']['eg'] : ''), $key) ?>>
                              <?=
                              ucfirst($value['region']
                                . str_repeat("&nbsp;", 2)
                                . str_repeat("-", 10)
                                . str_repeat("&nbsp;", 2) .
                                $value['country']
                                . str_repeat("&nbsp;", 2)
                                . str_repeat("-", 10)
                                . str_repeat("&nbsp;", 2) .
                                (!empty($value['tax']) ? $value['tax'] : $translator->translate('invoice.storecove.not.available'))
                              );
                              ?>
                          </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="mb3 form-group">
                    <label for="legal_entity_registration_name"><?= $translator->translate('invoice.client.peppol.legal_entity_registration_name'); ?></label>
                    <input type="text" name="legal_entity_registration_name" id="legal_entity_registration_name" class="form-control" required
                           value="<?= Html::encode($body['legal_entity_registration_name'] ?? ($defaults ? $pep['legal_entity_registration_name']['eg'] : '')); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="legal_entity_companyid"><?= $translator->translate('invoice.client.peppol.legal_entity_companyid'); ?></label>
                    <select name="legal_entity_companyid" id="legal_entity_companyid" class="form-control" required>
                        <?php
                        /**
                         * @var int $key
                         * @var array $value
                         */
                        foreach ($iso_6523_array as $key => $value) {
                          ?>
                          <option value="<?= $value['Id']; ?>" <?php $s->check_select($body['legal_entity_companyid'] ?? '', $value['Id']) ?>>
                              <?=
                              ucfirst($value['Id']
                                . str_repeat("&nbsp;", 2)
                                . str_repeat("-", 10)
                                . str_repeat("&nbsp;", 2) .
                                $value['Name']
                                . str_repeat("&nbsp;", 2)
                                . str_repeat("-", 10)
                                . str_repeat("&nbsp;", 2) .
                                $value['Description']
                              );
                              ?>
                          </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="mb3 form-group">
                    <label for="legal_entity_companyid_schemeid"><?= $translator->translate('invoice.client.peppol.legal_entity_companyid_schemeid') . $translator->translate('invoice.peppol.optional'); ?></label>
                    <select name="legal_entity_companyid_schemeid" id="legal_entity_companyid_schemeid" class="form-control" required>
                        <?php
                        /**
                         * @var int $key
                         * @var array $value
                         */
                        foreach ($iso_6523_array as $key => $value) {
                          ?>
                          <option value="<?= $value['Id']; ?>" <?php $s->check_select($body['legal_entity_companyid_schemeid'] ?? '', $value['Id']) ?>>
                              <?=
                              ucfirst($value['Id']
                                . str_repeat("&nbsp;", 2)
                                . str_repeat("-", 10)
                                . str_repeat("&nbsp;", 2) .
                                $value['Name']
                                . str_repeat("&nbsp;", 2)
                                . str_repeat("-", 10)
                                . str_repeat("&nbsp;", 2) .
                                $value['Description']
                              );
                              ?>
                          </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="mb3 form-group">
                    <label for="legal_entity_company_legal_form"><?= $translator->translate('invoice.client.peppol.legal_entity_company_legal_form'); ?></label>
                    <input type="text" name="legal_entity_company_legal_form" id="legal_entity_company_legal_form" class="form-control" required
                           value="<?= Html::encode($body['legal_entity_company_legal_form'] ?? ($defaults ? $pep['legal_entity_company_legal_form']['eg'] : '')); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="financial_institution_branchid"><?= $translator->translate('invoice.client.peppol.financial_institution_branchid'); ?></label>
                    <input type="text" name="financial_institution_branchid" id="financial_institution_branchid" class="form-control" required
                           value="<?= Html::encode($body['financial_institution_branchid'] ?? ($defaults ? $pep['financial_institution_branchid']['eg'] : '')); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="accounting_cost"><?= $translator->translate('invoice.client.peppol.accounting_cost'); ?></label>
                    <input type="text" name="accounting_cost" id="accounting_cost" class="form-control" required
                           value="<?= Html::encode($body['accounting_cost'] ?? ($defaults ? $pep['accounting_cost']['eg'] : '')); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="buyer_reference"><?= $translator->translate('invoice.client.peppol.buyer_reference.default') . ' ' . $translator->translate('invoice.client.peppol.buyer_reference.example'); ?></label>
                    <input type="text" name="buyer_reference" id="buyer_reference" class="form-control" required
                           value="<?= Html::encode($body['buyer_reference'] ?? ($defaults ? $pep['buyer_reference']['eg'] : '')); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="supplier_assigned_accountid"><?= $translator->translate('invoice.client.peppol.supplier.assigned.account.id') . ' ' . $translator->translate('invoice.client.peppol.buyer_reference.example'); ?></label>
                    <input type="text" name="supplier_assigned_accountid" id="supplier_assigned_accountid" class="form-control" required
                           value="<?= Html::encode($body['supplier_assigned_accountid'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>
</form>
