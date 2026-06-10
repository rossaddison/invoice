<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Widget\SalesOrderToolbar;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;

final class SoControllerMiscDeps
{
    public function __construct(
        public readonly DataResponseFactoryInterface $factory,
        public readonly SalesOrderService $salesorderService,
        public readonly SalesOrderToolbar $salesOrderToolbar,
    ) {
    }
}
