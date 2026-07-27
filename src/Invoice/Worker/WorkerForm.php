<?php

declare(strict_types=1);

namespace App\Invoice\Worker;

use App\Infrastructure\Persistence\Worker\Worker;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

final class WorkerForm extends FormModel
{
    #[Required]
    #[Length(min: 0, max: 100)]
    private ?string $firstname = null;
    #[Length(min: 0, max: 100)]
    private ?string $lastname = null;
    private bool $active = true;

    public static function show(Worker $worker): self
    {
        $form = new self();
        $form->firstname = $worker->getFirstname();
        $form->lastname = $worker->getLastname();
        $form->active = $worker->getActive();
        return $form;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function getActive(): bool
    {
        return $this->active;
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
