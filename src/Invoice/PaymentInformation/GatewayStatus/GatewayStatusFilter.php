<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\GatewayStatus;

use Yiisoft\Input\Http\Attribute\Data\FromQuery;
use Yiisoft\Input\Http\RequestInputInterface;

/**
 * Query-bound filter values for /gateway-status -- mirrors
 * App\Invoice\Inv\InvIndexFilter's shape/convention exactly. Applied by
 * GatewayStatusRows::filter(); the native DropdownFilter widgets rendered
 * in GatewayStatusListWidget's own filter row submit into these same
 * property names but leave the actual filtering to app code
 * (Filter\Factory\NoOpFilterFactory on each DataColumn) -- see
 * InvsColumnBuilder::buildColumns()'s filterClient column for the
 * precedent this reuses.
 */
#[FromQuery]
final class GatewayStatusFilter implements RequestInputInterface
{
    public ?string $filterRegion = null;
    public ?string $filterSandboxStatus = null;
    /** 'yes' | 'no' */
    public ?string $filterNeedsRetest = null;
}
