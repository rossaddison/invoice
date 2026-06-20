<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Company\Trait;

use App\Infrastructure\Persistence\CompanyPrivate\CompanyPrivate;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait CompanyTrait3
{

    public function getTwitter(): ?string
    {
        return $this->twitter;
    }

    public function setTwitter(string $twitter): void
    {
        $this->twitter = $twitter;
    }

    public function getFacebook(): ?string
    {
        return $this->facebook;
    }

    public function setFacebook(string $facebook): void
    {
        $this->facebook = $facebook;
    }

    public function getLinkedIn(): ?string
    {
        return $this->linkedin;
    }

    public function setLinkedIn(string $linkedin): void
    {
        $this->linkedin = $linkedin;
    }

    public function getWhatsapp(): ?string
    {
        return $this->whatsapp;
    }

    public function setWhatsapp(string $whatsapp): void
    {
        $this->whatsapp = $whatsapp;
    }

    public function getArbitrationBody(): ?string
    {
        return $this->arbitrationBody;
    }

    public function setArbitrationBody(string $arbitrationBody): void
    {
        $this->arbitrationBody = $arbitrationBody;
    }

    public function getArbitrationJurisdiction(): ?string
    {
        return $this->arbitrationJurisdiction;
    }

    public function setArbitrationJurisdiction(string $arbitrationJurisdiction): void
    {
        $this->arbitrationJurisdiction = $arbitrationJurisdiction;
    }

    public function getDateCreated(): DateTimeImmutable
    {
        return $this->date_created;
    }

    public function getDateModified(): DateTimeImmutable
    {
        return $this->date_modified;
    }
}
