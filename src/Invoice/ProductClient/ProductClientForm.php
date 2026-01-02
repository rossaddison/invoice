<?php

declare(strict_types=1);

namespace App\Invoice\ProductClient;

use App\Invoice\Entity\ProductClient;
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
    public function getNew_client_name(): string
    {
        return $this->new_client_name;
    }
    
    public function setNew_client_name(string $value): void
    {
        $this->new_client_name = $value;
    }
    
    public function getNew_client_surname(): string
    {
        return $this->new_client_surname;
    }
    
    public function setNew_client_surname(string $value): void
    {
        $this->new_client_surname = $value;
    }
    
    public function getNew_client_email(): string
    {
        return $this->new_client_email;
    }
    
    public function setNew_client_email(string $value): void
    {
        $this->new_client_email = $value;
    }
    
    public function getNew_client_mobile(): string
    {
        return $this->new_client_mobile;
    }
    
    public function setNew_client_mobile(string $value): void
    {
        $this->new_client_mobile = $value;
    }
    
    public function getNew_client_group(): string
    {
        return $this->new_client_group;
    }
    
    public function setNew_client_group(string $value): void
    {
        $this->new_client_group = $value;
    }
}
