<?php

declare(strict_types=1);

namespace App\Invoice\Delivery;

use App\Infrastructure\Persistence\Delivery\Delivery;
use Yiisoft\FormModel\FormModel;
use DateTimeImmutable;

final class DeliveryForm extends FormModel
{
    private mixed $date_created = '';
    private mixed $date_modified = '';
    private mixed $start_date = '';
    private mixed $actual_delivery_date = '';
    private mixed $end_date = '';
    private ?int $delivery_location_id = null;
    private ?int $delivery_party_id = null;
    private ?int $inv_id = null;
    private ?int $inv_item_id = null;

    public static function show(Delivery $delivery): self
    {
        $form = new self();
        $form->date_created = $delivery->getDateCreated();
        $form->date_modified = $delivery->getDateModified();
        $form->start_date = $delivery->getStartDate();
        $form->actual_delivery_date = $delivery->getActualDeliveryDate();
        $form->end_date = $delivery->getEndDate();
        $form->delivery_location_id = (int) $delivery->getDeliveryLocationId();
        $form->delivery_party_id = (int) $delivery->getDeliveryPartyId();
        $form->inv_id = $delivery->getInvId();
        $form->inv_item_id = $delivery->getInvItemId();
        return $form;
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
