<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Trait;

use App\Invoice\Setting\DatabaseBackupException;
use App\Invoice\Setting\DatabaseBackupService;

/**
 * Separates the Settings > Backup tab's file-download action from the main
 * SettingController — kept its own trait purely to stay under SonarQube's
 * S1448 method-count ceiling (php:S1448), not because the responsibility
 * itself needs isolating.
 */
trait SettingBackupTrait
{
    /**
     * Streams a gzip-compressed SQL dump of the whole database as a file
     * download. Generated via DatabaseBackupService (pure Cycle DBAL, no
     * mysqldump/exec()) so it works the same on shared hosting as on a VPS.
     */
    public function downloadBackup(DatabaseBackupService $backupService): mixed
    {
        $fileName = 'invoice_backup_' . date('Ymd_His') . '.sql.gz';
        $filePath = sys_get_temp_dir() . '/' . $fileName;
        try {
            $backupService->writeGzippedDump($filePath);
            $fileSize = filesize($filePath);
            if ($fileSize === false) {
                throw new DatabaseBackupException('backup file was not created');
            }
            header('Expires: -1');
            header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
            header("Content-Disposition: attachment; filename=\"{$fileName}\"");
            header('Content-Type: application/gzip');
            header('Content-Length: ' . (string) $fileSize);
            readfile($filePath);
        } catch (\Throwable $e) {
            $this->flashMessage('danger',
                $this->translator->translate('backup.download.failed') . ': ' . $e->getMessage());
            return $this->webService->getRedirectResponse('setting/tabIndex', [], ['active' => 'backup']);
        } finally {
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
        exit;
    }
}
