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
    /** @var list<string> the ids of the businesses the worker is registered under (any number) */
    private array $company_ids = [];

    /**
     * @param list<int> $companyIds the businesses the worker is registered under
     */
    public static function show(Worker $worker, array $companyIds = []): self
    {
        $form = new self();
        $form->company_ids = array_map('strval', $companyIds);
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
     * @return list<string>
     */
    public function getCompanyIds(): array
    {
        return $this->company_ids;
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
