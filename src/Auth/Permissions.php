<?php

declare(strict_types=1);

namespace App\Auth;

final class Permissions
{
    public const string ENTRY_TO_BASE_CONTROLLER = 'entry.to.base.controller';

    public const string NO_ENTRY_TO_BASE_CONTROLLER = 'no.entry.to.base.controller';

    public const string VIEW_INV = 'view.inv';

    public const string EDIT_INV = 'edit.inv';

    public const string EDIT_USER_INV = 'edit.user.inv';

    public const string VIEW_PAYMENT = 'view.payment';

    public const string EDIT_PAYMENT = 'edit.payment';

    public const string EDIT_CLIENT_PEPPOL = 'edit.client.peppol';

    public function __construct() {}
}
