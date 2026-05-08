<?php

declare(strict_types=1);

namespace App\Invoice\UserClient;

use App\Infrastructure\Persistence\UserClient\UserClient;
use Yiisoft\FormModel\FormModel;

final class UserClientForm extends FormModel
{
    private ?int $user_id = null;
    private ?int $client_id = null;
    private ?string $user_all_clients = '';

    public static function show(UserClient $user_client): self
    {
        $form = new self();
        $form->user_id = $user_client->reqUserId();
        $form->client_id = $user_client->reqClientId();
        $form->user_all_clients = '0';
        return $form;
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
