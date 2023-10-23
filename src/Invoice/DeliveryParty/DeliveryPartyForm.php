<?php
declare(strict_types=1);

namespace App\Invoice\DeliveryParty;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class DeliveryPartyForm extends FormModel
{    
    
    private ?string $party_name='';

    public function getParty_name() : string|null
    {
      return $this->party_name;
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
        'party_name' => [new Required()]
      ];
    }
}
