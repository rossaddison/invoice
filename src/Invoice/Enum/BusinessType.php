<?php

declare(strict_types=1);

namespace App\Invoice\Enum;

// The kind of business the install is set up for, kept in the settings table under SETTING_KEY as a
// plain string (same convention as AllocationSide/HomeCareRunSheetStatus). A missing or any other
// value means the general setup. Standard is that general setup, saved as the word 'standard' so a settings
// list reads clearly (an empty value is drawn as the grid's 'not set' hourglass). WindowCleaning shows the
// window-cleaning front page and a menu without Quote, SalesOrder, Tasks, Projects or Peppol; see
// WindowCleaningPreset.

enum BusinessType: string
{
    public const string SETTING_KEY = 'business_type';

    case Standard = 'standard';
    case WindowCleaning = 'window_cleaning';
}
