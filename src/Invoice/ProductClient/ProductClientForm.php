<?php

declare(strict_types=1);

namespace App\Invoice\ProductClient;

use App\Infrastructure\Persistence\ProductClient\ProductClient;
use Yiisoft\FormModel\FormModel;
use DateTimeImmutable;

final class ProductClientForm extends FormModel
{
    private mixed $created_at = '';
    private mixed $updated_at = '';

    // New client creation fields
    private string $new_client_name = '';
    private string $new_client_surname = '';
    private string $new_client_email = '';
    private string $new_client_mobile = '';
    private string $new_client_group = '';

    public function __construct(
        ProductClient $productClient,
        private readonly ?int $product_id,
        private readonly ?int $client_id
    )
    {
        $this->created_at = $productClient->getCreatedAt();
        $this->updated_at = $productClient->getUpdatedAt();
    }

    public function getProductId(): ?int
    {
        return $this->product_id;
    }

    public function getClientId(): ?int
    {
        return $this->client_id;
    }

    public function getCreatedAt(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string|null $this->created_at
         */
        return $this->created_at;
    }

    public function getUpdatedAt(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string|null $this->updated_at
         */
        return $this->updated_at;
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

    // Getters and setters for new client fields
    public function getNewClientName(): string
    {
        return $this->new_client_name;
    }

    public function setNewClientName(string $value): void
    {
        $this->new_client_name = $value;
    }

    public function getNewClientSurname(): string
    {
        return $this->new_client_surname;
    }

    public function setNewClientSurname(string $value): void
    {
        $this->new_client_surname = $value;
    }

    public function getNewClientEmail(): string
    {
        return $this->new_client_email;
    }

    public function setNewClientEmail(string $value): void
    {
        $this->new_client_email = $value;
    }

    public function getNewClientMobile(): string
    {
        return $this->new_client_mobile;
    }

    public function setNewClientMobile(string $value): void
    {
        $this->new_client_mobile = $value;
    }

    public function getNewClientGroup(): string
    {
        return $this->new_client_group;
    }

    public function setNewClientGroup(string $value): void
    {
        $this->new_client_group = $value;
    }
}
