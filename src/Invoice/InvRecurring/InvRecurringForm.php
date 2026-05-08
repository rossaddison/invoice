<?php

declare(strict_types=1);

namespace App\Invoice\InvRecurring;

use App\Infrastructure\Persistence\InvRecurring\InvRecurring;
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

    private ?int $inv_id = null;
    
    public static function show(InvRecurring $invRecurring,
        ?int $inv_id): self
    {
        $form = new self();
        $form->frequency = $invRecurring->getFrequency();
        $form->start = $invRecurring->getStart();
        $form->next = $invRecurring->getNext();
        $form->end = $invRecurring->getEnd();
        $form->inv_id = $inv_id;
        return $form; 
    }

    public function getInvId(): ?int
    {
        return $this->inv_id;
    }

    public function getStart(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string|null $this->start
         */
        return $this->start;
    }

    public function getEnd(): string|DateTimeImmutable|null
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

    public function getNext(): string|DateTimeImmutable|null
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
