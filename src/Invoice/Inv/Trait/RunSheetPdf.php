<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\Family\FamilyRepository as FR;
use App\Invoice\Helpers\MpdfHelper;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Helpers\PdfCreateContext;
use App\Invoice\Inv\InvIndexFilter;
use App\Invoice\Inv\InvIndexListDeps;
use App\Invoice\Inv\InvIndexNavDeps;
use App\Invoice\Inv\RunSheetPdfRowBuilder;
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
        FR $fR,
        RunSheetPdfRowBuilder $rowBuilder,
    ): Response|\Mpdf\Mpdf|array|string {
        $effectiveStatus = isset($filter->filterStatus)
            && !empty($filter->filterStatus)
            ? (int) $filter->filterStatus
            : 0;
        $run  = $this->indexHomeCareRunContext($request, $filter);
        $invs = $list->invRepo->filterCombined($filter, $run, $effectiveStatus);

        $rows = $rowBuilder->build($invs, $nav->dwR, $fR);
        $title = $this->translator->translate('run.sheet.pdf');
        $html = $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/run_sheet_pdf',
            [
                'rows'         => $rows,
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
