<?php

declare(strict_types=1);

namespace App\Widget;

use Yiisoft\Html\Tag\Img;
use chillerlan\QRCode\QRCode as ChillQrCode;

final class QrCode
{
    public static function absoluteUrl(?string $absoluteUrl = null, string $directions = '', int $pixels = 40): void
    {
        /**
         * @var float|int|string $qrCode
         */
        $qrCode = (new ChillQrCode())->render($absoluteUrl);
        printf(
            Img::tag()
            ->addAttributes(['data-bs-toggle' => 'tooltip', 'title' => $directions])
            ->width($pixels)
            ->height($pixels)
            ->src('%s')
            ->render(),
            $qrCode,
        );
    }
}
