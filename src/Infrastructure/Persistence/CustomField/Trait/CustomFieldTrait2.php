<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\CustomField\Trait;

use RequireId;

/**
 * @method int requireId(?int $id, string $context)
 */
trait CustomFieldTrait2
{

    public function setEmailMinLength(int $min): void
    {
        $this->email_min_length = $min;
    }

    public function getEmailMaxLength(): ?int
    {
        return $this->email_max_length;
    }

    public function setEmailMaxLength(int $max): void
    {
        $this->email_max_length = $max;
    }

    public function getEmailMultiple(): bool
    {
        return $this->email_multiple;
    }

    public function setEmailMultiple(bool $multiple): void
    {
        $this->email_multiple = $multiple;
    }

    public function getTextMinLength(): ?int
    {
        return $this->text_min_length;
    }

    public function setTextMinLength(int $min): void
    {
        $this->text_min_length = $min;
    }

    public function getTextMaxLength(): ?int
    {
        return $this->text_max_length;
    }

    public function setTextMaxLength(int $max): void
    {
        $this->text_max_length = $max;
    }

    public function getTextAreaMinLength(): ?int
    {
        return $this->text_area_min_length;
    }

    public function setTextAreaMinLength(int $min): void
    {
        $this->text_area_min_length = $min;
    }

    public function getTextAreaMaxLength(): ?int
    {
        return $this->text_area_max_length;
    }

    public function setTextAreaMaxLength(int $max): void
    {
        $this->text_area_max_length = $max;
    }

    public function getTextAreaCols(): ?int
    {
        return $this->text_area_cols;
    }

    public function setTextAreaCols(int $cols): void
    {
        $this->text_area_cols = $cols;
    }

    public function getTextAreaRows(): ?int
    {
        return $this->text_area_rows;
    }
}
