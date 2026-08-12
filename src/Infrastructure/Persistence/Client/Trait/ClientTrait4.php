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
trait ClientTrait4
{

    public function getClientLanguage(): ?string
    {
        return $this->client_language;
    }

    public function setClientLanguage(string $client_language): void
    {
        $this->client_language = $client_language;
    }

    public function getClientActive(): bool
    {
        return $this->client_active;
    }

    public function setClientActive(bool $client_active): void
    {
        $this->client_active = $client_active;
    }

    public function getClientBirthdate(): DateTimeImmutable|string|null
    {
        /** @var DateTimeImmutable|string|null $this->client_birthdate */
        return $this->client_birthdate;
    }

    public function setClientBirthdate(?DateTimeImmutable $client_birthdate): void
    {
        $this->client_birthdate = $client_birthdate;
    }

    public function getClientAge(): int
    {
        return $this->client_age;
    }

    public function setClientAge(int $client_age): void
    {
        $this->client_age = $client_age;
    }

    public function getClientNumber(): ?string
    {
        return $this->client_number;
    }

    public function setClientNumber(?string $client_number): void
    {
        $this->client_number = $client_number;
    }

    public function getClientGender(): int
    {
        return $this->client_gender;
    }

    public function setClientGender(int $client_gender): void
    {
        $this->client_gender = $client_gender;
    }

    public function setPostaladdressId(int $postaladdress_id): void
    {
        $this->postaladdress_id = $postaladdress_id;
    }

    public function getPostaladdressId(): ?int
    {
        return $this->postaladdress_id;
    }

    /**
     * The single Dwelling (house on a HomeCare street) this Client currently occupies, if any. Plain
     * scalar FK, deliberately not a Cycle #[BelongsTo] relation — see
     * {@see \App\Infrastructure\Persistence\Dwelling\Dwelling} class docblock for why, and for the
     * accepted one-Client-to-at-most-one-Dwelling trade-off (no multi-property-landlord support).
     */
    public function setDwellingId(int $dwelling_id): void
    {
        $this->dwelling_id = $dwelling_id;
    }

    public function getDwellingId(): ?int
    {
        return $this->dwelling_id;
    }

    public function getClientTelegramChatId(): ?string
    {
        return $this->client_telegram_chat_id;
    }

    public function setClientTelegramChatId(?string $client_telegram_chat_id): void
    {
        $this->client_telegram_chat_id = $client_telegram_chat_id === '' ? null : $client_telegram_chat_id;
    }

    public function getClientQrToken(): ?string
    {
        return $this->client_qr_token;
    }

    public function setClientQrToken(?string $client_qr_token): void
    {
        $this->client_qr_token = $client_qr_token === '' ? null : $client_qr_token;
    }

    public function getHomecareAutoInvoicePaused(): bool
    {
        return $this->homecare_auto_invoice_paused;
    }

    public function setHomecareAutoInvoicePaused(bool $homecare_auto_invoice_paused): void
    {
        $this->homecare_auto_invoice_paused = $homecare_auto_invoice_paused;
    }
}
