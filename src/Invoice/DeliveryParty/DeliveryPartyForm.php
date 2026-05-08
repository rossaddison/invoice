<?php

declare(strict_types=1);

namespace App\Invoice\DeliveryParty;

use App\Infrastructure\Persistence\DeliveryParty\DeliveryParty;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class DeliveryPartyForm extends FormModel
{
    #[Required]
    private ?string $party_name = '';

    public static function show(DeliveryParty $delivery_party): self
    {
        $form = new self();
        $form->party_name = $delivery_party->getPartyName();
        return $form;
    }

    public function getPartyName(): ?string
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
