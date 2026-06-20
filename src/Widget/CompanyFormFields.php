<?php

declare(strict_types=1);

namespace App\Widget;

use App\Invoice\Company\CompanyForm;
use Yiisoft\FormModel\Field;
use Yiisoft\Translator\TranslatorInterface;

final readonly class CompanyFormFields
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
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
        $hintKey = $required ? 'hint.this.field.is.required' :
            'hint.this.field.is.not.required';
        $cssClass = 'form-control form-control-lg';

        $value = match ($fieldName) {
            'name' => $form->name,
            'address_1' => $form->address_1,
            'address_2' => $form->address_2,
            'city' => $form->city,
            'state' => $form->state,
            'zip' => $form->zip,
            'country' => $form->country,
            'seo_description' => $form->seo_description,
            'web' => $form->web,
            'arbitrationBody' => $form->arbitration_body,
            'arbitrationJurisdiction' => $form->arbitration_jurisdiction,
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
            $field = $field->required(true)->hint(
                    $this->translator->translate($hintKey));
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
                'class' => 'form-control form-control-lg',
                'value' => $form->email ?? '',
            ])
            ->required(true)
            ->hint($this->translator->translate('hint.this.field.is.required'))
            ->render();
    }

    /**
     * Company telephone field
     */
    public function companyTelephoneField(CompanyForm $form, string $fieldName,
            string $labelKey): string
    {
        $value = match ($fieldName) {
            'phone' => $form->phone,
            'fax' => $form->fax,
            default => null,
        };

        return Field::telephone($form, $fieldName)
            ->label($this->translator->translate($labelKey))
            ->addInputAttributes([
                'placeholder' => $this->translator->translate($labelKey),
                'class' => 'form-control form-control-lg',
                'value' => $value ?? '',
            ])
            ->required(false)
            ->render();
    }

    /**
     * Company checkbox field with consistent styling
     */
    public function companyCheckboxField(CompanyForm $form, string $fieldName,
            string $labelKey): string
    {
        return Field::checkbox($form, $fieldName)
            ->inputLabelAttributes(['class' => 'form-check-label'])
            ->inputClass('form-check-input')
            ->ariaDescribedBy($this->translator->translate($labelKey))
            ->render();
    }
}
