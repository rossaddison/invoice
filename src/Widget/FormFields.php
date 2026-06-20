<?php

declare(strict_types=1);

namespace App\Widget;

use App\Invoice\Inv\InvForm;
use App\Invoice\Quote\QuoteForm;
use App\Invoice\SalesOrder\SalesOrderForm;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface;

final readonly class FormFields
{
    public function __construct(
        private TranslatorInterface $translator,
        private SettingRepository $settingRepository,
    ) {
    }

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
        $hintKey = $required ? 'hint.this.field.is.required' :
                'hint.this.field.is.not.required';

/** @var array<array-key, array<array-key, string>|string> $clientOptions */
        $clientOptions = $optionsData['client'] ?? [];

        return Html::openTag('div')
            . Field::select($form, 'client_id')
                ->label($this->translator->translate($labelKey))
                ->addInputAttributes(['class' => 'form-control form-control-lg',])
                ->value($form->getClientId())
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
        $hintKey = $required ? 'hint.this.field.is.required' :
                'hint.this.field.is.not.required';

/** @var array<array-key, array<array-key, string>|string> $groupOptions */
        $groupOptions = $optionsData['group'] ?? [];

        return Html::openTag('div')
            . Field::select($form, 'group_id')
                ->label($this->translator->translate('group'))
                ->addInputAttributes(['class' => 'form-control form-control-lg'])
                ->value($form->getGroupId() ?? $defaultValue)
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
        $hintKey = $required ? 'hint.this.field.is.required' :
                'hint.this.field.is.not.required';

/** @var array<array-key, array<array-key, string>|string> $statusOptions */
        $statusOptions = $optionsData[$statusKey] ?? [];

        return Html::openTag('div')
            . Field::select($form, 'status_id')
                ->label($this->translator->translate('status'))
                ->addInputAttributes(['class' => 'form-control form-control-lg'])
                ->value($form->getStatusId())
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
                ->label($this->translator->translate('discount.amount')
                        . ' '
                        . $this->settingRepository->getSetting('currency_symbol'))
                ->addInputAttributes([
                    'class' => 'form-control form-control-lg',
                    'id' => 'inv_discount_amount'])
                ->value($this->settingRepository->formatAmount($form->getDiscountAmount() ?? 0.00))
                ->placeholder($this->translator->translate('discount.amount'))
                ->render()
            . Html::closeTag('div');
    }

    /**
     * Date created field
     */
    public function dateCreatedField(InvForm|QuoteForm|SalesOrderForm $form,
            string $labelKey = 'date.issued'): string
    {
        $value = $form->getDateCreated();
        $dateValue = $value instanceof \DateTimeImmutable ?
                $value->format('Y-m-d') : '';

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
                ->addInputAttributes([
                    'class' => 'form-control form-control-lg',
                    'autocomplete' => 'current-password'])
                ->value($form->getPassword())
                ->placeholder($this->translator->translate('password'))
                ->hint(
                $this->translator->translate('hint.this.field.is.not.required'))
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
                ->addInputAttributes(['class' => 'form-control form-control-lg'])
                ->value($value ?? '')
                ->placeholder($this->translator->translate('note'))
                ->hint(
                $this->translator->translate('hint.this.field.is.not.required'))
                ->render()
            . Html::closeTag('div');
    }
}
