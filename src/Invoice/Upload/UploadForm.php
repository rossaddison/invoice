<?php

declare(strict_types=1);

namespace App\Invoice\Upload;

use App\Infrastructure\Persistence\Upload\Upload;
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

    public static function show(Upload $upload): self
    {
        $form = new self();
        $form->client_id = $upload->reqClientId();
        $form->url_key = $upload->getUrlKey();
        $form->file_name_original = $upload->getFileNameOriginal();
        $form->file_name_new = $upload->getFileNameNew();
        $form->description = $upload->getDescription();
        $form->uploaded_date = $upload->getUploadedDate();
        return $form;
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
