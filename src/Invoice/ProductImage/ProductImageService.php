<?php

declare(strict_types=1);

namespace App\Invoice\ProductImage;

use App\Invoice\Entity\ProductImage;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\Files\FileHelper;

final readonly class ProductImageService
{
    public function __construct(private ProductImageRepository $repository, private SettingRepository $s)
    {
    }

    public function saveProductImage(ProductImage $model, array $array): void
    {
        $model->nullifyRelationOnChange((int) $array['product_id']);

        $datetime_created = new \DateTimeImmutable();
        $model->setUploaded_date(
            $datetime_created::createFromFormat('Y-m-d', (string) $array['uploaded_date'])
            ?: new \DateTimeImmutable('now'),
        );

        isset($array['product_id']) ? $model->setProduct_id((int) $array['product_id']) : '';
        isset($array['file_name_original']) ? $model->setFile_name_original((string) $array['file_name_original']) : '';
        isset($array['file_name_new']) ? $model->setFile_name_new((string) $array['file_name_new']) : '';
        isset($array['description']) ? $model->setDescription((string) $array['description']) : '';

        $this->repository->save($model);
    }

    public function deleteProductImage(ProductImage $model, SettingRepository $sR): void
    {
        $aliases    = $sR->get_productimages_files_folder_aliases();
        $targetPath = $aliases->get('@public_product_images');
        $file_path  = $targetPath.'/'.$model->getFile_name_new();
        // see vendor/yiisoft/files/src/FileHelper::unlink will delete the file
        $realTargetPath = realpath($targetPath);
        $realFilePath   = realpath($file_path);
        if ((false != $realTargetPath) && (false != $realFilePath)) {
            str_starts_with($realTargetPath, $realFilePath) ? FileHelper::unlink($file_path) : '';
            $this->repository->delete($model);
        }
    }
}
