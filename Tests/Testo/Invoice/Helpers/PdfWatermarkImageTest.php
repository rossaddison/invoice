<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Invoice\Helpers\PdfWatermarkImage;
use App\Invoice\Setting\SettingRepository;
use Mpdf\Mpdf;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Html\Html;

/**
 * The paid and overdue PDF watermark. It used to be a relative 'paid.png', which mPDF resolved against the
 * request's base URL (https://host/paid.png) instead of public/img/paid.png, so a PDF for a paid invoice raised
 * Mpdf\MpdfImageException whenever the watermark was switched on.
 */
#[Test]
final class PdfWatermarkImageTest
{
    private const string PNG_SIGNATURE = "\x89PNG\r\n\x1a\n";

    private function temporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'watermark-test-' . bin2hex(random_bytes(6));
        mkdir($directory);
        return $directory;
    }

    private function removeDirectory(string $directory): void
    {
        $files = glob($directory . DIRECTORY_SEPARATOR . '*');
        foreach ($files === false ? [] : $files as $file) {
            unlink($file);
        }
        rmdir($directory);
    }

    /** The '@img' alias the application itself uses for public/img: SettingRepository::getImg(). */
    private function imgAliases(): Aliases
    {
        return (new ReflectionClass(SettingRepository::class))->newInstanceWithoutConstructor()->getImg();
    }

    private function decoded(string $dataUri): string
    {
        return (string) base64_decode(substr($dataUri, strlen('data:image/png;base64,')), true);
    }

    public function theDefaultDirectoryHoldsThePaidAndOverdueImagesAsRealPngs(): void
    {
        foreach (['paid', 'overdue'] as $name) {
            $uri = PdfWatermarkImage::dataUri($name, $this->imgAliases());

            Assert::true(str_starts_with($uri, 'data:image/png;base64,'));
            Assert::same(substr($this->decoded($uri), 0, 8), self::PNG_SIGNATURE);
        }
    }

    public function theDataUriCarriesExactlyTheBytesOfTheImageFile(): void
    {
        $directory = $this->temporaryDirectory();
        $bytes = self::PNG_SIGNATURE . random_bytes(64);
        file_put_contents($directory . DIRECTORY_SEPARATOR . 'stamp.png', $bytes);
        try {
            Assert::same($this->decoded(PdfWatermarkImage::dataUri('stamp', new Aliases(['@img' => $directory]))), $bytes);
        } finally {
            $this->removeDirectory($directory);
        }
    }

    public function aMissingImageGivesNoWatermarkInsteadOfAnError(): void
    {
        $directory = $this->temporaryDirectory();
        try {
            Assert::same(PdfWatermarkImage::dataUri('paid', new Aliases(['@img' => $directory])), '');
        } finally {
            $this->removeDirectory($directory);
        }
    }

    public function aNameThatIsNotAPlainImageNameIsRefused(): void
    {
        foreach (['../.env', '..\\.env', 'paid.png', 'a/b', '', 'paid%00', "paid\n"] as $name) {
            Assert::same(PdfWatermarkImage::dataUri($name, $this->imgAliases()), '');
        }
    }

    public function mpdfDrawsThePaidAndOverdueWatermarksWithImageErrorsOnAndNeedsNoBaseUrl(): void
    {
        foreach (['paid', 'overdue'] as $name) {
            $mpdf = new Mpdf(['tempDir' => dirname(__DIR__, 4) . '/src/Invoice/Tmp/']);
            $mpdf->showImageErrors = true;
            $mpdf->showWatermarkImage = true;
            // Built the way the paid.php and overdue.php templates build it.
            $mpdf->WriteHTML(
                '<p>Invoice</p>' . Html::tag('watermarkimage', '', ['src' => PdfWatermarkImage::dataUri($name, $this->imgAliases()), 'alpha' => '0.1']),
                2,
            );
            $pdf = (string) $mpdf->Output('', 'S');

            Assert::same(substr($pdf, 0, 5), '%PDF-');
            Assert::true(preg_match('~/Subtype\s*/Image~', $pdf) === 1);
        }
    }

    public function aRelativeImageNameIsTheOriginalFailure(): void
    {
        $mpdf = new Mpdf(['tempDir' => dirname(__DIR__, 4) . '/src/Invoice/Tmp/']);
        $mpdf->showImageErrors = true;
        $mpdf->showWatermarkImage = true;
        $error = null;
        try {
            $mpdf->WriteHTML('<p>Invoice</p><watermarkimage src="paid.png" alpha="0.1">', 2);
            $mpdf->Output('', 'S');
        } catch (\Mpdf\MpdfImageException $e) {
            $error = $e;
        }

        Assert::true($error instanceof \Mpdf\MpdfImageException);
        Assert::true(str_contains($error->getMessage(), 'Could not find image file (paid.png)'));
    }
}
