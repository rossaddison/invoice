<?php

declare(strict_types=1);

namespace App\Invoice\Task;

use DateTimeImmutable;
use App\Invoice\Entity\Task;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class TaskForm extends FormModel
{
    #[Required]
    private ?int $project_id = null;

    #[Required]
    private ?string $name = '';

    #[Required]
    private ?string $description = '';

    #[Required]
    private ?float $price = null;

    private mixed $finish_date = '';

    #[Required]
    private ?int $status = null;

    #[Required]
    private ?int $tax_rate_id = null;

    public function __construct(Task $task)
    {
        $this->project_id = (int) $task->getProject_id();
        $this->name = $task->getName();
        $this->description = $task->getDescription();
        $this->price = $task->getPrice();
        $this->finish_date = $task->getFinish_date();
        $this->status = $task->getStatus();
        $this->tax_rate_id = (int) $task->getTax_rate_id();
    }

    public function getProject_id(): int|null
    {
        return $this->project_id;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function getDescription(): string|null
    {
        return $this->description;
    }

    public function getPrice(): float|null
    {
        return $this->price;
    }

    public function getFinish_date(): null|string|DateTimeImmutable
    {
        /**
         * @var DateTimeImmutable|string|null $this->finish_date
         */
        return $this->finish_date;
    }

    public function getStatus(): int|null
    {
        return $this->status;
    }

    public function getTax_rate_id(): int|null
    {
        return $this->tax_rate_id;
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
