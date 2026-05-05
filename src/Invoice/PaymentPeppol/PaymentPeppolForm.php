<?php

declare(strict_types=1);

namespace App\Invoice\PaymentPeppol;

use App\Infrastructure\Persistence\PaymentPeppol\PaymentPeppol;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class PaymentPeppolForm extends FormModel
{
    private ?int $inv_id = null;
    
    #[Required]
    private ?int $auto_reference = null;

    #[Required]
    private ?string $provider = '';

    public static function show(PaymentPeppol $paymentPeppol): self
    {
        $form = new self();
        $form->inv_id = $paymentPeppol->reqInvId();
        $form->auto_reference = $paymentPeppol->getAutoReference();
        $form->provider = $paymentPeppol->getProvider();
        return $form;
    }

    public function getInvId(): ?int
    {
        return $this->inv_id;
    }

    public function getAutoReference(): ?int
    {
        return $this->auto_reference;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
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
