<?php

declare(strict_types=1);

namespace App\Invoice\Payment;

use App\Infrastructure\Persistence\{
    Inv\Inv, Payment\Payment
};
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

    public static function show(Payment $payment): self
    {
        $form = new self();
        $form->payment_method_id = $payment->reqPaymentMethodId();
        $form->payment_date = $payment->getPaymentDate();
        $form->amount = $payment->getAmount();
        $form->note = $payment->getNote();
        $form->inv_id = $payment->reqInvId();
        $form->inv = $payment->getInv();
        return $form;
    }

    public function getPaymentMethodId(): ?int
    {
        return $this->payment_method_id;
    }

    public function getPaymentDate(): string|DateTimeImmutable|null
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

    public function getInvId(): ?int
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
