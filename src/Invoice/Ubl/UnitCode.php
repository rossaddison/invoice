<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

/**
 * All possible Unit Codes that can be used
 * To extend, see also: http://tfig.unece.org/contents/recommendation-20.htm
 */
class UnitCode
{
    const string UNIT = 'C62';
    const string PIECE = 'H87';

    const string ARE = 'ARE';
    const string HECTARE = 'HAR';

    const string SQUARE_METRE = 'MTK';
    const string SQUARE_KILOMETRE = 'KMK';
    const string SQUARE_FOOT = 'FTK';
    const string SQUARE_YARD = 'YDK';
    const string SQUARE_MILE = 'MIK';

    const string LITRE = 'LTR';

    const string SECOND = 'SEC';
    const string MINUTE = 'MIN';
    const string HOUR = 'HUR';
    const string DAY = 'DAY';
    const string MONTH = 'MON';
    const string YEAR = 'ANN';
}
