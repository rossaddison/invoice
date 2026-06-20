<?php

declare(strict_types=1);

namespace App\Widget;

use App\Invoice\Client\ClientForm;
use Yiisoft\FormModel\Field;
use Yiisoft\Translator\TranslatorInterface;

final readonly class ClientFormFields
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
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
        $hintKey = $required ? 'hint.this.field.is.required' :
                'hint.this.field.is.not.required';
        $cssClass = 'form-control form-control-lg';

        $value = match ($fieldName) {
            'client_name' => $form->client_name,
            'client_surname' => $form->client_surname,
            'client_group' => $form->client_group,
            'client_number' => $form->client_number,
            'client_address_1' => $form->client_address_1,
            'client_address_2' => $form->client_address_2,
            'client_building_number' => $form->client_building_number,
            'client_city' => $form->client_city,
            'client_state' => $form->client_state,
            'client_zip' => $form->client_zip,
            'client_vat_id' => $form->client_vat_id,
            'client_tax_code' => $form->client_tax_code,
            default => null,
        };

        $field = Field::text($form, $fieldName)
            ->label($this->translator->translate($labelKey))
            ->addInputAttributes([
                'class' => $cssClass,
                'placeholder' => $this->translator->translate($labelKey),
                'value' => $value ?? '',
            ]);

        if ($required) {
            $field =
            $field->required(true)->hint($this->translator->translate($hintKey));
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
                'placeholder'  => $this->translator->translate('email'),
                'value'        => $form->client_email ?? '',
                'class'        => 'form-control form-control-lg',
                'id'           => 'client_email',
                'autocomplete' => 'email',
            ])
            ->required(false)
            ->render();
    }

    /**
     * Client telephone field
     */
    public function clientTelephoneField(ClientForm $form, string $fieldName,
            string $labelKey): string
    {
        $value = match ($fieldName) {
            'client_mobile' => $form->client_mobile,
            'client_phone' => $form->client_phone,
            'client_fax' => $form->client_fax,
            default => null,
        };

        return Field::telephone($form, $fieldName)
            ->label($this->translator->translate($labelKey))
            ->addInputAttributes([
                'placeholder' => $this->translator->translate($labelKey),
                'value' => $value ?? '',
                'class' => 'form-control form-control-lg',
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
                'value' => $form->client_web ?? '',
                'class' => 'form-control form-control-lg',
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
            ->addInputAttributes(['class' => 'form-control form-control-lg'])
            ->value($form->client_title)
            ->prompt($this->translator->translate('none'))
            ->optionsData($titleOptions)
            ->required(false)
            ->render();
    }

    /**
     * Client language selection dropdown
     * @param array<string, string> $languageOptions
     */
    public function clientLanguageSelect(ClientForm $form,
            array $languageOptions, string $selectedLanguage): string
    {
        return Field::select($form, 'client_language')
            ->label($this->translator->translate('language'))
            ->addInputAttributes(['class' => 'form-control form-control-lg',
                'id' => 'client_language'])
            ->value(strlen($form->client_language ?? '') > 0 ?
                    $form->client_language : $selectedLanguage)
            ->optionsData($languageOptions)
            ->required(false)
            ->render();
    }

    /**
     * Client country select dropdown
     * @param array<string, string> $countryOptions
     */
    public function clientCountrySelect(ClientForm $form,
            array $countryOptions, string $selectedCountry): string
    {
        return Field::select($form, 'client_country')
            ->label($this->translator->translate('country'))
            ->addInputAttributes([
                'id' => 'client_country',
                'class' => 'form-control form-control-lg',
            ])
            ->value($form->client_country ?? $selectedCountry)
            ->optionsData($countryOptions)
            ->required(false)
            ->render();
    }
}
