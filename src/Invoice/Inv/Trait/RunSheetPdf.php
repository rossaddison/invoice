<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\Dwelling\DwellingRepository as DwR;
use App\Invoice\Family\FamilyRepository as FR;
use App\Invoice\Helpers\MpdfHelper;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Helpers\PdfCreateContext;
use App\Invoice\Inv\InvIndexFilter;
use App\Invoice\Inv\InvIndexListDeps;
use App\Invoice\Inv\InvIndexNavDeps;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\Inv\RunSheetPdfDeps;
use App\Invoice\Inv\RunSheetPdfRowBuilder;
use App\Invoice\InvWorkerAllocation\AllocationSidesSummary;
use App\Invoice\Worker\WorkerRepository as WR;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Derives a simple, printable "run sheet" PDF from whatever `inv/index`'s
 * own active filters currently match (same InvIndexFilter/HomeCareRunContext/
 * filterCombined() machinery Index::index() and MultipleCopy::copyAllToDate()
 * already use for exactly this "act on the grid's current filtered set, not
 * a checkbox selection" pattern) -- added per user request: a downloadable
 * fallback for a HomeCare cleaning run that works with zero connectivity
 * (paper, or a cached PDF), with each row's street address as a clickable
 * Google Maps single-destination navigation link when viewed digitally.
 *
 * Address source is Inv -> Client -> Dwelling (house number, postcode, and
 * optional precise lat/long) -> Dwelling -> Family (street name and
 * street_sort_order, the existing manually-curated cleaning-run visiting
 * order -- see docs/FAMILY_DRAG_DROP_STREET_ORDER.md) -- not the
 * InvItem -> Product -> Family chain Inv::getFirstItemFamilyName() walks,
 * which identifies which street a product/service is normally invoiced
 * under, not necessarily where this particular client actually lives.
 * Invoices whose client has no dwelling set (non-HomeCare clients) fall
 * back to Client::getClientAddress1()/getClientAddress2() and sort after
 * every dwelling-backed row, since they have no street_sort_order.
 *
 * Row-building/sorting itself lives in RunSheetPdfRowBuilder, a stateless
 * class with no HTTP/PDF dependencies -- this trait is just the thin
 * request-handling/PDF-rendering wiring around it.
 *
 * renderRunSheetPdf() below is also reused by
 * Trait\Guest::guestRunSheetPdf() -- the same PDF from whatever this
 * signed-in guest's own worker-/client-scoped inv/guest filters
 * currently match, using Trait\Guest's own access control and
 * applyGuestFilters(), not this staff-side filterCombined(). Only the
 * "which invoices" step differs between the two entry points; row-
 * building and PDF rendering are identical either way.
 */
trait RunSheetPdf
{
    /**
     * @return Response|\Mpdf\Mpdf|array<array-key, mixed>|string
     * @psalm-suppress MixedInferredReturnType
     */
    public function runSheetPdf(
        Request $request,
        InvIndexFilter $filter,
        InvIndexListDeps $list,
        InvIndexNavDeps $nav,
        RunSheetPdfDeps $deps,
        WR $wR,
    ): Response|\Mpdf\Mpdf|array|string {
        $user = $this->userService->getUser();
        if (!$this->userService->hasPermission(Permissions::EDIT_INV)
            && $user instanceof User
            && null !== $wR->findByUserId($user->reqId())
        ) {
            // this sheet carries client names, phones and balances for every invoice: a field worker
            // is sent to their own scoped sheet, which has none of them
            return $this->webService->getRedirectResponse('inv/guest/runsheetpdf');
        }
        $effectiveStatus = isset($filter->filterStatus)
            && !empty($filter->filterStatus)
            ? (int) $filter->filterStatus
            : 0;
        $run  = $this->indexHomeCareRunContext($request, $filter);
        $invs = $list->invRepo->filterCombined($filter, $run, $effectiveStatus);

        $startPostcode = $nav->csR->repoCategorySecondaryQuery($run->categorySecondaryId)?->getStartPostcode();

        return $this->renderRunSheetPdf($invs, $nav->dwR, $deps->fR, $deps->rowBuilder, $deps->sides, true, $startPostcode);
    }

    /**
     * @return Response|\Mpdf\Mpdf|array<array-key, mixed>|string
     * @psalm-suppress MixedInferredReturnType
     */
    private function renderRunSheetPdf(
        iterable $invs,
        DwR $dwR,
        FR $fR,
        RunSheetPdfRowBuilder $rowBuilder,
        AllocationSidesSummary $sides,
        bool $showPrivateDetails = true,
        ?string $startPostcode = null,
    ): Response|\Mpdf\Mpdf|array|string {
        $rows = $rowBuilder->build($invs, $dwR, $fR);
        // each row also says which side is whose ("Front: Dave; Back: Sam"), in the user's language
        $rows = $rowBuilder->withSides(
            $rows,
            $sides,
            fn (AllocationSide $side): string => $this->translator->translate('allocation.side.' . $side->value),
            fn (AllocationStatus $status): string => $this->translator->translate('allocation.status.' . $status->value),
        );
        // a field worker's or the lead's sheet names no client and shows no phone or amount (data protection)
        $rows = $showPrivateDetails ? $rows : $rowBuilder->withoutClientDetails($rows);
        $title = $this->translator->translate('run.sheet.pdf');
        $html = $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/run_sheet_pdf',
            [
                'rows'         => $rows,
                'showPrivateDetails' => $showPrivateDetails,
                'startPostcode' => $startPostcode,
                'startLabel'   => $this->translator->translate('run.start.postcode'),
                'title'        => $title,
                'numberHelper' => new NumberHelper($this->sR),
            ],
        );

        $mpdfHelper = new MpdfHelper($this->translator);
        // Landscape, not the MpdfHelper default portrait -- the address
        // column is deliberately wide (it's the row's foremost/prominent
        // content per the user's own request), and landscape gives it
        // more room before wrapping than a portrait A4 page would.
        $mpdfHelper->orientation = MpdfHelper::ORIENT_LANDSCAPE;
        /** @psalm-suppress MixedReturnStatement */
        return $mpdfHelper->pdfCreate(
            $html,
            $title,
            true,
            $this->sR,
            new PdfCreateContext('', null, null, false, false, [], null),
        );
    }
}
