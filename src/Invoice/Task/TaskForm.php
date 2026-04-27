<?php

declare(strict_types=1);

namespace App\Invoice\Task;

use DateTimeImmutable;
use App\Infrastructure\Persistence\Task\Task;
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

    public static function show(Task $task): self
    {
        $form = new self();
        $form->project_id = $task->reqProjectId();
        $form->name = $task->getName();
        $form->description = $task->getDescription();
        $form->price = $task->getPrice();
        $form->finish_date = $task->getFinishDate();
        $form->status = $task->getStatus();
        $form->tax_rate_id = $task->reqTaxRateId();
        return $form;
    }

    public function getProjectId(): ?int
    {
        return $this->project_id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getFinishDate(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string|null $this->finish_date
         */
        return $this->finish_date;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function getTaxRateId(): ?int
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
