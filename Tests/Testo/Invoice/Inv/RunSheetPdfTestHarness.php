<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Invoice\BaseController;
use App\Invoice\Inv\HomeCareRunContext;
use App\Invoice\Inv\InvIndexFilter;
use App\Invoice\Inv\Trait\RunSheetPdf;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Test-only harness mixing the real RunSheetPdf trait into a minimal BaseController subclass.
 * indexHomeCareRunContext() belongs to Trait\Index (a much larger dependency graph), so it is a
 * stub: the tests here stop before it is reached.
 */
final class RunSheetPdfTestHarness extends BaseController
{
    use RunSheetPdf;

    private function indexHomeCareRunContext(Request $_request, InvIndexFilter $_filter): HomeCareRunContext
    {
        throw new \LogicException('Not exercised by RunSheetPdfWorkerTest.');
    }
}
