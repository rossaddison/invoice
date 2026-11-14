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
    public const string ROLE_WORKER      = 'worker';

    public const string LABEL_API_KEY    = 'Api Key';

    // Settings > Front Page: no_front_X_page = '1' hides X from the public front page.
    public const string SETTING_NO_FRONT_WEBSHOP_PAGE = 'no_front_webshop_page';
    public const string SETTING_NO_FRONT_GATEWAY_STATUS_PAGE = 'no_front_gateway_status_page';
    public const string SETTING_NO_FRONT_PEPPOL_STATUS_PAGE = 'no_front_peppol_status_page';
    public const string SETTING_NO_FRONT_HMRC_API_STATUS_PAGE = 'no_front_hmrc_api_status_page';
}
