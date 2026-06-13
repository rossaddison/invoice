<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use Yiisoft\Input\Http\Attribute\Data\FromQuery;
use Yiisoft\Input\Http\RequestInputInterface;

#[FromQuery]
final class QuoteIndexFilter implements RequestInputInterface
{
    public ?string $groupBy = 'none';
    public ?string $filterClient = null;
    public ?string $filterStatus = null;
}
