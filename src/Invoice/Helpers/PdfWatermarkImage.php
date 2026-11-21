<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

/**
 * The image mPDF draws behind a paid or overdue invoice, as a data URI.
 *
 * The templates used to write <watermarkimage src="paid.png">, which mPDF resolves against the address the
 * request came in on (https://host/paid.png) and not against the file on disk (public/img/paid.png). With the
 * watermark switched on that is a Mpdf\MpdfImageException, and it breaks whenever the base URL or document root
 * differs from the layout the path was written for. A data URI carries the image itself, so it needs no URL, host
 * or filesystem path to resolve.
 */
final class PdfWatermarkImage
{
    /** @return string a data: URI, or '' when the name is not a plain image name or the file is missing */
    public static function dataUri(string $name, ?string $directory = null): string
    {
        if (preg_match('/^[A-Za-z0-9_-]+$/', $name) !== 1) {
            return '';
        }
        $file = ($directory ?? dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'img')
            . DIRECTORY_SEPARATOR . $name . '.png';
        if (!is_file($file)) {
            return '';
        }
        $bytes = file_get_contents($file);
        return $bytes === false ? '' : 'data:image/png;base64,' . base64_encode($bytes);
    }
}
