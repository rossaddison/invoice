<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Trait;

use Yiisoft\Aliases\Aliases;
use Yiisoft\Files\FileHelper;
use Yiisoft\Files\PathMatcher\PathMatcher;

trait SettingFileFolderTrait
{

    /**
     * @param string $base_dir
     * @param int $level
     * @return iterable
     * @psalm-return iterable<mixed, array<array-key, mixed>|object>
     */
    public function expandDirectoriesMatrix(string $base_dir, int $level): iterable
    {
        $directories = [];
        $scanDir = scandir($base_dir);
        if ($scanDir != false) {
            foreach ($scanDir as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $dir = $base_dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($dir)) {
                    $directories[] = [
                        'level' => $level,
                        'name' => $file,
                        'path' => $dir,
                    ];
                }
            }
        }
        return $directories;
    }

    /**
     * @return string
     */
    public function specificCommonConfigAliase(string $key): string
    {
        $config = $this->getConfigParams();
        $params = $config->get('params');
        /**
         * @var array $params['yiisoft/aliases']
         */
        $yiisoftAliases = $params['yiisoft/aliases'];
        /**
         * @var array $yiisoftAliases['aliases']
         */
        $aliases = $yiisoftAliases['aliases'];
        /** @var array<string, string> $aliases */
        $allAliases = new Aliases($aliases);
        return $allAliases->get($key);
    }

    /**
      * @return (mixed|string)[]
      *
      * @psalm-return array{esmtp_enabled: bool, esmtp_scheme: mixed,
         esmtp_host: mixed, esmtp_port: mixed, use_send_mail: string}
      */
    public function configParams(): array
    {
        $config = $this->getConfigParams();
        $params = $config->get('params');
    /**
     * @var array $params['yiisoft/mailer-symfony']
     * @var string $params['yiisoft/mailer-symfony']['useSendmail']
     * @var bool $params['yiisoft/mailer-symfony']['esmtpTransport']['enabled']
     * @var string $params['yiisoft/mailer-symfony']['esmtpTransport']['scheme']
     * @var string $params['yiisoft/mailer-symfony']['esmtpTransport']['host']
     * @var string $params['yiisoft/mailer-symfony']['esmtpTransport']['port']
     */
        return [
            'esmtp_enabled' =>
                $params['yiisoft/mailer-symfony']['esmtpTransport']['enabled'],
            'esmtp_scheme' =>
                $params['yiisoft/mailer-symfony']['esmtpTransport']['scheme'],
            'esmtp_host' =>
                $params['yiisoft/mailer-symfony']['esmtpTransport']['host'],
            'esmtp_port' =>
                $params['yiisoft/mailer-symfony']['esmtpTransport']['port'],
            'use_send_mail' =>
                $params['yiisoft/mailer-symfony']['useSendmail'] == 1 ?
                    $this->translator->translate('true') :
                    $this->translator->translate('false'),
        ];
    }

    /**
     * @return Aliases
     */
    public function getImg(): Aliases
    {
        return new Aliases(['@base' => dirname(__DIR__, 3),
            '@img' => '@base/public/img',
        ]);
    }

    /**
     * @param string $type
     * @return array
     */
    public function getInvoiceTemplates(string $type = 'pdf'): array
    {
        $aliases = new Aliases(['@base' => dirname(__DIR__, 3),
            '@pdf' => '@base/resources/views/invoice/template/invoice/pdf',
            '@public' => '@base/resources/views/invoice/template/invoice/public',
        ]);
        $templates = [];
        $pdf = scandir($aliases->get('@pdf'), SCANDIR_SORT_ASCENDING);
        $public = scandir($aliases->get('@public'), SCANDIR_SORT_ASCENDING);
        if (($type == 'pdf') && ($pdf != false)) {
            $templates = array_diff($pdf, ['..', '.']);
        } elseif (($type == 'public') && ($public != false)) {
            $templates = array_diff($public, ['..', '.']);
        }
        return $this->removeExtension($templates);
    }

    /**
     * @param string $type
     * @return array
     */
    public function getQuoteTemplates(string $type = 'pdf'): array
    {
        $aliases = new Aliases(['@base' => dirname(__DIR__, 3),
            '@pdf' => '@base/resources/views/invoice/template/quote/pdf',
            '@public' => '@base/resources/views/invoice/template/quote/public',
        ]);
        $templates = [];
        $scanPdf = scandir($aliases->get('@pdf'), SCANDIR_SORT_ASCENDING);
        $scanPublic = scandir($aliases->get('@public'), SCANDIR_SORT_ASCENDING);
        if (($type == 'pdf') && ($scanPdf != false)) {
            $templates = array_diff($scanPdf, ['..', '.']);
        } elseif (($type == 'public') && ($scanPublic != false)) {
            $templates = array_diff($scanPublic, ['..', '.']);
        }
        return $this->removeExtension($templates);
    }

    /**
     * @return Aliases
     */
    public function getInvoiceArchivedFolderAliases(): Aliases
    {
        return new Aliases(['@base' => dirname(__DIR__, 3),
            '@archive_invoice' => '@base/src/Invoice/Uploads'
            . self::getUploadsArchiveholderRelativeUrl() . '',
        ]);
    }

    /**
     * @return Aliases
     */
    public function getCustomerFilesFolderAliases(): Aliases
    {
        return new Aliases(['@base' => dirname(__DIR__, 3),
            '@customer_files' => '@base/src/Invoice/Uploads'
            . self::getUploadsCustomerFilesRelativeUrl(),
            '@public' => '@base/public',
        ]);
    }

    /**
     * @return Aliases
     */
    public function getCompanyPrivateLogosFolderAliases(): Aliases
    {
        return new Aliases(['@base' => dirname(__DIR__, 3),
            '@company_private_logos' => '@base/src/Invoice/Uploads'
            . self::getCompanyPrivateLogosRelativefolderUrl(),
            '@public' => '@base/public',

            // Web accessible external folder normally used
            '@public_logo' => '@public/logo',
        ]);
    }

    /**
     * @return Aliases
     */
    public function getGoogleTranslateJsonFileAliases(): Aliases
    {
        return new Aliases(['@base' => dirname(__DIR__, 3),
            '@google_translate_json_file_folder' => '@base/src/Invoice'
            . self::getGoogleTranslateJsonFileFolder(),
        ]);
    }

    /**
     * @return Aliases
     */
    public function getProductimagesFilesFolderAliases(): Aliases
    {
        return new Aliases(['@base' => dirname(__DIR__, 3),
            // Internal folder not normally used for storage
            '@productimages_files' => '@base/src/Invoice/Uploads'
            . self::getUploadsProductImagesRelativeUrl(),
            '@public' => '@base/public',

            // Web accessible external folder normally used
            '@public_product_images' => '@public/products',
        ]);
    }

    /**
     * @param string $invoice_number
     * @return array
     */
    public function getInvoiceArchivedFilesWithFilter(string $invoice_number): array
    {
        $aliases = $this->getInvoiceArchivedFolderAliases();
        $filehelper = new FileHelper();
        $filter =  new PathMatcher()
                   ->doNotCheckFilesystem()
                   ->only($invoice_number . '.pdf');
        return $filehelper::findFiles(
                $aliases->get('@archive_invoice'),
                ['recursive' => false,
                    'filter' => $filter]);
    }

    /**
     * @return Aliases
     */
    public function getAmazonPemFileFolderAliases(): Aliases
    {
        return new Aliases(['@base' => dirname(__DIR__, 3),
            '@pem_file_unique_folder' => '@base/src/Invoice'
            . self::getPemFileFolder(),
        ]);
    }

    /**
     * @param array $files
     * @return array
     */
    private function removeExtension(array $files): array
    {
        /**
         * @var string $file
         */
        foreach ($files as $key => $file) {
            $files[$key] = str_replace('.php', '', $file);
        }

        return $files;
    }
}
