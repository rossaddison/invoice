<?php

declare(strict_types=1);

namespace App\Invoice\Payment;

use App\Invoice\Entity\Inv;
use App\Invoice\Entity\Payment;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\GreaterThan;
use DateTimeImmutable;

final class PaymentForm extends FormModel
{
    #[Required]
    private ?int $payment_method_id = null;

    private mixed $payment_date = '';
    #[GreaterThan(0)]
    private ?float $amount = null;
    #[Required]
    private ?string $note = '';
    #[Required]
    private ?int $inv_id = null;
    private ?Inv $inv = null;

    public function __construct(Payment $payment)
    {
        $this->payment_method_id = (int) $payment->getPayment_method_id();
        $this->payment_date = $payment->getPayment_date();
        $this->amount = $payment->getAmount();
        $this->note = $payment->getNote();
        $this->inv_id = (int) $payment->getInv_id();
        $this->inv = $payment->getInv();
    }

    public function getPayment_method_id(): ?int
    {
        return $this->payment_method_id;
    }

    public function getPayment_date(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string|null $this->payment_date
         */
        return $this->payment_date;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getInv_id(): ?int
    {
        return $this->inv_id;
    }

    public function getInv(): ?Inv
    {
        return $this->inv;
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
