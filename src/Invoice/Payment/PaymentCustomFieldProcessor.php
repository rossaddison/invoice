<?php

declare(strict_types=1);

namespace App\Invoice\Payment;

use App\Invoice\CustomFieldProcessor;
use App\Invoice\PaymentCustom\PaymentCustomRepository;
use App\Invoice\PaymentCustom\PaymentCustomService;
use App\Invoice\Entity\PaymentCustom;
use App\Invoice\PaymentCustom\PaymentCustomForm;
use Yiisoft\FormModel\FormHydrator;

final class PaymentCustomFieldProcessor implements CustomFieldProcessor
{
    public function __construct(
        private readonly PaymentCustomRepository $paymentCustomRepository,
        private readonly PaymentCustomService $paymentCustomService,
    ) {
    }

    #[\Override]
    public function exists(string $entityId, string $customFieldId): bool
    {
        return $this->paymentCustomRepository->repoPaymentCustomCount($entityId, $customFieldId) > 0;
    }

    #[\Override]
    public function findExisting(string $entityId, string $customFieldId): ?\App\Invoice\Entity\PaymentCustom
    {
        return $this->paymentCustomRepository->repoFormValuequery($entityId, $customFieldId);
    }

    #[\Override]
    public function createEntity(): \App\Invoice\Entity\PaymentCustom
    {
        return new PaymentCustom();
    }

    #[\Override]
    public function createForm(object $entity): \Yiisoft\FormModel\FormModelInterface
    {
        if (!$entity instanceof \App\Invoice\Entity\PaymentCustom) {
            throw new \InvalidArgumentException('Entity must be an instance of PaymentCustom');
        }
        return new PaymentCustomForm($entity);
    }

    #[\Override]
    public function prepareInputData(int $entityId, int $customFieldId, mixed $value): array
    {
        return [
            'payment_id' => $entityId,
            'custom_field_id' => $customFieldId,
            'value' => is_array($value) ? serialize($value) : $value,
        ];
    }

    #[\Override]
    public function save(object $entity, array $inputData): void
    {
        if (!$entity instanceof \App\Invoice\Entity\PaymentCustom) {
            throw new \InvalidArgumentException('Entity must be an instance of PaymentCustom');
        }
        $this->paymentCustomService->savePaymentCustom($entity, $inputData);
    }
}
