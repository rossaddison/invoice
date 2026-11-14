<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

/**
 * Google Maps links, in one place: turn-by-turn directions to a destination
 * (for the van, such as a run's starting postcode or a house) and a
 * plain search (for a postcode cell in a grid). Both open the Maps app on a phone.
 */
final class GoogleMapsLink
{
    public const string DIRECTIONS_URL = 'https://www.google.com/maps/dir/?api=1&destination=';

    public const string SEARCH_URL = 'https://www.google.com/maps/search/?api=1&query=';

    /**
     * Directions by car to the destination (a postcode, an address, or "lat,long").
     */
    public static function directionsTo(string $destination): string
    {
        return self::DIRECTIONS_URL . rawurlencode($destination) . '&travelmode=driving';
    }

    /**
     * A Maps search for the place (a postcode, an address).
     */
    public static function search(string $query): string
    {
        return self::SEARCH_URL . rawurlencode($query);
    }
}
