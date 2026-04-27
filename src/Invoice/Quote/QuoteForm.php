<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Infrastructure\Persistence\Quote\Quote;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use DateTimeImmutable;

final class QuoteForm extends FormModel
{
    private ?string $number = '';
    private mixed $date_created = '';
    private ?int $inv_id = null;
    private ?int $so_id = null;

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

    public static function show(Quote $quote): self
    {
        $form = new self();
        $form->number = $quote->getNumber();
        $form->date_created = $quote->getDateCreated();
        $form->inv_id = $quote->getInvId();
        $form->so_id = $quote->getSoId();
        $form->group_id = $quote->reqGroupId();
        $form->client_id = $quote->reqClientId();
        $form->status_id = $quote->reqStatusId();
        $form->discount_amount = $quote->getDiscountAmount();
        $form->url_key = $quote->getUrlKey();
        $form->password = $quote->getPassword();
        $form->notes = $quote->getNotes();
        $form->delivery_location_id = $quote->getDeliveryLocationId();
        return $form;
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

    public function getInvId(): ?int
    {
        return $this->inv_id;
    }

    public function getSoId(): ?int
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
