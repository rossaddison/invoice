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

    // Token type for the one-time login link OrderService issues after a
    // webshop checkout — see WebshopOrderLoginController. The stored Token
    // ->type value is this constant plus ':{inv_id}', not just this
    // constant alone, so the redemption controller can recover which
    // invoice to redirect to without trusting an unsigned request param.
    public const string TOKEN_TYPE_WEBSHOP_ORDER_LOGIN = 'webshop-order-login';
}
