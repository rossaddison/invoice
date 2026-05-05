<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Payment;

use App\Infrastructure\Persistence\{
    PaymentMethod\PaymentMethod, Inv\Inv, Trait\RequireId
};
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTime;
use DateTimeImmutable;

#[Entity(repository: \App\Invoice\Payment\PaymentRepository::class)]
class Payment
{
    use RequireId;
    
    #[BelongsTo(target: Inv::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Inv $inv = null;

    #[BelongsTo(target: PaymentMethod::class, nullable: false, fkAction: 'NO ACTION')]
    private ?PaymentMethod $payment_method = null;

    #[Column(type: 'date', nullable: false)]
    private mixed $payment_date = '';

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $inv_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $payment_method_id = null,
        #[Column(type: 'decimal(20,2)', nullable: true, default: 0.00)]
        private ?float $amount = 0.00,
        #[Column(type: 'longText', nullable: false)]
        private string $note = '')
    {
    }

    public function getInv(): ?Inv
    {
        return $this->inv;
    }

    public function setInv(?Inv $inv): void
    {
        $this->inv = $inv;
    }

    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->payment_method;
    }

    //set relation $payment_method
    public function setPaymentMethod(?PaymentMethod $payment_method): void
    {
        $this->payment_method = $payment_method;
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'Payment');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqPaymentMethodId(): int
    {
        return $this->requireId($this->payment_method_id, 'PaymentMethod');
    }

    public function setPaymentMethodId(int $payment_method_id): void
    {
        $this->payment_method_id = $payment_method_id;
    }

    public function getPaymentDate(): string|DateTimeImmutable
    {
        /** @var DateTimeImmutable|string $this->payment_date */
        return $this->payment_date;
    }

    public function setPaymentDate(?DateTime $payment_date): void
    {
        $this->payment_date = $payment_date;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    public function reqInvId(): int
    {
        return $this->requireId($this->inv_id, 'Inv');
    }

    public function setInvId(int $inv_id): void
    {
        $this->inv_id = $inv_id;
    }
}
