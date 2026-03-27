<?php

declare(strict_types=1);

namespace App\Invoice\QuoteCustom;

use App\Invoice\Entity\QuoteCustom;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class QuoteCustomForm extends FormModel
{
    private ?int $quote_id = null;
    private ?int $custom_field_id = null;

    #[Required]
    private ?string $value = '';

    public function __construct(QuoteCustom $quoteCustom)
    {
        $this->quote_id = (int) $quoteCustom->getQuoteId();
        $this->custom_field_id = (int) $quoteCustom->getCustomFieldId();
        $this->value = $quoteCustom->getValue();
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
