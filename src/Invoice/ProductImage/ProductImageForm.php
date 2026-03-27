<?php

declare(strict_types=1);

namespace App\Invoice\ProductImage;

use App\Invoice\Entity\ProductImage;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use DateTimeImmutable;

final class ProductImageForm extends FormModel
{
    #[Required]
    private string $file_name_original = '';
    #[Required]
    private string $file_name_new = '';

    private string $description = '';

    #[Required]
    private mixed $uploaded_date = '';

    public function __construct(ProductImage $productImage, private readonly ?int $product_id)
    {
        $this->file_name_original = $productImage->getFileNameOriginal();
        $this->file_name_new = $productImage->getFileNameNew();
        $this->description = $productImage->getDescription();
        $this->uploaded_date = $productImage->getUploadedDate();
    }

    public function getProductId(): ?int
    {
        return $this->product_id;
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
