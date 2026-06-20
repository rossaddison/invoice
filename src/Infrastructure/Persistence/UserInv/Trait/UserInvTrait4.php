<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\UserInv\Trait;

use DateTimeImmutable;
use App\Infrastructure\Persistence\User\User;
use Yiisoft\Translator\TranslatorInterface as Translator;
use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait UserInvTrait4
{

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(string $iban): void
    {
        $this->iban = $iban;
    }

    public function getGln(): ?int
    {
        return $this->gln;
    }

    public function setGln(int $gln): void
    {
        $this->gln = $gln;
    }

    public function getRcc(): ?string
    {
        return $this->rcc;
    }

    public function setRcc(string $rcc): void
    {
        $this->rcc = $rcc;
    }

    public function getListLimit(): ?int
    {
        return $this->listLimit;
    }

    public function setListLimit(int $listLimit): void
    {
        $this->listLimit = $listLimit;
    }

    public function getConsentPeriodicInvoice(): bool
    {
        return (bool) $this->consent_periodic_invoice;
    }

    public function setConsentPeriodicInvoice(bool $consent): void
    {
        $this->consent_periodic_invoice = $consent;
    }

    public function getConsentTelegramOutstanding(): bool
    {
        return (bool) $this->consent_telegram_outstanding;
    }

    public function setConsentTelegramOutstanding(bool $consent): void
    {
        $this->consent_telegram_outstanding = $consent;
    }

    public function getTelegramChatId(): ?string
    {
        return $this->telegram_chat_id;
    }

    public function setTelegramChatId(?string $telegram_chat_id): void
    {
        $this->telegram_chat_id = $telegram_chat_id;
    }
}
