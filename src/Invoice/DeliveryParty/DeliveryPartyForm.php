<?php

declare(strict_types=1);

namespace App\Invoice\DeliveryParty;

use App\Invoice\Entity\DeliveryParty;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class DeliveryPartyForm extends FormModel
{
    #[Required]
    private ?string $party_name = '';

    public function __construct(DeliveryParty $delivery_party)
    {
        $this->party_name = $delivery_party->getPartyName();
    }

    public function getParty_name(): string|null
    {
        return $this->party_name;
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
