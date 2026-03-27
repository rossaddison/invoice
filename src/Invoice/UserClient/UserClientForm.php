<?php

declare(strict_types=1);

namespace App\Invoice\UserClient;

use App\Invoice\Entity\UserClient;
use Yiisoft\FormModel\FormModel;

final class UserClientForm extends FormModel
{
    private ?int $user_id = null;
    private ?int $client_id = null;
    private ?string $user_all_clients = '';

    public function __construct(UserClient $user_client)
    {
        $this->user_id = (int) $user_client->getUserId();
        $this->client_id = (int) $user_client->getClientId();
        $this->user_all_clients = '0';
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function getClientId(): ?int
    {
        return $this->client_id;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
