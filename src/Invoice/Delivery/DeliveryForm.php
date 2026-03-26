<?php

declare(strict_types=1);

namespace App\Invoice\Delivery;

use App\Invoice\Entity\Delivery;
use Yiisoft\FormModel\FormModel;
use DateTimeImmutable;

final class DeliveryForm extends FormModel
{
    private readonly mixed $date_created;
    private readonly mixed $date_modified;
    private readonly mixed $start_date;
    private readonly mixed $actual_delivery_date;
    private readonly mixed $end_date;
    private ?int $id = null;
    private ?int $delivery_location_id = null;
    private ?int $delivery_party_id = null;
    private ?int $inv_id = null;
    private ?int $inv_item_id = null;

    public function __construct(Delivery $delivery)
    {
        $this->id = $delivery->getId();
        $this->date_created = $delivery->getDateCreated();
        $this->date_modified = $delivery->getDateModified();
        $this->start_date = $delivery->getStartDate();
        $this->actual_delivery_date = $delivery->getActualDeliveryDate();
        $this->end_date = $delivery->getEndDate();
        $this->delivery_location_id = (int) $delivery->getDeliveryLocationId();
        $this->delivery_party_id = (int) $delivery->getDeliveryPartyId();
        $this->inv_id = $delivery->getInvId();
        $this->inv_item_id = $delivery->getInvItemId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateCreated(): string|DateTimeImmutable
    {
        /**
         * @var DateTimeImmutable|string $this->date_created
         */
        return $this->date_created;
    }

    public function getDateModified(): string|DateTimeImmutable
    {
        /**
         * @var DateTimeImmutable|string $this->date_modified
         */
        return $this->date_modified;
    }

    public function getStartDate(): string|DateTimeImmutable
    {
        /**
         * @var DateTimeImmutable|string $this->start_date
         */
        return $this->start_date;
    }

    public function getActualDeliveryDate(): string|DateTimeImmutable
    {
        /**
         * @var DateTimeImmutable|string $this->actual_delivery_date
         */
        return $this->actual_delivery_date;
    }

    public function getEndDate(): string|DateTimeImmutable
    {
        /**
         * @var DateTimeImmutable|string $this->end_date
         */
        return $this->end_date;
    }

    public function getDeliveryLocationId(): ?int
    {
        return $this->delivery_location_id;
    }

    public function getDeliveryPartyId(): ?int
    {
        return $this->delivery_party_id;
    }

    public function getInvId(): ?int
    {
        return $this->inv_id;
    }

    public function getInvItemId(): ?int
    {
        return $this->inv_item_id;
    }

    /**
     * @return string
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
