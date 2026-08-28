<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use App\Widget\LabelSwitch;
use Yiisoft\VarDumper\VarDumper;

/**
 * Related logic: see App\Invoice\ClientPeppol\ClientPeppolController.php
 * function add and function edit
 *
 * @var App\Invoice\ClientPeppol\ClientPeppolForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @psalm-var array<array-key, array{Id: string, Name: string, Description: string}> $electronic_address_scheme
 * @psalm-var array<array-key, array{Id: string, Name: string, Description: string}> $iso_6523_array
 * @var array $pep
 * @var array $pep['endpointid']
 * @var array $pep['endpointid_schemeid']
 * @var array $pep['identificationid']
 * @var array $pep['identificationid_schemeid']
 * @var array $pep['taxschemecompanyid']
 * @var array $pep['taxschemeid']
 * @var array $pep['legal_entity_registration_name']
 * @var array $pep['legal_entity_companyid']
 * @var array $pep['legal_entity_companyid_schemeid']
 * @var array $pep['legal_entity_company_legal_form']
 * @var array $pep['financial_institution_branchid']
 * @var array $pep['accounting_cost']
 * @var array $pep['buyer_reference']
 * @var array $pep['supplier_assigned_accountid']
 * @psalm-var array<array-key, array{region: string, country: string, tax?: string}> $receiver_identifier_array
 * @var bool $defaults
 * @var int $client_id
 * @var string $actionName
 * @var string $csrf
 * @var string $setting
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 */

?>

