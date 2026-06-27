<?php

declare(strict_types=1);

namespace App\Widget;

use App\Invoice\UserInv\UserInvForm;
use Yiisoft\FormModel\Field;
use Yiisoft\Translator\TranslatorInterface;

final readonly class UserInvFormFields
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
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
        $hintKey = $required ? 'hint.this.field.is.required' :
                'hint.this.field.is.not.required';
        $cssClass = 'form-control form-control-lg';

        $value = match ($fieldName) {
            'name' => $form->name,
            'company' => $form->company,
            'address_1' => $form->address_1,
            'address_2' => $form->address_2,
            'city' => $form->city,
            'state' => $form->state,
            'zip' => $form->zip,
            'country' => $form->country,
            'phone' => $form->phone,
            'fax' => $form->fax,
            'mobile' => $form->mobile,
            'web' => $form->web,
            'vat_id' => $form->vat_id,
            'tax_code' => $form->tax_code,
            'subscribernumber' => $form->subscribernumber,
            'iban' => $form->iban,
            'rcc' => $form->rcc,
            default => null,
        };

        $field = Field::text($form, $fieldName)
            ->label($this->translator->translate($labelKey))
            ->addInputAttributes([
                'class' => $cssClass,
                'placeholder' => $this->translator->translate($labelKey),
                'value' => $value ?? '',
                'id' => $fieldName,
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
     * UserInv user selection dropdown (for add form)
     * @param array<array-key, string> $userOptions
     */
    public function userInvUserSelect(UserInvForm $form,
            array $userOptions): string
    {
        return Field::select($form, 'user_id')
            ->label($this->translator->translate('users'))
            ->addInputAttributes([
                'class' => 'form-control form-control-lg',
                'id' => 'user_id',
            ])
            ->optionsData($userOptions)
            ->value($form->user_id ?? '')
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
                'class' => 'form-control form-control-lg',
                'id' => 'user_id',
            ])
            ->readonly(true)
            ->value($form->user_id !== null ? (string) $form->user_id : null)
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
                'class' => 'form-control form-control-lg',
                'id' => 'type',
            ])
            ->optionsData($typeOptions)
            ->value($form->type ?? 1)
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
                'class' => 'form-control form-control-lg',
                'id' => 'language',
            ])
            ->optionsData($languageOptions)
            ->value($form->language ?? '')
            ->hint($this->translator->translate('hint.this.field.is.required'))
            ->render();
    }

    /**
     * UserInv checkbox field with consistent styling
     */
    public function userInvCheckboxField(UserInvForm $form, string $fieldName,
            string $labelKey): string
    {
        return Field::checkbox($form, $fieldName)
            ->inputLabelAttributes(['class' => 'form-check-label'])
            ->inputClass('form-check-input')
            ->ariaDescribedBy($this->translator->translate($labelKey))
            ->render();
    }

    /**
     * UserInv number field (for GLN and listLimit)
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function userInvNumberField(UserInvForm $form, string $fieldName,
            string $labelKey, bool $required = false): string
    {
        $hintKey = $required ? 'hint.this.field.is.required' :
                'hint.this.field.is.not.required';

        $value = match ($fieldName) {
            'gln' => $form->gln,
            'listLimit' => $form->list_limit,
            default => null,
        };

        $field = Field::number($form, $fieldName)
            ->label($this->translator->translate($labelKey))
            ->addInputAttributes([
                'class' => 'form-control form-control-lg',
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
}
