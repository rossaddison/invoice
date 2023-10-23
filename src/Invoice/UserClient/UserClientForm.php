<?php

declare(strict_types=1);

namespace App\Invoice\UserClient;

use Yiisoft\Form\FormModel;

final class UserClientForm extends FormModel
{    
    
    private ?int $user_id=null;
    private ?int $client_id=null;

    public function getUser_id() : int|null
    {
      return $this->user_id;
    }

    public function getClient_id() : int|null
    {
      return $this->client_id;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    public function getFormName(): string
    {
      return '';
    }

    /**
     * @return array
     *
     * @psalm-return array<never, never>
     */
    public function getRules(): array    {
      return [
    ];
}
}
