<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\CustomField\Trait;

use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait CustomFieldTrait3
{

    public function setTextAreaRows(int $rows): void
    {
        $this->text_area_rows = $rows;
    }

    public function setTextAreaWrap(string $hardOrSoft): void
    {
        $this->text_area_wrap = $hardOrSoft;
    }

    public function getTextAreaWrap(): ?string
    {
        return $this->text_area_wrap;
    }

    public function getNumberMin(): ?int
    {
        return $this->number_min;
    }

    public function setNumberMin(int $min): void
    {
        $this->number_min = $min;
    }

    public function getUrlMinLength(): ?int
    {
        return $this->url_min_length;
    }

    public function setUrlMinLength(int $min): void
    {
        $this->url_min_length = $min;
    }

    public function getUrlMaxLength(): ?int
    {
        return $this->url_max_length;
    }

    public function setUrlMaxLength(int $max): void
    {
        $this->url_max_length = $max;
    }

    public function getNumberMax(): ?int
    {
        return $this->number_max;
    }

    public function setNumberMax(int $max): void
    {
        $this->number_max = $max;
    }
}
