<?php

declare(strict_types=1);

namespace App\Invoice\ProductImage;

use App\Invoice\Entity\ProductImage;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\Files\FileHelper;

final readonly class ProductImageService
{
    public function __construct(
        private ProductImageRepository $repository,
        private SettingRepository $s,
        private PR $pR,
    ) {
    }

    /**
     * @param ProductImage $model
     * @param array $array
     */
    public function saveProductImage(
        ProductImage $model,
        array $array
    ): void {
        $this->persist($model, $array);

        $datetime_created = new \DateTimeImmutable();
        $model->setUploadedDate(
            $datetime_created::createFromFormat(
                'Y-m-d', 
                (string) $array['uploaded_date'])
            ?: new \DateTimeImmutable('now'),
        );

        isset($array['product_id']) ? 
            $model->setProductId(
                (int) $array['product_id']) : '';
        isset($array['file_name_original']) ? 
            $model->setFileNameOriginal(
                (string) $array['file_name_original']) : '';
        isset($array['file_name_new']) ? 
            $model->setFileNameNew(
                (string) $array['file_name_new']) : '';
        isset($array['description']) ? 
            $model->setDescription(
                (string) $array['description']) : '';

        $this->repository->save($model);
    }

    private function persist(
        ProductImage $model,
        array $array
    ): void {
        $product = 'product_id';
        if (isset($array[$product])) {
            $productEntity = $this->pR->repoProductquery(
                (string) $array[$product]);
            if ($productEntity) {
                $model->setProduct($productEntity);
            }
        }
    }

    /**
     * @param ProductImage $model
     * @param SettingRepository $sR
     */
    public function deleteProductImage(
        ProductImage $model,
        SettingRepository $sR
    ): void {
        $aliases = $sR->getProductimagesFilesFolderAliases();
        $targetPath = $aliases->get('@public_product_images');
        $file_path = $targetPath . '/' 
            . $model->getFileNameNew();
        // see vendor/yiisoft/files/src/FileHelper::unlink 
        // will delete the file
        $realTargetPath = realpath($targetPath);
        $realFilePath = realpath($file_path);
        if (($realTargetPath != false) 
            && ($realFilePath != false)) {
            str_starts_with($realTargetPath, $realFilePath) ? 
                FileHelper::unlink($file_path) : '';
            $this->repository->delete($model);
        }
    }
}
