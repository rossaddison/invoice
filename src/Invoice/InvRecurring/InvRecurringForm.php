<?php

declare(strict_types=1);

namespace App\Invoice\InvRecurring;

use App\Invoice\Entity\InvRecurring;
use DateTimeImmutable;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class InvRecurringForm extends FormModel
{
    #[Required]
    private ?string $frequency = '';

    private mixed $start = '';

    private mixed $next = '';

    private mixed $end = '';

    public function __construct(InvRecurring $invRecurring, private readonly ?int $inv_id)
    {
        $this->frequency = $invRecurring->getFrequency();
        $this->start     = $invRecurring->getStart();
        $this->next      = $invRecurring->getNext();
        $this->end       = $invRecurring->getEnd();
    }

    public function getInv_id(): ?int
    {
        return $this->inv_id;
    }

    public function getStart(): string|\DateTimeImmutable|null
    {
        /*
         * @var DateTimeImmutable|string|null $this->start
         */
        return $this->start;
    }

    public function getEnd(): string|\DateTimeImmutable|null
    {
        /*
         * @var DateTimeImmutable|string|null $this->end
         */
        return $this->end;
    }

    public function getFrequency(): ?string
    {
        return $this->frequency;
    }

    public function getNext(): string|\DateTimeImmutable|null
    {
        /*
         * @var DateTimeImmutable|string|null $this->next
         */
        return $this->next;
    }

    /**
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
