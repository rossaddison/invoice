<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\Entity\Quote;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use DateTimeImmutable;

final class QuoteForm extends FormModel
{
    private ?string $number = '';
    private mixed $date_created = '';
    private ?string $inv_id = null;
    private ?string $so_id = null;

    #[Required]
    private ?int $group_id = null;
    #[Required]
    private ?int $client_id = null;

    private ?int $status_id = 1;
    private ?float $discount_amount = 0;
    private ?string $url_key = '';
    private ?string $password = '';
    private ?string $notes = '';

    private ?int $delivery_location_id = null;

    public function __construct(Quote $quote)
    {
        $this->number = $quote->getNumber();
        $this->date_created = $quote->getDateCreated();
        $this->inv_id = $quote->getInvId();
        $this->so_id = $quote->getSoId();
        $this->group_id = (int) $quote->getGroupId();
        $this->client_id = (int) $quote->getClientId();
        $this->status_id = $quote->getStatusId();
        $this->discount_amount = $quote->getDiscountAmount();
        $this->url_key = $quote->getUrlKey();
        $this->password = $quote->getPassword();
        $this->notes = $quote->getNotes();
        $this->delivery_location_id = (int) $quote->getDeliveryLocationId();
    }

    public function getDateCreated(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string|null $this->date_created
         */
        return $this->date_created;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function getInvId(): ?string
    {
        return $this->inv_id;
    }

    // The Entities ie. Entity/Quote.php have return type string => return type strings in the form
    // get => string ;
    public function getSoId(): ?string
    {
        return $this->so_id;
    }

    public function getGroupId(): ?int
    {
        return $this->group_id;
    }

    public function getClientId(): ?int
    {
        return $this->client_id;
    }

    public function getStatusId(): ?int
    {
        return $this->status_id;
    }

    public function getDeliveryLocationId(): ?int
    {
        return $this->delivery_location_id;
    }

    public function getDiscountAmount(): ?float
    {
        return $this->discount_amount;
    }

    public function getUrlKey(): ?string
    {
        return $this->url_key;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
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
