<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\QuoteCustom;

use App\Infrastructure\Persistence\{
    Quote\Quote, CustomField\CustomField, Trait\RequireId
};
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: \App\Invoice\QuoteCustom\QuoteCustomRepository::class)]
class QuoteCustom
{
    use RequireId;
    
    #[BelongsTo(target: CustomField::class, nullable: false)]
    private ?CustomField $custom_field = null;

    #[BelongsTo(target: Quote::class, nullable: false)]
    private ?Quote $quote = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,

        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $quote_id = null,

        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $custom_field_id = null,

        #[Column(type: 'text', nullable: true)]
        private string $value = '')
    {
    }

    public function getCustomField(): ?CustomField
    {
        return $this->custom_field;
    }

    public function setCustomField(?CustomField $custom_field): void
    {
        $this->custom_field = $custom_field;
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(?Quote $quote): void
    {
        $this->quote = $quote;
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'QuoteCustom');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqQuoteId(): int
    {
        return $this->requireId($this->quote_id, 'Quote');
    }

    public function setQuoteId(int $quote_id): void
    {
        $this->quote_id = $quote_id;
    }

    public function reqCustomFieldId(): int
    {
        return $this->requireId($this->custom_field_id, 'Custom Field');
    }


    public function setCustomFieldId(int $custom_field_id): void
    {
        $this->custom_field_id = $custom_field_id;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
