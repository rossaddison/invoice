<?php

declare(strict_types=1);

namespace App\Invoice;

final class AppConstants
{
    public const string DATE_FORMAT      = 'Y-m-d';
    public const string DATETIME_FORMAT  = 'Y-m-d H:i:s';

    public const string ROLE_ADMIN       = 'admin';
    public const string ROLE_OBSERVER    = 'observer';
    public const string ROLE_ACCOUNTANT  = 'accountant';
}
