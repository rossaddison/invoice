<?php

declare(strict_types=1);

namespace App\Invoice\ProductImage;

use App\Invoice\Entity\ProductImage;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
use DateTimeImmutable;

final class ProductImageForm extends FormModel
{
    private ?int $product_id = null;
    #[Required]
    private string $file_name_original = '';
    #[Required]
    private string $file_name_new = '';

    private string $description = '';

    #[Required]
    private mixed $uploaded_date = '';

    public function __construct(ProductImage $productImage, int $product_id)
    {
        $this->product_id = $product_id;
        $this->file_name_original = $productImage->getFile_name_original();
        $this->file_name_new = $productImage->getFile_name_new();
        $this->description = $productImage->getDescription();
        $this->uploaded_date = $productImage->getUploaded_date();
    }

    public function getProduct_id(): int|null
    {
        return $this->product_id;
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
    public function getFormName(): string
    {
        return '';
    }
}
