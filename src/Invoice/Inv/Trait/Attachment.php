<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\{
    Entity\Inv, Entity\Upload,
    Inv\InvRepository as IR,
    Upload\UploadRepository as UPR,
    UserClient\UserClientRepository as UCR,
    UserInv\UserInvRepository as UIR
};

use Psr\Http\Message\ResponseInterface as Response;

use Yiisoft\Router\HydratorAttribute\RouteArgument;

trait Attachment
{
    private function attachmentMoveTo(
        string $tmp,
        string $target,
        int $client_id,
        string $url_key,
        string $fileName,
        UPR $uPR,
    ): bool {
        $file_exists = file_exists($target);
// The file does not exist yet in the target path but it exists in the tmp folder
// on the server
        if (!$file_exists) {
// Record the details of this upload
// (Related logic:
//  see https://www.php.net/manual/en/function.is-uploaded-file.php)
// Returns true if the file named by filename was uploaded via HTTP POST.
// This is useful to help ensure that a malicious user hasn't tried to trick
// the script into working on files upon which it should not be working--for
// instance, /etc/passwd. This sort of check is especially important if there
// is any chance that anything done with uploaded files could reveal their
// contents to the user, or even to other users on the same system. For proper
// working, the function isUploadedFile() needs an argument like
// $_FILES['userfile']['tmp_name'], - the name of the uploaded file on the
// client's machine
// $_FILES['userfile']['name'] does not work.
            if (is_uploaded_file($tmp) && move_uploaded_file($tmp, $target)) {
                $track_file = new Upload();
                $track_file->setClientId($client_id);
                $track_file->setUrlKey($url_key);
                $track_file->setFileNameOriginal($fileName);
                $track_file->setFileNameNew($url_key . '_' . $fileName);
                $track_file->setUploadedDate(new \DateTimeImmutable());
                $uPR->save($track_file);
                return true;
            }
                $this->flashMessage('warning',
                    $this->translator->translate('possible.file.upload.attack')
                        . $tmp);
            return false;
        }
        $this->flashMessage('warning',
                $this->translator->translate('error.duplicate.file'));
        return false;
    }

    private function attachmentNotWritable(int $inv_id): Response
    {
        $this->flashMessage('danger', $this->translator->translate('path')
                . $this->translator->translate('is.not.writable'));
        return $this->webService->getRedirectResponse('inv/view',
                ['id' => $inv_id]);
    }

    private function attachmentSuccessfullyCreated(int $inv_id): Response
    {
        $this->flashMessage('success',
                $this->translator->translate('record.successfully.created'));
        return $this->webService->getRedirectResponse('inv/view',
                ['id' => $inv_id]);
    }

    private function attachmentNoFileUploaded(int $inv_id): Response
    {
        $this->flashMessage('warning',
                $this->translator->translate('no.file.uploaded'));
        return $this->webService->getRedirectResponse('inv/view',
                ['id' => $inv_id]);
    }

    public function attachment(#[RouteArgument('id')] int $inv_id, IR $iR,
            UPR $uPR): Response
    {
        $aliases = $this->sR->getCustomerFilesFolderAliases();
        $targetPath = $aliases->get('@customer_files');
        if ($inv_id) {
            if (!is_writable($targetPath)) {
                return $this->attachmentNotWritable($inv_id);
            }
            $invoice = $iR->repoInvLoadedquery((string) $inv_id) ?: null;
            if ($invoice instanceof Inv) {
                $client_id = $invoice->getClient()?->reqClientId();
                if (null !== $client_id) {
                    $url_key = $invoice->getUrlKey();
                    if (!empty($_FILES)) {
// Related logic:
//  see https://github.com/vimeo/psalm/issues/5458
/** @var array $_FILES['InvAttachmentsForm'] */
/** @var string $_FILES['InvAttachmentsForm']['tmp_name']['attachFile'] */
$temporary_file = $_FILES['InvAttachmentsForm']['tmp_name']['attachFile'];
/** @var string $_FILES['InvAttachmentsForm']['name']['attachFile'] */
$original_file_name = preg_replace(
    '/\s+/', '_', $_FILES['InvAttachmentsForm']['name']['attachFile']);
                        if (null!==($originalFileName = $original_file_name) && (strlen($originalFileName) > 0)
                            && (strlen($temporary_file) > 0)) {
                            $target_path_with_filename =
                                $targetPath . '/' . $url_key . '_'
                                    . $originalFileName;
                            if ($this->attachmentMoveTo($temporary_file,
                                    $target_path_with_filename, $client_id,
                                        $url_key,
                                    $originalFileName, $uPR)) {
                                return $this->attachmentSuccessfullyCreated($inv_id);
                            }
                            return $this->attachmentNoFileUploaded($inv_id);
                        }
                    } else {
                        return $this->attachmentNoFileUploaded($inv_id);
                    }
                } // $client_id
            } // $invoice
            return $this->webService->getRedirectResponse('inv/index');
        } //null!==$inv_id
        return $this->webService->getRedirectResponse('inv/index');
    }
    
