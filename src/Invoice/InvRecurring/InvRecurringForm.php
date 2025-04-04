<?php

declare(strict_types=1);

namespace App\Invoice\InvRecurring;

use App\Invoice\Entity\InvRecurring;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use DateTimeImmutable;

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
        $this->start = $invRecurring->getStart();
        $this->next = $invRecurring->getNext();
        $this->end = $invRecurring->getEnd();
    }

    public function getInv_id(): ?int
    {
        return $this->inv_id;
    }

    public function getStart(): null|string|DateTimeImmutable
    {
        /**
         * @var DateTimeImmutable|string|null $this->start
         */
        return $this->start;
    }

    public function getEnd(): null|string|DateTimeImmutable
    {
        /**
         * @var DateTimeImmutable|string|null $this->end
         */
        return $this->end;
    }

    public function getFrequency(): ?string
    {
        return $this->frequency;
    }

    public function getNext(): null|string|DateTimeImmutable
    {
        /**
         * @var DateTimeImmutable|string|null $this->next
         */
        return $this->next;
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
