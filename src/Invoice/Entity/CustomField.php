<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: \App\Invoice\CustomField\CustomFieldRepository::class)]
class CustomField
{
    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $table = '',
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $label = '',
        #[Column(type: 'string(151)', nullable: false, default: 'TEXT')]
        private string $type = '',
        #[Column(type: 'integer(11)', nullable: true, default: 0)]
        private ?int $location = null,
        #[Column(type: 'integer(11)', nullable: true, default: 999)]
        private ?int $order = null,
        #[Column(type: 'bool', default: true)]
        private bool $required = false,
        #[Column(type: 'integer(11)', nullable: true, default: 0)]
        private ?int $email_min_length = null,
        #[Column(type: 'integer(11)', nullable: true, default: 100)]
        private ?int $email_max_length = null,
        #[Column(type: 'bool', default: false)]
        private bool $email_multiple = false,
        #[Column(type: 'integer(11)', nullable: true, default: 0)]
        private ?int $url_min_length = null,
        #[Column(type: 'integer(11)', nullable: true, default: 150)]
        private ?int $url_max_length = null,
        #[Column(type: 'integer(11)', nullable: true, default: 0)]
        private ?int $number_min = null,
        #[Column(type: 'integer(11)', nullable: true, default: 100)]
        private ?int $number_max = null,
        #[Column(type: 'integer(11)', nullable: true, default: 0)]
        private ?int $text_min_length = null,
        #[Column(type: 'integer(11)', nullable: true, default: 150)]
        private ?int $text_max_length = null,
        #[Column(type: 'integer(11)', nullable: true, default: 0)]
        private ?int $text_area_min_length = null,
        #[Column(type: 'integer(11)', nullable: true, default: 150)]
        private ?int $text_area_max_length = null,
        #[Column(type: 'integer(11)', nullable: true, default: 10)]
        private ?int $text_area_cols = null,
        #[Column(type: 'integer(11)', nullable: true, default: 10)]
        private ?int $text_area_rows = null,
        #[Column(type: 'string(4)', nullable: true)]
        private ?string $text_area_wrap = 'hard',
    ) {
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getLocation(): ?int
    {
        return $this->location;
    }

    public function setLocation(int $location): void
    {
        $this->location = $location;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function setOrder(int $order): void
    {
        $this->order = $order;
    }

    public function getRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): void
    {
        $this->required = $required;
    }

    public function getEmailMinLength(): ?int
    {
        return $this->email_min_length;
    }

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
