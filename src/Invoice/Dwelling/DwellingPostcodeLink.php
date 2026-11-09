<?php

declare(strict_types=1);

namespace App\Invoice\Dwelling;

use App\Invoice\Helpers\GoogleMapsLink;
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

    public const string PULL_POINT_ICON = '📍';

    public static function html(
        ?int $dwellingId,
        DwellingRepository $dwR,
        string $pendingTitle = '',
        string $pullPointTitle = '',
    ): string {
        $dwelling = $dwellingId === null ? null : $dwR->repoDwellingQuery($dwellingId);
        $postcode = $dwelling?->getPostcode() ?? '';
        if ($postcode === '') {
            return Html::span(self::PENDING_ICON, ['title' => $pendingTitle, 'aria-label' => $pendingTitle])->render();
        }
        $link = Html::a(
            $postcode,
            self::MAPS_SEARCH_URL . rawurlencode($postcode),
            ['target' => '_blank', 'rel' => 'noopener noreferrer']
        )->render();
        if ($dwelling?->isPullPoint() !== true) {
            return $link;
        }
        // the houses around this one are pulled from here: mark it, with a drive-there link beside it
        $latitude = $dwelling->getLatitude();
        $longitude = $dwelling->getLongitude();
        $destination = null !== $latitude && null !== $longitude ? $latitude . ',' . $longitude : $postcode;
        return $link . ' ' . Html::a(
            self::PULL_POINT_ICON . '🧭',
            GoogleMapsLink::directionsTo($destination),
            ['target' => '_blank', 'rel' => 'noopener noreferrer', 'title' => $pullPointTitle, 'aria-label' => $pullPointTitle]
        )->render();
    }
}
