<?php

declare(strict_types=1);

namespace App\Invoice\PaymentPeppol;

use App\Invoice\Entity\PaymentPeppol;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class PaymentPeppolForm extends FormModel
{
    private ?string $inv_id = '';
    private ?string $id     = '';

    #[Required]
    private ?int $auto_reference = null;

    #[Required]
    private ?string $provider = '';

    public function __construct(PaymentPeppol $paymentPeppol)
    {
        $this->inv_id         = $paymentPeppol->getInv_id();
        $this->id             = $paymentPeppol->getId();
        $this->auto_reference = $paymentPeppol->getAuto_reference();
        $this->provider       = $paymentPeppol->getProvider();
    }

    public function getInv_id(): ?string
    {
        return $this->inv_id;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getAuto_reference(): ?int
    {
        return $this->auto_reference;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    /**
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
