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
 * @psalm-var array<array-key, array{code: string, description: string}> $electronic_address_scheme
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

<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', [
    'class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', [
    'class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
    <?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
        <?= Html::encode($title) ?>
    <?= Html::closeTag('h1'); ?>
        <?= Form::tag()
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
                                    ->value($form->getClient_id() ?? $client_id)
                                ?>
                                
                                <?= Field::hidden($form, 'id')
                                    ->value($form->getId() ?? '')
                                ?>
                
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::email($form, 'endpointid')
                    ->label($translator->translate('client.peppol.endpointid'))
                    ->addInputAttributes([
                        'class' => 'form-control',
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
                
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::select($form, 'endpointid_schemeid')
                    ->label($translator->translate(
                            'client.peppol.endpointid.schemeid')
                            . $translator->translate('peppol.optional'))
                    ->addInputAttributes([
                        'class' => 'form-control',
                        'id' => 'endpointid_schemeid',
                    ])
                    ->value($form->getEndpointid_schemeid() !== ''
                            && $form->getEndpointid_schemeid() !== null ?
                            $form->getEndpointid_schemeid() :
                        ($defaults ? $pep['endpointid_schemeid']['eg'] : '0088'))
                    ->optionsData(array_combine(
                        /** @var list<string> */
                        array_column($electronic_address_scheme, 'code'),
                        array_map(
                            /** @param array{code: string, description: string} $v */
                            fn($v) => $v['code']
                                . str_repeat("-", 10) . $v['description'],
                            $electronic_address_scheme
                        )
                    ))
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>
                
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::text($form, 'identificationid')
                    ->label($translator->translate(
                            'client.peppol.identificationid'))
                    ->addInputAttributes([
                        'class' => 'form-control',
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
                
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::text($form, 'identificationid_schemeid')
                    ->label($translator->translate(
                            'client.peppol.identificationid.schemeid')
                            . $translator->translate('peppol.optional'))
                    ->addInputAttributes([
                        'class' => 'form-control',
                        'id' => 'identificationid_schemeid',
                        'maxlength' => 4,
                    ])
                    ->value($form->getIdentificationid_schemeid() !== ''
                            && $form->getIdentificationid_schemeid() !== null ?
                            $form->getIdentificationid_schemeid() :
                        ($defaults ?
                                $pep['identificationid_schemeid']['eg'] : ''))
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>
                
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::text($form, 'taxschemecompanyid')
                    ->label($translator->translate(
                            'client.peppol.taxschemecompanyid'))
                    ->addInputAttributes([
                        'class' => 'form-control',
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
                
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::select($form, 'taxschemeid')
                    ->label($translator->translate('client.peppol.taxschemeid'))
                    ->addInputAttributes([
                        'class' => 'form-control',
                        'id' => 'taxschemeid',
                    ])
                    ->value($form->getTaxschemeid() !== ''
                            && $form->getTaxschemeid() !== null ?
                            $form->getTaxschemeid() :
                                ($defaults ? $pep['taxschemeid']['eg'] : ''))
                    ->optionsData(array_map(
                        /**
                         * @param array{region: string, country: string, tax?: string} $value
                         */
                        function($key, $value) use ($translator) {
                            return ucfirst(
                                $value['region'] .
                                str_repeat(" ", 2) .
                                str_repeat("-", 10) .
                                str_repeat(" ", 2) .
                                $value['country'] .
                                str_repeat(" ", 2) .
                                str_repeat("-", 10) .
                                str_repeat(" ", 2) .
                                (isset($value['tax']) && $value['tax'] !== '' ?
                                        $value['tax'] : $translator->translate(
                                                'storecove.not.available'))
                            );
                        },
                        array_keys($receiver_identifier_array),
                        $receiver_identifier_array
                    ))
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>
                
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::text($form, 'legal_entity_registration_name')
                    ->label($translator->translate(
                            'client.peppol.legal.entity.registration.name'))
                    ->addInputAttributes([
                        'class' => 'form-control',
                        'id' => 'legal_entity_registration_name',
                        'maxlength' => 100,
                    ])
                    ->value($form->getLegal_entity_registration_name() !== ''
                        && $form->getLegal_entity_registration_name() !== null ?
                            $form->getLegal_entity_registration_name() :
                ($defaults ? $pep['legal_entity_registration_name']['eg'] : ''))
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>
                
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::select($form, 'legal_entity_companyid')
                    ->label($translator->translate(
                                        'client.peppol.legal.entity.companyid'))
                    ->addInputAttributes([
                        'class' => 'form-control',
                        'id' => 'legal_entity_companyid',
                    ])
                    ->value($form->getLegal_entity_companyid() !== ''
                            && $form->getLegal_entity_companyid() !== null ?
                                        $form->getLegal_entity_companyid() : '')
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
                
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::select($form, 'legal_entity_companyid_schemeid')
                    ->label($translator->translate(
                            'client.peppol.legal.entity.companyid.schemeid')
                            . $translator->translate('peppol.optional'))
                    ->addInputAttributes([
                        'class' => 'form-control',
                        'id' => 'legal_entity_companyid_schemeid',
                    ])
                    ->value($form->getLegal_entity_companyid_schemeid() !== '' &&
                        $form->getLegal_entity_companyid_schemeid() !== null ?
                            $form->getLegal_entity_companyid_schemeid() : '')
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
                
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::text($form, 'legal_entity_company_legal_form')
                    ->label($translator->translate(
                            'client.peppol.legal.entity.company.legal.form'))
                    ->addInputAttributes([
                        'class' => 'form-control',
                        'id' => 'legal_entity_company_legal_form',
                        'maxlength' => 50,
                    ])
                    ->value($form->getLegal_entity_company_legal_form() !== '' &&
                        $form->getLegal_entity_company_legal_form() !== null ?
                            $form->getLegal_entity_company_legal_form() :
               ($defaults ? $pep['legal_entity_company_legal_form']['eg'] : ''))
                    ->required(true)
                    ->hint($translator->translate(
                                                'hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>
                
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::text($form, 'financial_institution_branchid')
                    ->label($translator->translate(
                                'client.peppol.financial.institution.branchid'))
                    ->addInputAttributes([
                        'class' => 'form-control',
                        'id' => 'financial_institution_branchid',
                        'maxlength' => 20,
                    ])
                    ->value($form->getFinancial_institution_branchid() !== ''
                            && $form->getFinancial_institution_branchid()
                                !== null ?
                            $form->getFinancial_institution_branchid() :
                ($defaults ? $pep['financial_institution_branchid']['eg'] : ''))
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>
                
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::text($form, 'accounting_cost')
                    ->label($translator->translate(
                                                'client.peppol.accounting.cost'))
                    ->addInputAttributes([
                        'class' => 'form-control',
                        'id' => 'accounting_cost',
                        'maxlength' => 30,
                    ])
                    ->value($form->getAccounting_cost() !== '' &&
                            $form->getAccounting_cost() !== null ?
                            $form->getAccounting_cost() : ($defaults ?
                                    $pep['accounting_cost']['eg'] : ''))
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>
                
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::text($form, 'buyer_reference')
                    ->label($translator->translate(
                            'client.peppol.buyer.reference.default')
                            . ' ' . $translator->translate(
                                    'client.peppol.buyer.reference.example'))
                    ->addInputAttributes([
                        'class' => 'form-control',
                        'id' => 'buyer_reference',
                        'maxlength' => 20,
                    ])
                    ->value($form->getBuyer_reference() !== '' &&
                            $form->getBuyer_reference() !== null ?
                            $form->getBuyer_reference() :
                        ($defaults ? $pep['buyer_reference']['eg'] : ''))
                    ->required(true)
                    ->hint($translator->translate('hint.this.field.is.required'))
                  ?>
                <?= Html::closeTag('div'); ?>
                
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?= Field::text($form, 'supplier_assigned_accountid')
                    ->label($translator->translate(
                            'client.peppol.supplier.assigned.account.id')
                            . ' ' . $translator->translate(
                                    'client.peppol.buyer.reference.example'))
                    ->addInputAttributes([
                        'class' => 'form-control',
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
