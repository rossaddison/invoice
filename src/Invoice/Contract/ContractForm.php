<?php

declare(strict_types=1);

namespace App\Invoice\Contract;

use App\Invoice\Entity\Contract;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use DateTimeImmutable;

final class ContractForm extends FormModel
{
    private ?int $id = null;

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
        $this->id = $contract->getId();
        $this->reference = $contract->getReference();
        $this->name = $contract->getName();
        $this->period_start = $contract->getPeriod_start();
        $this->period_end = $contract->getPeriod_end();
        $this->client_id = $contract->getClient_id();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPeriod_start(): DateTimeImmutable
    {
        return $this->period_start;
    }

    public function getPeriod_end(): DateTimeImmutable
    {
        return $this->period_end;
    }

    public function getClient_id(): ?string
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
