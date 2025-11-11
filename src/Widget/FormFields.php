<?php

declare(strict_types=1);

namespace App\Widget;

use App\Invoice\Client\ClientForm;
use App\Invoice\Company\CompanyForm;
use App\Invoice\CompanyPrivate\CompanyPrivateForm;
use App\Invoice\Inv\InvForm;
use App\Invoice\Product\ProductForm;
use App\Invoice\Quote\QuoteForm;
use App\Invoice\SalesOrder\SalesOrderForm;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserInv\UserInvForm;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface;

final readonly class FormFields
{
    public function __construct(
        private TranslatorInterface $translator,
        private SettingRepository $settingRepository,
    ) {}

    /**
     * Client selection dropdown field
     * @param array<array-key, mixed> $optionsData
     */
    public function clientSelect(
        InvForm|QuoteForm|SalesOrderForm $form,
        array $optionsData,
        string $labelKey = 'client',
        bool $required = true,
    ): string {
        $hintKey = $required ? 'hint.this.field.is.required' : 'hint.this.field.is.not.required';

        /** @var array<array-key, array<array-key, string>|string> $clientOptions */
        $clientOptions = $optionsData['client'] ?? [];

        return Html::openTag('div')
            . Field::select($form, 'client_id')
                ->label($this->translator->translate($labelKey))
                ->addInputAttributes(['class' => 'form-control'])
                ->value($form->getClient_id())
                ->prompt($this->translator->translate('none'))
                ->optionsData($clientOptions)
                ->hint($this->translator->translate($hintKey))
                ->render()
            . Html::closeTag('div');
    }

    /**
     * Group selection dropdown field
     * @param array<array-key, mixed> $optionsData
     */
    public function groupSelect(
        InvForm|QuoteForm|SalesOrderForm $form,
        array $optionsData,
        int $defaultValue = 2,
        bool $required = true,
    ): string {
        $hintKey = $required ? 'hint.this.field.is.required' : 'hint.this.field.is.not.required';

        /** @var array<array-key, array<array-key, string>|string> $groupOptions */
        $groupOptions = $optionsData['group'] ?? [];

        return Html::openTag('div')
            . Field::select($form, 'group_id')
                ->label($this->translator->translate('group'))
                ->addInputAttributes(['class' => 'form-control'])
                ->value($form->getGroup_id() ?? $defaultValue)
                ->prompt($this->translator->translate('none'))
                ->optionsData($groupOptions)
                ->hint($this->translator->translate($hintKey))
                ->render()
            . Html::closeTag('div');
    }

    /**
     * Status selection dropdown field
     * @param array<array-key, mixed> $optionsData
     */
    public function statusSelect(
        InvForm|QuoteForm|SalesOrderForm $form,
        array $optionsData,
        string $statusKey,
        bool $required = false,
    ): string {
        $hintKey = $required ? 'hint.this.field.is.required' : 'hint.this.field.is.not.required';

        /** @var array<array-key, array<array-key, string>|string> $statusOptions */
        $statusOptions = $optionsData[$statusKey] ?? [];

        return Html::openTag('div')
            . Field::select($form, 'status_id')
                ->label($this->translator->translate('status'))
                ->addInputAttributes(['class' => 'form-control'])
                ->value($form->getStatus_id())
                ->prompt($this->translator->translate('none'))
                ->optionsData($statusOptions)
                ->hint($this->translator->translate($hintKey))
                ->render()
            . Html::closeTag('div');
    }

    /**
     * Discount fields (amount and percent)
     */
    public function discountFields(InvForm|QuoteForm|SalesOrderForm $form): string
    {
        return Html::openTag('div')
            . Field::text($form, 'discount_amount')
                ->hideLabel(false)
                ->disabled($form->getDiscount_percent() > 0.00 && $form->getDiscount_amount() == 0.00)
                ->label($this->translator->translate('discount.amount') . ' ' . $this->settingRepository->getSetting('currency_symbol'))
                ->addInputAttributes(['class' => 'form-control', 'id' => 'inv_discount_amount'])
                ->value(Html::encode($this->settingRepository->format_amount($form->getDiscount_amount() ?? 0.00)))
                ->placeholder($this->translator->translate('discount.amount'))
                ->render()
            . Html::closeTag('div')
            . Html::openTag('div')
            . Field::text($form, 'discount_percent')
                ->label($this->translator->translate('discount.percent'))
                ->disabled(($form->getDiscount_amount() > 0.00 && $form->getDiscount_percent() == 0.00))
                ->addInputAttributes(['class' => 'form-control', 'id' => 'inv_discount_percent'])
                ->value(Html::encode($this->settingRepository->format_amount($form->getDiscount_percent() ?? 0.00)))
                ->placeholder($this->translator->translate('discount.percent'))
                ->render()
            . Html::closeTag('div');
    }

    /**
     * Date created field
     */
    public function dateCreatedField(InvForm|QuoteForm|SalesOrderForm $form, string $labelKey = 'date.issued'): string
    {
        $value = $form->getDate_created();
        $dateValue = $value instanceof \DateTimeImmutable ? $value->format('Y-m-d') : '';

        return Html::openTag('div')
            . Field::date($form, 'date_created')
                ->label($this->translator->translate($labelKey))
                ->value($dateValue)
                ->hint($this->translator->translate('hint.this.field.is.required'))
                ->render()
            . Html::closeTag('div');
    }

    /**
     * Password field
     */
    public function passwordField(InvForm|QuoteForm|SalesOrderForm $form): string
    {
        return Html::openTag('div')
            . Field::password($form, 'password')
                ->label($this->translator->translate('password'))
                ->addInputAttributes(['class' => 'form-control', 'autocomplete' => 'current-password'])
                ->value(Html::encode($form->getPassword()))
                ->placeholder($this->translator->translate('password'))
                ->hint($this->translator->translate('hint.this.field.is.not.required'))
                ->render()
            . Html::closeTag('div');
    }

    /**
     * Notes textarea field
     */
    public function notesField(InvForm|QuoteForm|SalesOrderForm $form): string
    {
        // Handle different field names: InvForm uses 'note', others use 'notes'
        $fieldName = $form instanceof InvForm ? 'note' : 'notes';
        $value = $form instanceof InvForm ? $form->getNote() : $form->getNotes();

        return Html::openTag('div')
            . Field::textarea($form, $fieldName)
                ->label($this->translator->translate('note'))
                ->addInputAttributes(['class' => 'form-control'])
                ->value(Html::encode($value ?? ''))
                ->placeholder($this->translator->translate('note'))
                ->hint($this->translator->translate('hint.this.field.is.not.required'))
                ->render()
            . Html::closeTag('div');
    }

    /**
     * Family selection dropdown field for products
     * @param array<array-key, array<array-key, string>|string> $familiesData
     */
    public function familySelect(ProductForm $form, array $familiesData, bool $required = true): string
    {
        $hintKey = $required ? 'hint.this.field.is.required' : 'hint.this.field.is.not.required';
        $cssClass = $required ? 'form-control alert alert-warning' : 'form-control alert alert-success';

        return Field::select($form, 'family_id')
            ->label($this->translator->translate('family'))
            ->addInputAttributes(['class' => $cssClass])
            ->value($form->getFamily_id())
            ->prompt($this->translator->translate('none'))
            ->optionsData($familiesData)
            ->hint($this->translator->translate($hintKey))
            ->render();
    }

    /**
     * Unit selection dropdown field for products
     * @param array<array-key, array<array-key, string>|string> $unitsData
     */
    public function unitSelect(ProductForm $form, array $unitsData, bool $required = true): string
    {
        $hintKey = $required ? 'hint.this.field.is.required' : 'hint.this.field.is.not.required';
        $cssClass = $required ? 'form-control alert alert-warning' : 'form-control alert alert-success';

        return Field::select($form, 'unit_id')
            ->label($this->translator->translate('unit'))
            ->addInputAttributes(['class' => $cssClass])
            ->value($form->getUnit_id())
            ->prompt($this->translator->translate('none'))
            ->optionsData($unitsData)
            ->hint($this->translator->translate($hintKey))
            ->render();
    }

    /**
     * Tax rate selection dropdown field for products
     * @param array<array-key, array<array-key, string>|string> $taxRatesData
     */
    public function taxRateSelect(ProductForm $form, array $taxRatesData, bool $required = true): string
    {
        $hintKey = $required ? 'hint.this.field.is.required' : 'hint.this.field.is.not.required';
        $cssClass = $required ? 'form-control alert alert-warning' : 'form-control alert alert-success';

        return Field::select($form, 'tax_rate_id')
            ->label($this->translator->translate('tax.rate'))
            ->addInputAttributes(['class' => $cssClass])
            ->value($form->getTax_rate_id())
            ->prompt($this->translator->translate('none'))
            ->optionsData($taxRatesData)
            ->hint($this->translator->translate($hintKey))
            ->render();
    }

    /**
     * Product text field with consistent styling
     */
    public function productTextField(
        ProductForm $form,
        string $fieldName,
        string $labelKey,
        bool $required = true,
        bool $isPrice = false,
    ): string {
        $hintKey = $required ? 'hint.this.field.is.required' : 'hint.this.field.is.not.required';
        $cssClass = $required ? 'form-control alert alert-warning' : 'form-control alert alert-success';

        // Handle specific field name mappings for ProductForm
        $value = match ($fieldName) {
            'product_name' => $form->getProduct_name(),
            'product_description' => $form->getProduct_description(),
            'product_sku' => $form->getProduct_sku(),
            'purchase_price' => $form->getPurchase_price(),
            'product_price' => $form->getProduct_price(),
            'product_price_base_quantity' => $form->getProduct_price_base_quantity(),
            'product_tariff' => $form->getProduct_tariff(),
            'product_sii_id' => $form->getProduct_sii_id(),
            'product_sii_schemeid' => $form->getProduct_sii_schemeid(),
            'product_icc_listid' => $form->getProduct_icc_listid(),
            'product_icc_listversionid' => $form->getProduct_icc_listversionid(),
            'product_icc_id' => $form->getProduct_icc_id(),
            'product_country_of_origin_code' => $form->getProduct_country_of_origin_code(),
            'product_additional_item_property_name' => $form->getProduct_additional_item_property_name(),
            'product_additional_item_property_value' => $form->getProduct_additional_item_property_value(),
            'provider_name' => $form->getProvider_name(),
            default => null,
        };

        // Format price values using SettingRepository
        if ($isPrice && is_numeric($value)) {
            $numericValue = is_float($value) ? $value : (float) $value;
            $value = $this->settingRepository->format_amount($numericValue >= 0.00 ? $numericValue : 0.00);
        }

        $field = Field::text($form, $fieldName)
            ->label($this->translator->translate($labelKey))
            ->addInputAttributes(['class' => $cssClass])
            ->value(Html::encode($value ?? ''))
            ->placeholder($this->translator->translate($labelKey))
            ->hint($this->translator->translate($hintKey));

        if ($required) {
            $field = $field->required(true);
        }

        return $field->render();
    }

    /**
     * Product price field with proper formatting
     */
    public function productPriceField(ProductForm $form, string $fieldName, string $labelKey, bool $required = true): string
    {
        return $this->productTextField($form, $fieldName, $labelKey, $required, true);
    }

    /**
     * Unit Peppol selection dropdown field for products
     * @param array<array-key, array<array-key, string>|string> $unitPeppolsData
     */
    public function unitPeppolSelect(ProductForm $form, array $unitPeppolsData, bool $required = false): string
    {
        $hintKey = $required ? 'hint.this.field.is.required' : 'hint.this.field.is.not.required';
        $cssClass = $required ? 'form-control alert alert-warning' : 'form-control alert alert-success';

        return Field::select($form, 'unit_peppol_id')
            ->label($this->translator->translate('product.peppol.unit'))
            ->addInputAttributes(['class' => $cssClass])
            ->value(Html::encode($form->getUnit_peppol_id()))
            ->prompt($this->translator->translate('none'))
            ->optionsData($unitPeppolsData)
            ->hint($this->translator->translate($hintKey))
            ->render();
    }

    /**
     * Client text field with consistent styling
     */
    public function clientTextField(
        ClientForm $form,
        string $fieldName,
        string $labelKey,
        bool $required = false,
    ): string {
        $hintKey = $required ? 'hint.this.field.is.required' : 'hint.this.field.is.not.required';
        $cssClass = 'form-control';

        // Handle specific field name mappings for ClientForm
        $value = match ($fieldName) {
            'client_name' => $form->getClient_name(),
            'client_surname' => $form->getClient_surname(),
            'client_group' => $form->getClient_group(),
            'client_number' => $form->getClient_number(),
            'client_address_1' => $form->getClient_address_1(),
            'client_address_2' => $form->getClient_address_2(),
            'client_building_number' => $form->getClient_building_number(),
            'client_city' => $form->getClient_city(),
            'client_state' => $form->getClient_state(),
            'client_zip' => $form->getClient_zip(),
            'client_vat_id' => $form->getClient_vat_id(),
            'client_tax_code' => $form->getClient_tax_code(),
            'client_avs' => $form->getClient_avs(),
            'client_insurednumber' => $form->getClient_insurednumber(),
            'client_veka' => $form->getClient_veka(),
            default => null,
        };

        $field = Field::text($form, $fieldName)
            ->label($this->translator->translate($labelKey))
            ->addInputAttributes([
                'class' => $cssClass,
                'placeholder' => $this->translator->translate($labelKey),
                'value' => Html::encode($value ?? ''),
            ]);

        if ($required) {
            $field = $field->required(true)->hint($this->translator->translate($hintKey));
        } else {
            $field = $field->required(false);
        }

        return $field->render();
    }

    /**
     * Client email field
     */
    public function clientEmailField(ClientForm $form): string
    {
        return Field::email($form, 'client_email')
            ->label($this->translator->translate('email'))
            ->addInputAttributes([
                'placeholder' => $this->translator->translate('email'),
                'value' => Html::encode($form->getClient_email() ?? ''),
                'class' => 'form-control',
                'id' => 'client_email',
            ])
            ->required(false)
            ->render();
    }

    /**
     * Client telephone field
     */
    public function clientTelephoneField(ClientForm $form, string $fieldName, string $labelKey): string
    {
        $value = match ($fieldName) {
            'client_mobile' => $form->getClient_mobile(),
            'client_phone' => $form->getClient_phone(),
            'client_fax' => $form->getClient_fax(),
            default => null,
        };

        return Field::telephone($form, $fieldName)
            ->label($this->translator->translate($labelKey))
            ->addInputAttributes([
                'placeholder' => $this->translator->translate($labelKey),
                'value' => Html::encode($value ?? ''),
                'class' => 'form-control',
                'id' => $fieldName,
            ])
            ->required(false)
            ->render();
    }

    /**
     * Client URL field
     */
    public function clientUrlField(ClientForm $form): string
    {
        return Field::url($form, 'client_web')
            ->label($this->translator->translate('web'))
            ->addInputAttributes([
                'placeholder' => $this->translator->translate('web'),
                'value' => Html::encode($form->getClient_web() ?? ''),
                'class' => 'form-control',
                'id' => 'client_web',
            ])
            ->required(false)
            ->render();
    }

    /**
     * Client title selection dropdown
     * @param array<string> $titleOptions
     */
    public function clientTitleSelect(ClientForm $form, array $titleOptions): string
    {
        return Field::select($form, 'client_title')
            ->label($this->translator->translate('client.title'))
            ->addInputAttributes(['class' => 'form-control'])
            ->value($form->getClient_title())
            ->prompt($this->translator->translate('none'))
            ->optionsData($titleOptions)
            ->required(false)
            ->render();
    }

    /**
     * Client language selection dropdown
     * @param array<string, string> $languageOptions
     */
    public function clientLanguageSelect(ClientForm $form, array $languageOptions, string $selectedLanguage): string
    {
        return Field::select($form, 'client_language')
            ->label($this->translator->translate('language'))
            ->addInputAttributes(['class' => 'form-control', 'id' => 'client_language'])
            ->value(strlen($form->getClient_language() ?? '') > 0 ? $form->getClient_language() : $selectedLanguage)
            ->optionsData($languageOptions)
            ->required(true)
            ->hint($this->translator->translate('hint.this.field.is.required'))
            ->render();
    }

    /**
     * Client country select dropdown
     * @param array<string, string> $countryOptions
     */
    public function clientCountrySelect(ClientForm $form, array $countryOptions, string $selectedCountry): string
    {
        return Field::select($form, 'client_country')
            ->label($this->translator->translate('country'))
            ->addInputAttributes([
                'id' => 'client_country',
                'class' => 'form-control',
            ])
            ->value($form->getClient_country() ?? $selectedCountry)
            ->optionsData($countryOptions)
            ->required(true)
            ->hint($this->translator->translate('hint.this.field.is.required'))
            ->render();
    }

    /**
     * UserInv text field with consistent styling
     */
    public function userInvTextField(
        UserInvForm $form,
        string $fieldName,
        string $labelKey,
        bool $required = false,
    ): string {
        $hintKey = $required ? 'hint.this.field.is.required' : 'hint.this.field.is.not.required';
        $cssClass = 'form-control';

        // Handle specific field name mappings for UserInvForm
        $value = match ($fieldName) {
            'name' => $form->getName(),
            'company' => $form->getCompany(),
            'address_1' => $form->getAddress_1(),
            'address_2' => $form->getAddress_2(),
            'city' => $form->getCity(),
            'state' => $form->getState(),
            'zip' => $form->getZip(),
            'country' => $form->getCountry(),
            'phone' => $form->getPhone(),
            'fax' => $form->getFax(),
            'mobile' => $form->getMobile(),
            'web' => $form->getWeb(),
            'vat_id' => $form->getVat_id(),
            'tax_code' => $form->getTax_code(),
            'subscribernumber' => $form->getSubscribernumber(),
            'iban' => $form->getIban(),
            'rcc' => $form->getRcc(),
            default => null,
        };

        $field = Field::text($form, $fieldName)
            ->label($this->translator->translate($labelKey))
            ->addInputAttributes([
                'class' => $cssClass,
                'placeholder' => $this->translator->translate($labelKey),
                'value' => Html::encode($value ?? ''),
                'id' => $fieldName,
            ]);

        if ($required) {
            $field = $field->required(true)->hint($this->translator->translate($hintKey));
        } else {
            $field = $field->required(false);
        }

        return $field->render();
    }

    /**
     * UserInv user selection dropdown (for add form)
     * @param array<array-key, string> $userOptions
     */
    public function userInvUserSelect(UserInvForm $form, array $userOptions): string
    {
        return Field::select($form, 'user_id')
            ->label($this->translator->translate('users'))
            ->addInputAttributes([
                'class' => 'form-control',
                'id' => 'user_id',
            ])
            ->optionsData($userOptions)
            ->value(Html::encode($form->getUser_id() ?? ''))
            ->hint($this->translator->translate('hint.this.field.is.required'))
            ->render();
    }

    /**
     * UserInv user ID field (for edit form - readonly)
     */
    public function userInvUserIdField(UserInvForm $form): string
    {
        return Field::text($form, 'user_id')
            ->label($this->translator->translate('users'))
            ->addInputAttributes([
                'class' => 'form-control',
                'id' => 'user_id',
            ])
            ->readonly(true)
            ->value(Html::encode($form->getUser_id() ?? ''))
            ->hint($this->translator->translate('hint.this.field.is.required'))
            ->render();
    }

    /**
     * UserInv type selection dropdown
     * @param array<int, string> $typeOptions
     */
    public function userInvTypeSelect(UserInvForm $form, array $typeOptions): string
    {
        return Field::select($form, 'type')
            ->label($this->translator->translate('type'))
            ->addInputAttributes([
                'class' => 'form-control',
                'id' => 'type',
            ])
            ->optionsData($typeOptions)
            ->value(Html::encode($form->getType() ?? 1))
            ->hint($this->translator->translate('hint.this.field.is.required'))
            ->render();
    }

    /**
     * UserInv language selection dropdown
     * @param array<string, string> $languageOptions
     */
    public function userInvLanguageSelect(UserInvForm $form, array $languageOptions): string
    {
        return Field::select($form, 'language')
            ->label($this->translator->translate('language'))
            ->addInputAttributes([
                'class' => 'form-control',
                'id' => 'language',
            ])
            ->optionsData($languageOptions)
            ->value(Html::encode($form->getLanguage() ?? ''))
            ->hint($this->translator->translate('hint.this.field.is.required'))
            ->render();
    }

    /**
     * UserInv checkbox field with consistent styling
     */
    public function userInvCheckboxField(UserInvForm $form, string $fieldName, string $labelKey): string
    {
        return Field::checkbox($form, $fieldName)
            ->inputLabelAttributes(['class' => 'form-check-label'])
            ->inputClass('form-check-input')
            ->ariaDescribedBy($this->translator->translate($labelKey))
            ->render();
    }

    /**
     * UserInv number field (for GLN and listLimit)
     */
    public function userInvNumberField(UserInvForm $form, string $fieldName, string $labelKey, bool $required = false): string
    {
        $hintKey = $required ? 'hint.this.field.is.required' : 'hint.this.field.is.not.required';

        $value = match ($fieldName) {
            'gln' => $form->getGln(),
            'listLimit' => $form->getListLimit(),
            default => null,
        };

        $field = Field::number($form, $fieldName)
            ->label($this->translator->translate($labelKey))
            ->addInputAttributes([
                'class' => 'form-control',
                'placeholder' => $this->translator->translate($labelKey),
                'value' => $value !== null ? (string) $value : '',
                'id' => $fieldName,
            ]);

        if ($required) {
            $field = $field->required(true)->hint($this->translator->translate($hintKey));
        } else {
            $field = $field->required(false);
        }

        return $field->render();
    }

    /**
     * Company text field with consistent styling
     */
    public function companyTextField(
        CompanyForm $form,
        string $fieldName,
        string $labelKey,
        bool $required = false,
    ): string {
        $hintKey = $required ? 'hint.this.field.is.required' : 'hint.this.field.is.not.required';
        $cssClass = 'form-control';

        // Handle specific field name mappings for CompanyForm
        $value = match ($fieldName) {
            'name' => $form->getName(),
            'address_1' => $form->getAddress_1(),
            'address_2' => $form->getAddress_2(),
            'city' => $form->getCity(),
            'state' => $form->getState(),
            'zip' => $form->getZip(),
            'country' => $form->getCountry(),
            'web' => $form->getWeb(),
            'arbitrationBody' => $form->getArbitrationBody(),
            'arbitrationJurisdiction' => $form->getArbitrationJurisdiction(),
            default => null,
        };

        $field = Field::text($form, $fieldName)
            ->label($this->translator->translate($labelKey))
            ->addInputAttributes([
                'class' => $cssClass,
                'placeholder' => $this->translator->translate($labelKey),
                'value' => Html::encode($value ?? ''),
            ]);

        if ($required) {
            $field = $field->required(true)->hint($this->translator->translate($hintKey));
        } else {
            $field = $field->required(false);
        }

        return $field->render();
    }

    /**
     * Company email field
     */
    public function companyEmailField(CompanyForm $form): string
    {
        return Field::email($form, 'email')
            ->label($this->translator->translate('email'))
            ->addInputAttributes([
                'placeholder' => $this->translator->translate('email'),
                'class' => 'form-control',
                'value' => Html::encode($form->getEmail() ?? ''),
            ])
            ->required(true)
            ->hint($this->translator->translate('hint.this.field.is.required'))
            ->render();
    }

    /**
     * Company telephone field
     */
    public function companyTelephoneField(CompanyForm $form, string $fieldName, string $labelKey): string
    {
        $value = match ($fieldName) {
            'phone' => $form->getPhone(),
            'fax' => $form->getFax(),
            default => null,
        };

        return Field::telephone($form, $fieldName)
            ->label($this->translator->translate($labelKey))
            ->addInputAttributes([
                'placeholder' => $this->translator->translate($labelKey),
                'class' => 'form-control',
                'value' => Html::encode($value ?? ''),
            ])
            ->required(false)
            ->render();
    }

    /**
     * Company checkbox field with consistent styling
     */
    public function companyCheckboxField(CompanyForm $form, string $fieldName, string $labelKey): string
    {
        return Field::checkbox($form, $fieldName)
            ->inputLabelAttributes(['class' => 'form-check-label'])
            ->inputClass('form-check-input')
            ->ariaDescribedBy($this->translator->translate($labelKey))
            ->render();
    }

    /**
     * Company hidden field
     */
    public function companyHiddenField(CompanyForm $form, string $fieldName): string
    {
        $value = match ($fieldName) {
            'id' => $form->getId(),
            default => null,
        };

        return Field::hidden($form, $fieldName)
            ->addInputAttributes([
                'class' => 'form-control',
                'value' => Html::encode($value !== null ? (string) $value : ''),
            ])
            ->hideLabel()
            ->render();
    }

    /**
     * CompanyPrivate text field with consistent styling
     */
    public function companyPrivateTextField(
        CompanyPrivateForm $form,
        string $fieldName,
        string $labelKey,
        bool $required = false,
    ): string {
        $hintKey = $required ? 'hint.this.field.is.required' : 'hint.this.field.is.not.required';
        $cssClass = 'form-control';

        // Handle specific field name mappings for CompanyPrivateForm
        $value = match ($fieldName) {
            'tax_code' => $form->getTax_code(),
            'iban' => $form->getIban(),
            'gln' => $form->getGln(),
            'rcc' => $form->getRcc(),
            'logo_width' => $form->getLogo_width(),
            'logo_height' => $form->getLogo_height(),
            'logo_margin' => $form->getLogo_margin(),
            'vat_id' => $form->getVat_id(),
            default => null,
        };

        $field = Field::text($form, $fieldName)
            ->label($this->translator->translate($labelKey))
            ->addInputAttributes([
                'class' => $cssClass,
                'value' => Html::encode($value ?? ''),
            ]);

        if ($required) {
            $field = $field->required(true)->hint($this->translator->translate($hintKey));
        } else {
            $field = $field->required(false);
        }

        return $field->render();
    }

    /**
     * CompanyPrivate company selection dropdown
     * @param array<string, string> $companyOptions
     */
    public function companyPrivateCompanySelect(CompanyPrivateForm $form, array $companyOptions, string $labelKey): string
    {
        return Field::select($form, 'company_id')
            ->label($labelKey)
            ->addInputAttributes([
                'class' => 'form-control',
                'id' => 'company_id',
            ])
            ->optionsData($companyOptions)
            ->required(true)
            ->hint($this->translator->translate('hint.this.field.is.required'))
            ->render();
    }

    /**
     * CompanyPrivate hidden field
     */
    public function companyPrivateHiddenField(CompanyPrivateForm $form, string $fieldName): string
    {
        $value = match ($fieldName) {
            'id' => $form->getId(),
            default => null,
        };

        return Field::hidden($form, $fieldName)
            ->addInputAttributes([
                'class' => 'form-control',
                'value' => Html::encode($value !== null ? (string) $value : ''),
            ])
            ->hideLabel()
            ->render();
    }

    /**
     * CompanyPrivate file field
     */
    public function companyPrivateFileField(CompanyPrivateForm $form, string $fieldName): string
    {
        $value = match ($fieldName) {
            'logo_filename' => $form->getLogo_filename(),
            default => null,
        };

        return Field::file($form, $fieldName)
            ->accept('image/*')
            ->value(Html::encode($value ?? ''))
            ->render();
    }

    /**
     * CompanyPrivate date field with proper formatting
     */
    public function companyPrivateDateField(
        CompanyPrivateForm $form,
        string $fieldName,
        string $placeholder,
    ): string {
        $value = match ($fieldName) {
            'start_date' => $form->getStart_date(),
            'end_date' => $form->getEnd_date(),
            default => null,
        };

        $formattedValue = '';
        if ($value !== null) {
            if (!is_string($value) && $value instanceof \DateTimeInterface) {
                $formattedValue = $value->format('Y-m-d');
            } else {
                $formattedValue = (new \DateTimeImmutable('now'))->format('Y-m-d');
            }
        }

        return Field::date($form, $fieldName)
            ->addInputAttributes([
                'class' => 'form-control',
                'placeholder' => $placeholder,
            ])
            ->value(Html::encode($formattedValue))
            ->render();
    }
}
