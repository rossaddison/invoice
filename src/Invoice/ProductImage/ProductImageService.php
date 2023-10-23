<?php

declare(strict_types=1);

namespace App\Invoice\ProductImage;

use App\Invoice\Entity\ProductImage;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\Files\FileHelper;

final class ProductImageService {

    private ProductImageRepository $repository;
    private SettingRepository $s;

    public function __construct(ProductImageRepository $repository, SettingRepository $s) {
        $this->repository = $repository;
        $this->s = $s;
    }

    /**
     * @param ProductImage $model
     * @param ProductImageForm $form
     * @return void
     */
    public function saveProductImage(ProductImage $model, ProductImageForm $form): void {
        $model->nullifyRelationOnChange((int) $form->getProduct_id());
        /** @psalm-suppress PossiblyNullArgument $form->getProduct_id()*/
        $model->setProduct_id($form->getProduct_id());
        $model->setFile_name_original($form->getFile_name_original());
        $model->setFile_name_new($form->getFile_name_new());

        $datehelper = new DateHelper($this->s);

        $datetime_uploaded = $datehelper->get_or_set_with_style(null !== $form->getUploaded_date() ? $form->getUploaded_date() : new \DateTime());
        $datetimeimmutable_uploaded = new \DateTimeImmutable($datetime_uploaded instanceof \DateTime ? $datetime_uploaded->format('Y-m-d H:i:s') : 'now');
        $model->setUploaded_date($datetimeimmutable_uploaded);

        $model->setDescription($form->getDescription());
        $this->repository->save($model);
    }

    /**
     * @param ProductImage $model
     * @param SettingRepository $sR
     * @return void
     */
    public function deleteProductImage(ProductImage $model, SettingRepository $sR): void {
      $aliases = $sR->get_productimages_files_folder_aliases();
      $targetPath = $aliases->get('@public_product_images');
      $file_path = $targetPath . '/' . $model->getFile_name_new();
      // see vendor/yiisoft/files/src/FileHelper::unlink will delete the file
      strpos(realpath($targetPath), realpath($file_path)) == 0 ? FileHelper::unlink($file_path) : '';
      $this->repository->delete($model);
    }
}