<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', [
    'class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', [
    'class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
    <?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
        <?= Html::encode($title) ?>
    <?= Html::closeTag('h1'); ?>
        <?=  new Form()
            ->post($urlGenerator->generate($actionName, $actionArguments))
            ->enctypeMultipartFormData()
            ->csrf($csrf)
            ->id('ClientPeppolForm')
            ->open() ?>
                <?= Html::openTag('div', ['class' => 'container']); ?>
                    <?= Html::openTag('div', ['class' => 'row']); ?>
                        <?= Html::openTag('div', ['class' => 'col card mb-3']); ?>
                            <?= Html::openTag('div', ['class' => 'card-header']); ?>
                                <?php
                                LabelSwitch::checkbox(
                                    'client-peppol-label-switch',
                                    $setting,
                                    $translator->translate(
                                            'peppol.label.switch.on'),
                                    $translator->translate(
                                            'peppol.label.switch.off'),
                                    'client-peppol-label-switch-id',
                                    '16',
                                );
                                ?>
                                <?= Html::openTag('div'); ?>
                                    <?= Field::errorSummary($form)
                                        ->errors($errors)
                                        ->header($translator->translate(
                                                'error.summary'))
                                        ->onlyCommonErrors()
                                    ?>
                                <?= Html::closeTag('div'); ?>

                                <?= Field::hidden($form, 'client_id')
                                    ->value($form->getClientId() ?? $client_id)
                                ?>

                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                  <?= Field::email($form, 'endpointid')
                    ->label($translator->translate('client.peppol.endpointid'))
                    ->addInputAttributes([
                        'class' => 'form-control form-control-lg',
                        'id' => 'endpointid',
                        'placeholder' => $translator->translate(
                                'client.peppol.endpointid'),
                        'maxlength' => 100,
                    ])
                    ->value($form->getEndpointid() !== ''
                            && $form->getEndpointid() !== null ?
                            $form->getEndpointid() : ($defaults ?
                                    $pep['endpointid']['eg'] : ''))
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>

                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                  <?= Html::tag('small',
                      'PeppolArrays::electronicAddressScheme() ← DownloadedXml/eas.xml',
                      ['class' => 'text-muted d-block mb-1']); ?>
                  <?= Field::select($form, 'endpointid_schemeid')
                    ->label($translator->translate(
                            'client.peppol.endpointid.schemeid')
                            . $translator->translate('peppol.optional'))
                    ->addInputAttributes([
                        'class' => 'form-control form-control-lg',
                        'id' => 'endpointid_schemeid',
                    ])
                    ->value($form->getEndpointidSchemeid() !== ''
                            && $form->getEndpointidSchemeid() !== null ?
                            $form->getEndpointidSchemeid() :
                        ($defaults ? $pep['endpointid_schemeid']['eg'] : '0088'))
                    ->optionsData(array_combine(
                        /** @var list<string> */
                        array_column($electronic_address_scheme, 'Id'),
                        array_map(
                            /** @param array{Id: string, Name: string, Description: string} $v */
                            fn($v) => $v['Id']
                                . str_repeat("-", 10) . $v['Name'],
                            $electronic_address_scheme
                        )
                    ))
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>

                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                  <?= Field::text($form, 'identificationid')
                    ->label($translator->translate(
                            'client.peppol.identificationid'))
                    ->addInputAttributes([
                        'class' => 'form-control form-control-lg',
                        'id' => 'identificationid',
                        'maxlength' => 100,
                    ])
                    ->value($form->getIdentificationid() !== ''
                            && $form->getIdentificationid() !== null ?
                            $form->getIdentificationid() :
                        ($defaults ? $pep['identificationid']['eg'] : ''))
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>

                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                  <?= Field::text($form, 'identificationid_schemeid')
                    ->label($translator->translate(
                            'client.peppol.identificationid.schemeid')
                            . $translator->translate('peppol.optional'))
                    ->addInputAttributes([
                        'class' => 'form-control form-control-lg',
                        'id' => 'identificationid_schemeid',
                        'maxlength' => 4,
                    ])
                    ->value($form->getIdentificationidSchemeid() !== ''
                            && $form->getIdentificationidSchemeid() !== null ?
                            $form->getIdentificationidSchemeid() :
                        ($defaults ?
                                $pep['identificationid_schemeid']['eg'] : ''))
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>

                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                  <?= Field::text($form, 'taxschemecompanyid')
                    ->label($translator->translate(
                            'client.peppol.taxschemecompanyid'))
                    ->addInputAttributes([
                        'class' => 'form-control form-control-lg',
                        'id' => 'taxschemecompanyid',
                        'maxlength' => 100,
                    ])
                    ->value($form->getTaxschemecompanyid() !== ''
                            && $form->getTaxschemecompanyid() !== null ?
                            $form->getTaxschemecompanyid() : ($defaults ?
                                    $pep['taxschemecompanyid']['eg'] : ''))
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>

                <?php
                // Built via foreach, not array_map(fn, array_keys($x), $x) —
                // that form always re-indexes its result 0,1,2,..., so the
                // <option value="..."> submitted was this array's *position*
                // in the list, not a real Peppol tax scheme code. Confirmed
                // live: picking the first-listed row (the -1 legend entry
                // below, itself never meant to be selectable) posted
                // taxschemeid=0, which OrderResponseAdvancedService.php then
                // writes straight into UBL's cac:TaxScheme/cbc:ID with no
                // lookup step in between — unlike storecove_sender_identifier
                // (Settings > Storecove), which deliberately stores this same
                // array's raw key because StoreCoveHelper.php looks it back
                // up before use. No such lookup exists for this field, so it
                // has to store the real code (row's 'tax' value) directly.
                // Rows with no tax scheme at all ('tax' => '') are excluded
                // rather than offered as "N/A" — selecting one would submit
                // that literal placeholder text as if it were a real code.
                $taxSchemeOptions = [];
                foreach ($receiver_identifier_array as $rowKey => $row) {
                    if ($rowKey === -1 || !isset($row['tax']) || $row['tax'] === '') {
                        continue;
                    }
                    $taxSchemeOptions[$row['tax']] = ucfirst(
                        $row['region'] .
                        str_repeat(" ", 2) .
                        str_repeat("-", 10) .
                        str_repeat(" ", 2) .
                        $row['country'] .
                        str_repeat(" ", 2) .
                        str_repeat("-", 10) .
                        str_repeat(" ", 2) .
                        $row['tax']
                    );
                }
                ?>
                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                  <?= Html::tag('small',
                      'StoreCoveArrays::storeCoveReceiverIdentifierArray()',
                      ['class' => 'text-muted d-block mb-1']); ?>
                  <?= Field::select($form, 'taxschemeid')
                    ->label($translator->translate('client.peppol.taxschemeid'))
                    ->addInputAttributes([
                        'class' => 'form-control form-control-lg',
                        'id' => 'taxschemeid',
                    ])
                    ->value($form->getTaxschemeid() !== ''
                            && $form->getTaxschemeid() !== null ?
                            $form->getTaxschemeid() :
                                ($defaults ? $pep['taxschemeid']['eg'] : ''))
                    ->optionsData($taxSchemeOptions)
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>

                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                  <?= Field::text($form, 'legal_entity_registration_name')
                    ->label($translator->translate(
                            'client.peppol.legal.entity.registration.name'))
                    ->addInputAttributes([
                        'class' => 'form-control form-control-lg',
                        'id' => 'legal_entity_registration_name',
                        'maxlength' => 100,
                    ])
                    ->value($form->getLegalEntityRegistrationName() !== ''
                        && $form->getLegalEntityRegistrationName() !== null ?
                            $form->getLegalEntityRegistrationName() :
                ($defaults ? $pep['legal_entity_registration_name']['eg'] : ''))
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>

                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                  <?= Html::tag('small',
                      'PeppolArrays::getIso6523Icd() ← DownloadedXml/icd.xml',
                      ['class' => 'text-muted d-block mb-1']); ?>
                  <?= Field::select($form, 'legal_entity_companyid')
                    ->label($translator->translate(
                                        'client.peppol.legal.entity.companyid'))
                    ->addInputAttributes([
                        'class' => 'form-control form-control-lg',
                        'id' => 'legal_entity_companyid',
                    ])
                    ->value($form->getLegalEntityCompanyid() !== ''
                            && $form->getLegalEntityCompanyid() !== null ?
                                        $form->getLegalEntityCompanyid() : '')
                    ->optionsData(array_combine(
                        /** @var list<string> */
                        array_column($iso_6523_array, 'Id'),
                        array_map(
                            /** @param array{Id: string, Name: string, Description: string} $v */
                            fn($v) => ucfirst(
                                $v['Id'] .
                                str_repeat(" ", 2) .
                                str_repeat("-", 10) .
                                str_repeat(" ", 2) .
                                $v['Name'] .
                                str_repeat(" ", 2) .
                                str_repeat("-", 10) .
                                str_repeat(" ", 2) .
                                $v['Description']
                            ),
                            $iso_6523_array
                        )
                    ))
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>

                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                  <?= Html::tag('small',
                      'PeppolArrays::getIso6523Icd() ← DownloadedXml/icd.xml',
                      ['class' => 'text-muted d-block mb-1']); ?>
                  <?= Field::select($form, 'legal_entity_companyid_schemeid')
                    ->label($translator->translate(
                            'client.peppol.legal.entity.companyid.schemeid')
                            . $translator->translate('peppol.optional'))
                    ->addInputAttributes([
                        'class' => 'form-control form-control-lg',
                        'id' => 'legal_entity_companyid_schemeid',
                    ])
                    ->value($form->getLegalEntityCompanyidSchemeid() !== '' &&
                        $form->getLegalEntityCompanyidSchemeid() !== null ?
                            $form->getLegalEntityCompanyidSchemeid() : '')
                    ->optionsData(array_combine(
                        /** @var list<string> */
                        array_column($iso_6523_array, 'Id'),
                        array_map(
                            /** @param array{Id: string, Name: string, Description: string} $v */
                            fn($v) => ucfirst(
                                $v['Id'] .
                                str_repeat(" ", 2) .
                                str_repeat("-", 10) .
                                str_repeat(" ", 2) .
                                $v['Name'] .
                                str_repeat(" ", 2) .
                                str_repeat("-", 10) .
                                str_repeat(" ", 2) .
                                $v['Description']
                            ),
                            $iso_6523_array
                        )
                    ))
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>

                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                  <?= Field::text($form, 'legal_entity_company_legal_form')
                    ->label($translator->translate(
                            'client.peppol.legal.entity.company.legal.form'))
                    ->addInputAttributes([
                        'class' => 'form-control form-control-lg',
                        'id' => 'legal_entity_company_legal_form',
                        'maxlength' => 50,
                    ])
                    ->value($form->getLegalEntityCompanyLegalForm() !== '' &&
                        $form->getLegalEntityCompanyLegalForm() !== null ?
                            $form->getLegalEntityCompanyLegalForm() :
               ($defaults ? $pep['legal_entity_company_legal_form']['eg'] : ''))
                    ->required(true)
                    ->hint($translator->translate(
                                                'hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>

                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                  <?= Field::text($form, 'financial_institution_branchid')
                    ->label($translator->translate(
                                'client.peppol.financial.institution.branchid'))
                    ->addInputAttributes([
                        'class' => 'form-control form-control-lg',
                        'id' => 'financial_institution_branchid',
                        'maxlength' => 20,
                    ])
                    ->value($form->getFinancialInstitutionBranchid() !== ''
                            && $form->getFinancialInstitutionBranchid()
                                !== null ?
                            $form->getFinancialInstitutionBranchid() :
                ($defaults ? $pep['financial_institution_branchid']['eg'] : ''))
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>

                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                  <?= Field::text($form, 'accounting_cost')
                    ->label($translator->translate(
                                                'client.peppol.accounting.cost'))
                    ->addInputAttributes([
                        'class' => 'form-control form-control-lg',
                        'id' => 'accounting_cost',
                        'maxlength' => 30,
                    ])
                    ->value($form->getAccountingCost() !== '' &&
                            $form->getAccountingCost() !== null ?
                            $form->getAccountingCost() : ($defaults ?
                                    $pep['accounting_cost']['eg'] : ''))
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>

                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                  <?= Field::text($form, 'buyer_reference')
                    ->label($translator->translate(
                            'client.peppol.buyer.reference.default')
                            . ' ' . $translator->translate(
                                    'client.peppol.buyer.reference.example'))
                    ->addInputAttributes([
                        'class' => 'form-control form-control-lg',
                        'id' => 'buyer_reference',
                        'maxlength' => 20,
                    ])
                    ->value($form->getBuyerReference() !== '' &&
                            $form->getBuyerReference() !== null ?
                            $form->getBuyerReference() :
                        ($defaults ? $pep['buyer_reference']['eg'] : ''))
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>

                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                  <?= Field::text($form, 'supplier_assigned_accountid')
                    ->label($translator->translate(
                            'client.peppol.supplier.assigned.account.id')
                            . ' ' . $translator->translate(
                                    'client.peppol.buyer.reference.example'))
                    ->addInputAttributes([
                        'class' => 'form-control form-control-lg',
                        'id' => 'supplier_assigned_accountid',
                        'maxlength' => 20,
                    ])
                    ->value($form->getSupplierAssignedAccountId() !== ''
                            && $form->getSupplierAssignedAccountId() !== null ?
                            $form->getSupplierAssignedAccountId() : '')
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                                <?= Html::closeTag('div'); ?>
                            <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('div'); ?>
                    <?= Html::closeTag('div'); ?>
                <?= Html::closeTag('div'); ?>
        <?= $button::backSave(); ?>
    <?= Html::closeTag('div'); ?>

<?= Html::closeTag('div'); ?>

<?= Html::closeTag('form'); ?>

<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
