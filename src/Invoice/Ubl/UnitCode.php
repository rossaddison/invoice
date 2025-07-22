<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

/**
 * All possible Unit Codes that can be used
 * To extend, see also: http://tfig.unece.org/contents/recommendation-20.htm.
 */
class UnitCode
{
    public const string UNIT  = 'C62';
    public const string PIECE = 'H87';

    public const string ARE     = 'ARE';
    public const string HECTARE = 'HAR';

    public const string SQUARE_METRE     = 'MTK';
    public const string SQUARE_KILOMETRE = 'KMK';
    public const string SQUARE_FOOT      = 'FTK';
    public const string SQUARE_YARD      = 'YDK';
    public const string SQUARE_MILE      = 'MIK';

    public const string LITRE = 'LTR';

    public const string SECOND = 'SEC';
    public const string MINUTE = 'MIN';
    public const string HOUR   = 'HUR';
    public const string DAY    = 'DAY';
    public const string MONTH  = 'MON';
    public const string YEAR   = 'ANN';
}
