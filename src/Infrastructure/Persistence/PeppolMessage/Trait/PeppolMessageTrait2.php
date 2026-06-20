<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PeppolMessage\Trait;

use DateTimeImmutable;

/**
 * @method int requireId(?int $id, string $context)
 */
trait PeppolMessageTrait2
{

    public function setSentAt(DateTimeImmutable $sent_at): void
    {
        $this->sent_at = $sent_at;
    }

    public function getDeliveredAt(): ?DateTimeImmutable
    {
        return $this->delivered_at;
    }

    public function setDeliveredAt(DateTimeImmutable $delivered_at): void
    {
        $this->delivered_at = $delivered_at;
    }

    public function getErrorMessage(): ?string
    {
        return $this->error_message;
    }

    public function setErrorMessage(string $error_message): void
    {
        $this->error_message = $error_message;
    }

    public function getRetryCount(): int
    {
        return $this->retry_count;
    }

    public function incrementRetryCount(): void
    {
        $this->retry_count++;
    }

    public function getUblXml(): ?string
    {
        return $this->ubl_xml;
    }

    public function setUblXml(string $ubl_xml): void
    {
        $this->ubl_xml = $ubl_xml;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }
}
