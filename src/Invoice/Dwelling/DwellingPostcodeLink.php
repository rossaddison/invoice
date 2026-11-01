<?php

declare(strict_types=1);

namespace App\Invoice\Dwelling;

use Yiisoft\Html\Html;

/**
 * The HomeCare postcode cell shared by the inv/index and inv/guest grids: a
 * link opening a Google Maps search for the Dwelling's postcode in a new tab.
 * A Client with no Dwelling (or a Dwelling with no postcode) gets an hourglass
 * with a tooltip instead, so a missing link reads as "pending", not as a blank.
 */
final class DwellingPostcodeLink
{
    public const string MAPS_SEARCH_URL = 'https://www.google.com/maps/search/?api=1&query=';

    public const string PENDING_ICON = '⏳';

    public static function html(?int $dwellingId, DwellingRepository $dwR, string $pendingTitle = ''): string
    {
        $postcode = $dwellingId === null ? '' : ($dwR->repoDwellingQuery($dwellingId)?->getPostcode() ?? '');
        if ($postcode === '') {
            return Html::span(self::PENDING_ICON, ['title' => $pendingTitle, 'aria-label' => $pendingTitle])->render();
        }
        return Html::a(
            $postcode,
            self::MAPS_SEARCH_URL . rawurlencode($postcode),
            ['target' => '_blank', 'rel' => 'noopener noreferrer']
        )->render();
    }
}
