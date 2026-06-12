<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use Yiisoft\Input\Http\Attribute\Data\FromQuery;
use Yiisoft\Input\Http\RequestInputInterface;

#[FromQuery]
final class InvIndexFilter implements RequestInputInterface
{
    public ?string $page = null;
    public ?string $sort = null;
    public ?string $filterInvNumber = null;
    public ?string $filterCreditInvNumber = null;
    public ?string $filterFamilyName = null;
    public ?string $filterClient = null;
    public ?string $filterInvAmountTotal = null;
    public ?string $filterInvAmountPaid = null;
    public ?string $filterInvAmountBalance = null;
    public ?string $filterClientGroup = null;
    public ?string $filterClientAddress1 = null;
    public ?string $filterDateCreatedYearMonth = null;
    public ?string $filterStatus = null;
    public ?string $groupBy = 'none';
}
