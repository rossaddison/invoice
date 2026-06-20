<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Client\Trait;

use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Invoice\Client\ClientRepository;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\ProductClient\ProductClient;
use Doctrine\Common\Collections\ArrayCollection;
use DateTimeImmutable;
use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait ClientTrait1
{

    /**
     * Returns the database identifier for this Client.
     *
     * @throws \LogicException if the entity has not been persisted yet.
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'Client');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function getClientEmail(): string
    {
        return $this->client_email;
    }

    public function setClientEmail(string $client_email): void
    {
        $this->client_email = $client_email;
    }

    public function getClientMobile(): ?string
    {
        return $this->client_mobile;
    }

    public function setClientMobile(string $client_mobile): void
    {
        $this->client_mobile = $client_mobile;
    }

    public function setClientDateCreated(string $client_date_created): void
    {
        /**
         * Related logic: see ImportController insertClients function
         */
        $this->client_date_created =
                new DateTimeImmutable()
                ->createFromFormat('Y-m-d h:i:s', $client_date_created) ?:
                new DateTimeImmutable('now');
    }

    public function getClientDateCreated(): DateTimeImmutable
    {
        return $this->client_date_created;
    }

    public function getClientDateModified(): DateTimeImmutable
    {
        return $this->client_date_modified;
    }

    // Used in ImportController to import Invoiceplane $client_date_modified
    public function setClientDateModified(string $client_date_modified): void
    {
        $this->client_date_modified =
                new DateTimeImmutable()
                ->createFromFormat('Y-m-d h:i:s', $client_date_modified) ?:
                new DateTimeImmutable('now');
    }

    public function getClientTitle(): ?string
    {
        return $this->client_title;
    }

    public function setClientTitle(?string $client_title): void
    {
        $this->client_title = $client_title;
    }

    public function setClientFullName(string $client_full_name): void
    {
        $this->client_full_name = $client_full_name;
    }

    public static function syncFullName(
        \Cycle\ORM\Entity\Behavior\Event\Mapper\Command\OnCreate|
        \Cycle\ORM\Entity\Behavior\Event\Mapper\Command\OnUpdate $event
    ): void {
        $client = $event->entity;
        assert($client instanceof self);
        $client->setClientFullName(ltrim(rtrim(
            $client->getClientName() . ' ' . ($client->getClientSurname() ?? '')
        )));
    }

    public function getClientFullName(): string
    {
        if (null == $this->client_full_name) {
            if (null !== $this->client_surname) {
                return ltrim(rtrim(
                    $this->client_name . ' ' . $this->client_surname
                ));
            }
            return ltrim(rtrim($this->client_name));
        }
        return $this->client_full_name;
    }
}
