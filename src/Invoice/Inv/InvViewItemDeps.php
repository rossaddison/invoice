<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\ProductImage\ProductImageRepository as PIR;
use App\Invoice\Project\ProjectRepository as PRJCTR;
use App\Invoice\Task\TaskRepository as TASKR;

final class InvViewItemDeps
{
    public function __construct(
        public readonly IIR $iiR,
        public readonly IIAR $iiaR,
        public readonly PIR $piR,
        public readonly PR $pR,
        public readonly TASKR $taskR,
        public readonly PRJCTR $prjctR,
    ) {
    }
}
