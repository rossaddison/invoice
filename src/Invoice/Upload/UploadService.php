<?php

declare(strict_types=1);

namespace App\Invoice\Upload;

use App\Invoice\Entity\Upload;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\Files\FileHelper;

final class UploadService
{
    private UploadRepository $repository;

    public function __construct(UploadRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param Upload $model
     * @param array $array
     * @return void
     */
    public function saveUpload(Upload $model, array $array): void
    {
        $model->nullifyRelationOnChange((int) $array['client_id']);
        /** @psalm-suppress PossiblyNullArgument $array['client_id'] */
        isset($array['client_id']) ? $model->setClient_id((int)$array['client_id']) : '';
        isset($array['url_key']) ? $model->setUrl_key((string)$array['url_key']) : '';
        isset($array['file_name_original']) ? $model->setFile_name_original((string)$array['file_name_original']) : '';
        isset($array['file_name_new']) ? $model->setFile_name_new((string)$array['file_name_new']) : '';

        $uploadedDate = (new \DateTimeImmutable())::createFromFormat('Y-m-d', (string)$array['uploaded_date']);
        $uploadedDate ? $model->setUploaded_date($uploadedDate) : '';

        isset($array['description']) ? $model->setDescription((string)$array['description']) : '';
        $this->repository->save($model);
    }

    /**
     * @param Upload $model
     * @param SettingRepository $sR
     * @return void
     */
    public function deleteUpload(Upload $model, SettingRepository $sR): void
    {
        $aliases = $sR->get_customer_files_folder_aliases();
        $targetPath = $aliases->get('@customer_files');
        $file_path = $targetPath . '/' . $model->getFile_name_new();
        // see vendor/yiisoft/files/src/FileHelper::unlink will delete the file
        strpos(realpath($targetPath), realpath($file_path)) == 0 ? FileHelper::unlink($file_path) : '';
        $this->repository->delete($model);
    }
}
