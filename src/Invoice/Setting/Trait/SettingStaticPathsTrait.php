<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Trait;

trait SettingStaticPathsTrait
{

    // Add to src/Invoice
    public static function getPlaceholderRelativeUrl(): string
    {
        return DIRECTORY_SEPARATOR . 'Uploads' . DIRECTORY_SEPARATOR;
    }

    public static function getAssetholderRelativeUrl(): string
    {
        return DIRECTORY_SEPARATOR . 'Asset' . DIRECTORY_SEPARATOR;
    }

    public static function getCustomerfolderRelativeUrl(): string
    {
        return DIRECTORY_SEPARATOR . 'Customer_files';
    }

    public static function getPemFileFolder(): string
    {
        return DIRECTORY_SEPARATOR . 'Pem_unique_folder';
    }

    public static function getGoogleTranslateJsonFileFolder(): string
    {
        return DIRECTORY_SEPARATOR . 'Google_translate_unique_folder';
    }

    public static function getCompanyPrivateLogosRelativefolderUrl(): string
    {
        return DIRECTORY_SEPARATOR . 'Company_private_logos';
    }

    public static function getTempPeppolfolderRelativeUrl(): string
    {
        return DIRECTORY_SEPARATOR . 'Temp' . DIRECTORY_SEPARATOR . 'Peppol'
                . DIRECTORY_SEPARATOR;
    }

    public static function getTempZugferdfolderRelativeUrl(): string
    {
        return DIRECTORY_SEPARATOR . 'Temp' . DIRECTORY_SEPARATOR . 'Zugferd'
                . DIRECTORY_SEPARATOR;
    }

    public static function getTemplateholderRelativeUrl(): string
    {
        return DIRECTORY_SEPARATOR . 'Invoice_templates' . DIRECTORY_SEPARATOR
                . 'Pdf' . DIRECTORY_SEPARATOR;
    }

    // Append to uploads folder
    public static function getUploadsArchiveholderRelativeUrl(): string
    {
        return DIRECTORY_SEPARATOR . 'Archive';
    }

    // Append to uploads folder
    public static function getUploadsCustomerFilesRelativeUrl(): string
    {
        return self::getCustomerfolderRelativeUrl();
    }

    // Append to uploads folder
    public static function getUploadsProductImagesRelativeUrl(): string
    {
        return DIRECTORY_SEPARATOR . 'ProductImages';
    }

    // Append to uploads folder
    public static function getAttachmentsCustomerFilesRelativeUrl(): string
    {
        return 'src' . DIRECTORY_SEPARATOR . 'Invoice' . DIRECTORY_SEPARATOR
                . 'Uploads' . DIRECTORY_SEPARATOR . 'Customer_files'
                . DIRECTORY_SEPARATOR;
    }
}
