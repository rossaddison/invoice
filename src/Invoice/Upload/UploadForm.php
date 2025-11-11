<?php

declare(strict_types=1);

namespace App\Invoice\Upload;

use App\Invoice\Entity\Upload;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use DateTimeImmutable;

final class UploadForm extends FormModel
{
    private ?int $client_id = null;

    #[Required]
    private string $url_key = '';

    #[Required]
    private string $file_name_original = '';

    #[Required]
    private string $file_name_new = '';

    private string $description = '';

    private mixed $uploaded_date = '';

    public function __construct(Upload $upload)
    {
        $this->client_id = (int) $upload->getClient_id();
        $this->url_key = $upload->getUrl_key();
        $this->file_name_original = $upload->getFile_name_original();
        $this->file_name_new = $upload->getFile_name_new();
        $this->description = $upload->getDescription();
        $this->uploaded_date = $upload->getUploaded_date();
    }

    public function getClient_id(): ?int
    {
        return $this->client_id;
    }

    public function getUrl_key(): string
    {
        return $this->url_key;
    }

    public function getFile_name_original(): string
    {
        return $this->file_name_original;
    }

    public function getFile_name_new(): string
    {
        return $this->file_name_new;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getUploaded_date(): string|DateTimeImmutable
    {
        /**
         * @var DateTimeImmutable|string $this->uploaded_date
         */
        return $this->uploaded_date;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