    /**
     * Use: Download an attached, and currently uploaded file
     * @param int $upload_id
     * @param IR $iR,
     * @param UCR $ucR
     * @param UIR $uiR
     * @param UPR $upR
     *
     * @return never
     */
    public function downloadFile(#[RouteArgument('upload_id')] int $upload_id,
        IR $iR, UCR $ucR, UIR $uiR, UPR $upR) : never
    {
        $cC = 'Cache-Control: public, must-revalidate, post-check=0, pre-check=0';
        if ($upload_id) {
            $upload = $upR->repoUploadquery((string) $upload_id);
            if (null !== $upload) {
                $url_key = $upload->getUrlKey();
                $inv = $iR->repoUrlKeyGuestLoaded($url_key);
                if (null!==$inv) {
                    if (($this->rbacObserver($inv, $ucR, $uiR))
                        || ($this->rbacAdmin())) {
                    } else {
                        exit;
                    }
                }
                $aliases = $this->sR->getCustomerFilesFolderAliases();
                $targetPath = $aliases->get('@customer_files');
                $original_file_name = $upload->getFileNameOriginal();
                $target_path_with_filename = $targetPath . '/' . $url_key
                    . '_' . $original_file_name;
                $path_parts = pathinfo($target_path_with_filename);
                $file_ext = $path_parts['extension'] ?? '';
                if (file_exists($target_path_with_filename)) {
                    $file_size = filesize($target_path_with_filename);
                    if ($file_size != false) {
                        $allowed_content_type_array = $upR->getContentTypes();
                        // Check extension against allowed content file types
                        // Related logic: see UploadRepository getContentTypes
                        $save_ctype =
                            isset($allowed_content_type_array[$file_ext]);
                        /** @var string $ctype */
                        $ctype = $save_ctype ?
                            $allowed_content_type_array[$file_ext] :
                                $upR->getContentTypeDefaultOctetStream();
                        // https://www.php.net/manual/en/function.header.php
                        // Remember that header() must be called before any
                        // actual output is sent, either by normal HTML tags,
                        // blank lines in a file, or from PHP.
                        header('Expires: -1');
                        header($cC);
                        header(
            "Content-Disposition: attachment; filename=\"$original_file_name\"");
                        header('Content-Type: ' . $ctype);
                        header('Content-Length: ' . (string) $file_size);
                        echo file_get_contents($target_path_with_filename, true);
                    } // file size <> false
                    exit;
                } //if file_exists
                exit;
            } //null!==upload
            exit;
        } //null!==$upload_id
        exit;
    }

    public function download(#[RouteArgument('invoice')] string $invoice): void
    {
        $aliases = $this->sR->getInvoiceArchivedFolderAliases();
        if ($invoice) {
            header('Content-type: application/pdf');
            header('Content-Disposition: attachment; filename="'
                    . urldecode($invoice) . '"');
            readfile($aliases->get('@archive_invoice')
                    . DIRECTORY_SEPARATOR . urldecode($invoice));
        }
    }
}
