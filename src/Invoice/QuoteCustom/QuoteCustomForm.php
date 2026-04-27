<?php

declare(strict_types=1);

namespace App\Invoice\QuoteCustom;

use App\Infrastructure\Persistence\QuoteCustom\QuoteCustom;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class QuoteCustomForm extends FormModel
{
    private ?int $quote_id = null;
    private ?int $custom_field_id = null;

    #[Required]
    private ?string $value = '';

    public static function show(QuoteCustom $quoteCustom, int $quote_id): self
    {
        $form = new self();
        $form->quote_id = $quote_id;
        $form->custom_field_id = (int) $quoteCustom->getCustomFieldId();
        $form->value = $quoteCustom->getValue();
        return $form;
    }

    public function getQuoteId(): ?int
    {
        return $this->quote_id;
    }

    public function getCustomFieldId(): ?int
    {
        return $this->custom_field_id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
