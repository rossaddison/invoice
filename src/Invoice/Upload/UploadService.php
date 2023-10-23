<?php

declare(strict_types=1);

namespace App\Invoice\Upload;

use App\Invoice\Entity\Upload;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\Files\FileHelper;

final class UploadService {

    private UploadRepository $repository;
    private SettingRepository $s;

    public function __construct(UploadRepository $repository, SettingRepository $s) {
        $this->repository = $repository;
        $this->s = $s;
    }

    /**
     * @param Upload $model
     * @param UploadForm $form
     * @return void
     */
    public function saveUpload(Upload $model, UploadForm $form): void {
        $model->nullifyRelationOnChange((int) $form->getClient_id());
        /** @psalm-suppress PossiblyNullArgument $form->getClient_id()*/
        $model->setClient_id($form->getClient_id());
        $model->setUrl_key($form->getUrl_key());
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
     * @param Upload $model
     * @param SettingRepository $sR
     * @return void
     */
    public function deleteUpload(Upload $model, SettingRepository $sR): void {
        $aliases = $sR->get_customer_files_folder_aliases();
        $targetPath = $aliases->get('@customer_files');
        $file_path = $targetPath . '/' . $model->getFile_name_new();
        // see vendor/yiisoft/files/src/FileHelper::unlink will delete the file
        strpos(realpath($targetPath), realpath($file_path)) == 0 ? FileHelper::unlink($file_path) : '';
        $this->repository->delete($model);
    }

}
