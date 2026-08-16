<?php

declare(strict_types=1);

namespace App\Widget;

use App\Invoice\CompanyPrivate\CompanyPrivateForm;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface;

final readonly class CompanyPrivateFormFields
{
    private const string VL = ' value="';

    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * CompanyPrivate text field with consistent styling
     */
    public function companyPrivateTextField(
        CompanyPrivateForm $form,
        string $fieldName,
        string $labelKey,
        bool $required = false,
        ?int $maxlength = null,
    ): string {
        $hintKey = $required ? 'hint.this.field.is.required' :
                'hint.this.field.is.not.required';
        $cssClass = 'form-control form-control-lg';

        $value = match ($fieldName) {
            'tax_code' => $form->getTaxCode(),
            'iban' => $form->getIban(),
            'gln' => $form->getGln(),
            'rcc' => $form->getRcc(),
            'logo_width' => $form->getLogoWidth(),
            'logo_height' => $form->getLogoHeight(),
            'logo_margin' => $form->getLogoMargin(),
            'vat_id' => $form->getVatId(),
            default => null,
        };

        $inputAttributes = ['class' => $cssClass, 'value' => $value ?? ''];
        if ($maxlength !== null) {
            $inputAttributes['maxlength'] = (string) $maxlength;
        }

        $field = Field::text($form, $fieldName)
            ->label($this->translator->translate($labelKey))
            ->addInputAttributes($inputAttributes);

        if ($required) {
            $field =
            $field->required(true)->hint($this->translator->translate($hintKey));
        } else {
            $field = $field->required(false);
        }

        return $field->render();
    }

    /**
     * Three 2-digit inputs for a UK sort code, combined into a hidden bacs_sort_code field.
     * Stored format: XX-XX-XX (8 chars max). The combining logic lives in
     * company-private.ts, not an inline <script> here — this app's CSP
     * script-src has no 'unsafe-inline', so an inline script is silently
     * blocked by every browser rather than erroring visibly; see that
     * TS file's own docblock for the live bug this caused (the hidden
     * field, and therefore the stored sort code, never actually updated).
     */
    public function companyPrivateBacsSortCodeField(CompanyPrivateForm $form): string
    {
        $sortRaw   = $form->getBacsSortCode() ?? '';
        $parts     = array_pad(explode('-', $sortRaw), 3, '');
        $label     = Html::encode($this->translator->translate('bacs.sort.code'));
        $boxClass  = 'form-control form-control-lg font-monospace text-center';
        $boxStyle  = 'width:4rem';

        $box = static function (string $id, string $value, string $ariaLabel) use ($boxClass, $boxStyle): string {
            $cQ = '"';
            return '<input type="text" id="' . $id . $cQ
                . ' class="' . $boxClass . $cQ
                . ' maxlength="2" pattern="[0-9]{2}" inputmode="numeric"'
                . ' style="' . $boxStyle . $cQ
                . self::VL . Html::encode($value) . $cQ
                . ' aria-label="' . Html::encode($ariaLabel) . '">';
        };

        return '<label class="form-label" for="bacs_sort_code_1">' . $label . '</label>'
            . '<input type="hidden" name="bacs_sort_code" id="bacs_sort_code"'
            . self::VL . Html::encode($sortRaw) . '">'
            . '<div class="d-flex align-items-center gap-2">'
            . $box('bacs_sort_code_1', $parts[0], 'Sort code first two digits')
            . '<span class="fw-bold">-</span>'
            . $box('bacs_sort_code_2', $parts[1], 'Sort code middle two digits')
            . '<span class="fw-bold">-</span>'
            . $box('bacs_sort_code_3', $parts[2], 'Sort code last two digits')
            . '</div>';
    }

    /**
     * Single 8-digit numeric input for a UK bank account number.
     */
    public function companyPrivateBacsAccountNumberField(CompanyPrivateForm $form): string
    {
        $label = Html::encode($this->translator->translate('bacs.account.number'));
        $value = Html::encode($form->getBacsAccountNumber() ?? '');

        return '<label class="form-label" for="bacs_account_number">' . $label . '</label>'
            . '<input type="text" name="bacs_account_number" id="bacs_account_number"'
            . ' class="form-control form-control-lg font-monospace"'
            . ' maxlength="8" pattern="[0-9]{8}" inputmode="numeric"'
            . self::VL . $value . '" placeholder="12345678">';
    }

    /**
     * CompanyPrivate company selection dropdown
     * @param array<string, string> $companyOptions
     */
    public function companyPrivateCompanySelect(CompanyPrivateForm $form,
            array $companyOptions, string $labelKey): string
    {
        return Field::select($form, 'company_id')
            ->label($labelKey)
            ->addInputAttributes([
                'class' => 'form-control form-control-lg',
                'id' => 'company_id',
            ])
            ->optionsData($companyOptions)
            ->required(true)
            ->hint($this->translator->translate('hint.this.field.is.required'))
            ->render();
    }

    /**
     * CompanyPrivate file field
     */
    public function companyPrivateFileField(CompanyPrivateForm $form,
        string $fieldName): string
    {
        $value = match ($fieldName) {
            'logo_filename' => $form->getLogoFilename(),
            default => null,
        };

        return Field::file($form, $fieldName)
            ->accept('image/*')
            ->value($value ?? '')
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
            'start_date' => $form->getStartDate(),
            'end_date' => $form->getEndDate(),
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
                'class' => 'form-control form-control-lg',
                'placeholder' => $placeholder,
                'data-action' => 'show-picker',
            ])
            ->value($formattedValue)
            ->render();
    }
}
