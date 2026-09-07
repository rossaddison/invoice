<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use Yiisoft\Input\Http\Attribute\Data\FromQuery;
use Yiisoft\Input\Http\RequestInputInterface;

#[FromQuery]
final class InvGuestFilter implements RequestInputInterface
{
    public ?string $page = null;
    public ?string $sort = null;
    public ?string $filterClient = null;
    public ?string $filterInvNumber = null;
    public ?string $filterCreditInvNumber = null;
    public ?string $filterInvAmountTotal = null;
    public ?string $filterInvAmountPaid = null;
    public ?string $filterInvAmountBalance = null;
    public ?string $filterStatus = null;
    // 'Y-m-d' exact match -- set by inv/guest/calendar's day-block badges
    // (Trait\GuestCalendar), narrowed to this signed-in guest's own
    // worker-/client-scoped invoices via Trait\Guest::applyGuestFilters().
    public ?string $filterDateCreatedExact = null;
}
