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
        $this->user_id = (int)$user_client->getUser_id();
        $this->client_id = (int)$user_client->getClient_id();
        $this->user_all_clients = '0';
    }

    public function getUser_id(): int|null
    {
        return $this->user_id;
    }

    public function getClient_id(): int|null
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

}
