<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PaymentCustom;

use App\Infrastructure\Persistence\{
    CustomField\CustomField, Payment\Payment, Trait\RequireId
};
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: \App\Invoice\PaymentCustom\PaymentCustomRepository::class)]
class PaymentCustom
{
    use RequireId;
 
    #[BelongsTo(target: Payment::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Payment $payment = null;

    #[BelongsTo(target: CustomField::class, nullable: false, fkAction: 'NO ACTION')]
    private ?CustomField $custom_field = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $payment_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $custom_field_id = null,
        #[Column(type: 'text', nullable: true)]
        private string $value = '')
    {
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): void
    {
        $this->payment = $payment;
    }

    public function getCustomField(): ?CustomField
    {
        return $this->custom_field;
    }

    public function setCustomField(?CustomField $custom_field): void
    {
        $this->custom_field = $custom_field;
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'PaymentCustom');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqPaymentId(): int
    {
        return $this->requireId($this->payment_id, 'Payment');
    }

    public function setPaymentId(int $payment_id): void
    {
        $this->payment_id = $payment_id;
    }

    public function reqCustomFieldId(): int
    {
        return $this->requireId($this->custom_field_id, 'CustomField');
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
