<?php

declare(strict_types=1);

namespace App\Invoice\Enum;

// The kind of business the install is set up for, kept in the settings table under SETTING_KEY as a
// plain string (same convention as AllocationSide/HomeCareRunSheetStatus). A missing or any other
// value means the general setup. WindowCleaning shows the window-cleaning front page and a menu
// without Quote, SalesOrder, Tasks, Projects or Peppol; see WindowCleaningPreset.

enum BusinessType: string
{
    public const string SETTING_KEY = 'business_type';

    case WindowCleaning = 'window_cleaning';
}
