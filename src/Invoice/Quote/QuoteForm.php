<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\Entity\Quote;
use DateTimeImmutable;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class QuoteForm extends FormModel
{
    private ?string $number     = '';
    private mixed $date_created = '';
    private ?string $inv_id     = null;
    private ?string $so_id      = null;

    #[Required]
    private ?int $group_id = null;
    #[Required]
    private ?int $client_id = null;

    private ?int $status_id          = 1;
    private ?float $discount_amount  = 0;
    private ?float $discount_percent = 0;
    private ?string $url_key         = '';
    private ?string $password        = '';
    private ?string $notes           = '';

    private ?int $delivery_location_id = null;

    public function __construct(Quote $quote)
    {
        $this->number               = $quote->getNumber();
        $this->date_created         = $quote->getDate_created();
        $this->inv_id               = $quote->getInv_id();
        $this->so_id                = $quote->getSo_id();
        $this->group_id             = (int) $quote->getGroup_id();
        $this->client_id            = (int) $quote->getClient_id();
        $this->status_id            = $quote->getStatus_id();
        $this->discount_amount      = $quote->getDiscount_amount();
        $this->discount_percent     = $quote->getDiscount_percent();
        $this->url_key              = $quote->getUrl_key();
        $this->password             = $quote->getPassword();
        $this->notes                = $quote->getNotes();
        $this->delivery_location_id = (int) $quote->getDelivery_location_id();
    }

    public function getDate_created(): string|\DateTimeImmutable|null
    {
        /*
         * @var DateTimeImmutable|string|null $this->date_created
         */
        return $this->date_created;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function getInv_id(): ?string
    {
        return $this->inv_id;
    }

    // The Entities ie. Entity/Quote.php have return type string => return type strings in the form
    // get => string ;
    public function getSo_id(): ?string
    {
        return $this->so_id;
    }

    public function getGroup_id(): ?int
    {
        return $this->group_id;
    }

    public function getClient_id(): ?int
    {
        return $this->client_id;
    }

    public function getStatus_id(): ?int
    {
        return $this->status_id;
    }

    public function getDelivery_location_id(): ?int
    {
        return $this->delivery_location_id;
    }

    public function getDiscount_amount(): ?float
    {
        return $this->discount_amount;
    }

    public function getDiscount_percent(): ?float
    {
        return $this->discount_percent;
    }

    public function getUrl_key(): ?string
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
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
