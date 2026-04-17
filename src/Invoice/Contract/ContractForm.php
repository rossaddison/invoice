<?php

declare(strict_types=1);

namespace App\Invoice\Contract;

use App\Invoice\Entity\Contract;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use DateTimeImmutable;

final class ContractForm extends FormModel
{
    #[Required]
    private ?string $reference = null;

    #[Required]
    private ?string $name = '';

    #[Required]
    private readonly DateTimeImmutable $period_start;

    #[Required]
    private readonly DateTimeImmutable $period_end;

    #[Required]
    private ?string $client_id = '';

    public function __construct(Contract $contract)
    {
        $this->reference = $contract->getReference();
        $this->name = $contract->getName();
        $this->period_start = $contract->getPeriodStart();
        $this->period_end = $contract->getPeriodEnd();
        $this->client_id = $contract->getClientId();
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPeriodStart(): DateTimeImmutable
    {
        return $this->period_start;
    }

    public function getPeriodEnd(): DateTimeImmutable
    {
        return $this->period_end;
    }

    public function getClientId(): ?string
    {
        return $this->client_id;
    }

    /**
     * @return string
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
