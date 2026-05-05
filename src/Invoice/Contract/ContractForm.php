<?php

declare(strict_types=1);

namespace App\Invoice\Contract;

use App\Infrastructure\Persistence\Contract\Contract;
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
    private mixed $period_start = '';

    #[Required]
    private mixed $period_end = '';

    #[Required]
    private ?int $client_id = null;

    public static function show(Contract $contract): self
    {
        $form = new self();
        $form->reference = $contract->getReference();
        $form->name = $contract->getName();
        $form->period_start = $contract->getPeriodStart()->format('Y-m-d');
        $form->period_end = $contract->getPeriodEnd()->format('Y-m-d');
        $form->client_id = $contract->reqClientId();
        return $form;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPeriodStart(): DateTimeImmutable|string
    {
        /**
         * @var DateTimeImmutable|string $this->period_start
         */
        return $this->period_start;
    }

    public function getPeriodEnd(): DateTimeImmutable|string
    {
        /**
         * @var DateTimeImmutable|string $this->period_end
         */
        return $this->period_end;
    }

    public function getClientId(): ?int
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
