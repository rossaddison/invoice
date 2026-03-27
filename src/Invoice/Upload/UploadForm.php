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
        $this->client_id = (int) $upload->getClientId();
        $this->url_key = $upload->getUrlKey();
        $this->file_name_original = $upload->getFileNameOriginal();
        $this->file_name_new = $upload->getFileNameNew();
        $this->description = $upload->getDescription();
        $this->uploaded_date = $upload->getUploadedDate();
    }

    public function getClientId(): ?int
    {
        return $this->client_id;
    }

    public function getUrlKey(): string
    {
        return $this->url_key;
    }

    public function getFileNameOriginal(): string
    {
        return $this->file_name_original;
    }

    public function getFileNameNew(): string
    {
        return $this->file_name_new;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getUploadedDate(): string|DateTimeImmutable
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
