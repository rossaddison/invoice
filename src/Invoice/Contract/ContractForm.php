<?php

declare(strict_types=1);

namespace App\Invoice\Contract;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class ContractForm extends FormModel
{    
    
    private ?string $reference='';
    private ?string $name='';
    private ?string $period_start='';
    private ?string $period_end='';
    private ?string $client_id='';

    public function getReference() : string|null
    {
      return $this->reference;
    }

    public function getName() : string|null
    {
      return $this->name;
    }

    public function getPeriod_start() : string|null
    {
      return $this->period_start;
    }

    public function getPeriod_end() : string|null
    {
      return $this->period_end;
    }
    
    public function getClient_id() : string|null
    {
      return $this->client_id;
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
        'reference' => [new Required()],
        'name' => [new Required()],
        'period_start' => [new Required()],
        'period_end' => [new Required()],
        'client_id' => [new Required()]
      ];
}
}
