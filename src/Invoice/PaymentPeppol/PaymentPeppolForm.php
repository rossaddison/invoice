<?php

declare(strict_types=1);

namespace App\Invoice\PaymentPeppol;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class PaymentPeppolForm extends FormModel
{    
    
    private ?int $inv_id=null;
    private ?string $auto_reference='';
    private ?string $provider='';

    public function getInv_id() : int|null
    {
      return $this->inv_id;
    }

    public function getAuto_reference() : string|null
    {
      return $this->auto_reference;
    }

    public function getProvider() : string|null
    {
      return $this->provider;
    }

    /**
     * @return string
     * @psalm-return ''
     */
    public function getFormName(): string
    {
      return '';
    }

    public function getRules(): array    {
      return [
        'auto_reference' => [new Required()],        'provider' => [new Required()],    ];
}
}
