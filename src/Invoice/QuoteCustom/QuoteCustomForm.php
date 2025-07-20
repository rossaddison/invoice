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
        $this->quote_id = (int) $quoteCustom->getQuote_id();
        $this->custom_field_id = (int) $quoteCustom->getCustom_field_id();
        $this->value = $quoteCustom->getValue();
    }

    public function getQuote_id(): int|null
    {
        return $this->quote_id;
    }

    public function getCustom_field_id(): int|null
    {
        return $this->custom_field_id;
    }

    public function getValue(): string|null
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
