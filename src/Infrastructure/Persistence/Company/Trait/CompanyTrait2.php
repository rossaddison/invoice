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
trait CompanyTrait2
{

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(string $zip): void
    {
        $this->zip = $zip;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getFax(): ?string
    {
        return $this->fax;
    }

    public function setFax(string $fax): void
    {
        $this->fax = $fax;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    // src/ViewInjection/CommonViewInjection
    public function getSeoDescription(): ?string
    {
        return $this->seo_description;
    }

    public function setSeoDescription(string $seoDescription): void
    {
        $this->seo_description = $seoDescription;
    }

    public function getWeb(): ?string
    {
        return $this->web;
    }

    public function setWeb(string $web): void
    {
        $this->web = $web;
    }

    public function getSlack(): ?string
    {
        return $this->slack;
    }

    public function setSlack(string $slack): void
    {
        $this->slack = $slack;
    }
}
