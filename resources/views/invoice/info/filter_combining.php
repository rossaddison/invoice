<?php

declare(strict_types=1);

/**
 * @var int $fontSize
 */
?>

<div style="font-size: <?= $fontSize ?: 16; ?>px;">

<h6 id="what">How do two grid dropdown filters combine? (inv/index, quote/index, salesorder/index, product/index)</h6>
<p>
    Every filterable column on these four grids — e.g. <b>Client</b> and <b>Status</b> on
    <code>inv/index</code> — is expected to <i>AND</i> together: pick a client, then also
    pick a status, and the grid should show only that client's invoices in that status.
    Before the 2026-08-27 fix, picking a second dropdown silently dropped the first one's
    selection. This page walks through why, and how the fix wires two independent
    filters into a single combined result.
</p>

<h6 id="step-1">Step 1 — each filter is a DataColumn with a widget filter</h6>
<p>
    A filterable grid column is declared once, in a <code>*ColumnBuilder</code> class
    (e.g. <code>src/Invoice/Inv/Widget/InvsColumnBuilder.php</code>). The column's
    <code>property</code> is a <b>virtual name</b> — <code>filterClient</code>,
    <code>filterStatus</code> — not a real column on the <code>Inv</code> entity; the
    real filtering happens later, by hand, in the repository (Step 5).
</p>
<p><pre>
new DataColumn(
    property: 'filterClient',
    header: $t->translate('client'),
    content: static fn(Inv $model): string =>
        Html::encode($model->getClient()?->getClientFullName()),
    filter: DropdownFilter::widget()
        ->addAttributes(['id' => 'filter-client', 'name' => 'client_id'])
        ->optionsData($this->filterOptions->clients),
    filterFactory: new NoOpFilterFactory(),   // see Step 3
    withSorting: false,
),

new DataColumn(
    property: 'filterStatus',
    header: $t->translate('status'),
    content: static fn(Inv $model): string => /* status badge markup */ '',
    filter: DropdownFilter::widget()
        ->addAttributes(['id' => 'filter-status', 'name' => 'status'])
        ->optionsData($this->filterOptions->status),
    filterFactory: new NoOpFilterFactory(),
    withSorting: false,
),
</pre></p>
<p>
    <b>GridView renders exactly one shared, hidden <code>&lt;form&gt;</code> per grid</b> —
    both dropdowns are associated to it via HTML's <code>form="..."</code> attribute, and
    <code>DropdownFilter</code>'s <code>onChange="this.form.submit()"</code> already
    submits <i>every</i> dropdown's current value in a single GET request. The transport
    was never the bug — see Step 2.
</p>

<h6 id="step-2">Step 2 — urlParameterProvider makes each dropdown redisplay its own value</h6>
<p>
    Without <code>-&gt;urlParameterProvider(...)</code> on the GridView build, GridView's
    <code>FilterContext::value</code> is always <code>null</code>, so <b>every</b>
    <code>&lt;select&gt;</code> redisplays blank after the page reloads — regardless of
    what's in the URL. Combined with the shared form from Step 1: picking the Status
    dropdown submits whatever the DOM <i>currently shows</i>, and the Client
    <code>&lt;select&gt;</code> was sitting blank, so Client silently drops out of the
    request. This is the display/retention half of the fix, wired once per grid in the
    <code>*ListWidget</code> class:
</p>
<p><pre>
// src/Invoice/Inv/Widget/InvsListWidget.php
use Yiisoft\Yii\DataView\YiiRouter\UrlParameterProvider;

$gridView = GridView::widget()
    ->columns(...$columns)
    ->dataReader($gridDataReader)
    ->urlCreator($urlCreator)
    ->urlParameterProvider(new UrlParameterProvider($this->currentRoute))
    // ...
</pre></p>
<p>
    <code>CurrentRoute</code> is already an injected constructor dependency on every one
    of these widgets, so this is a one-line addition per grid.
</p>

<h6 id="step-3">Step 3 — NoOpFilterFactory stops GridView from filtering these columns itself</h6>
<p>
    Turning on <code>urlParameterProvider</code> alone is not enough: it also makes
    <code>DataColumnRenderer::makeFilter()</code> resolve a non-null query value for
    virtual properties like <code>filterClient</code>, which
    <code>BaseListView::render()</code> then applies via
    <code>$dataReader-&gt;withFilter(new AndX(...))</code> — a
    <code>PDOException: Unknown column 'inv.filterClient'</code>, because
    <code>filterClient</code> isn't a real column to build SQL from.
</p>
<p>
    <code>App\Widget\NoOpFilterFactory</code> (used on every filterable column above)
    neutralizes this:
</p>
<p><pre>
// src/Widget/NoOpFilterFactory.php
final class NoOpFilterFactory implements FilterFactoryInterface
{
    public function create(string $property, string $value): FilterInterface
    {
        return new All();   // yiisoft/data-cycle's AllHandler compiles this
    }                       // to a literal WHERE 1 = 1 — a complete no-op
}
</pre></p>
<p>
    This makes GridView's own auto-filtering inert for both <code>filterClient</code> and
    <code>filterStatus</code>, while leaving <code>renderFilter()</code> — the fixed
    redisplay from Step 2 — working normally, since <code>renderFilter()</code> never
    goes through this factory at all. Real filtering stays entirely on the repository
    (Step 5).
</p>

<h6 id="step-4">Step 4 — both dropdown values land on one filter object</h6>
<p>
    Submitting the shared form is a single GET request, so both query parameters arrive
    together and are auto-hydrated onto one <code>#[FromQuery]</code> input object —
    <code>client_id</code> and <code>status</code> map onto
    <code>InvIndexFilter::$filterClient</code> and
    <code>InvIndexFilter::$filterStatus</code> respectively:
</p>
<p><pre>
// src/Invoice/Inv/InvIndexFilter.php
#[FromQuery]
final class InvIndexFilter implements RequestInputInterface
{
    public ?string $filterClient = null;
    public ?string $filterStatus = null;
    // ...nine more filterXxx properties, one per filterable column
}
</pre></p>

<h6 id="step-5">Step 5 — filterCombined() ANDs both filters into one Cycle query</h6>
<p>
    <code>InvController::index()</code> passes the whole <code>InvIndexFilter</code> to
    <code>InvRepository::filterCombined()</code>
    (<code>src/Invoice/Inv/Trait/InvCombinedFilterTrait.php</code>), which builds
    <b>one</b> <code>Select</code> query and threads it through small per-concern
    helpers — each one only narrows the query further, never starting a fresh one:
</p>
<p><pre>
public function filterCombined(
    InvIndexFilter $filter,
    HomeCareRunContext $run,
    int $effectiveStatus,
): EntityReader {
    $query = $this->select()->load(['client', 'group', 'user']);

    if ($effectiveStatus > 0) {                       // ← Status dropdown
        $query = $query->andWhere(['status_id' => $effectiveStatus]);
    }
    $query = $this->applyIdentifierConditions($query, $filter);
    $query = $this->applyAmountConditions($query, $filter);
    $query = $this->applyClientConditions($query, $filter);   // ← Client dropdown
    $query = $this->applyFamilyAndRunConditions($query, $filter, $run);
    $query = $query->andWhere('deleted_at', null);
    return $this->prepareDataReader($query);
}

private function applyClientConditions(Select $query, InvIndexFilter $filter): Select
{
    if (isset($filter->filterClient) && !empty($filter->filterClient)) {
        $query = $query->andWhere(['client.id' => (int) $filter->filterClient]);
    }
    // ...filterClientGroup, filterClientAddress1, filterDateCreatedYearMonth
    return $query;
}
</pre></p>
<p>
    Because every helper receives and returns the <i>same</i> <code>$query</code>
    object — rather than each filter method calling <code>$this-&gt;select()</code> and
    starting over — a Client condition and a Status condition compound onto one
    <code>WHERE</code> clause instead of one replacing the other:
</p>
<p><pre>
WHERE status_id = :status AND client.id = :client AND deleted_at IS NULL
</pre></p>
<p>
    <code>InvController::index()</code> calls it once, up front, before either the
    HTMX partial-grid branch or the full-page branch renders:
</p>
<p><pre>
// src/Invoice/Inv/Trait/Index.php
$effectiveStatus = isset($filter->filterStatus) && !empty($filter->filterStatus)
    ? (int) $filter->filterStatus : (int) $status;
$run  = $this->indexHomeCareRunContext($request, $filter);
$invs = $list->invRepo->filterCombined($filter, $run, $effectiveStatus);
</pre></p>

<h6 id="round-trip">Putting it together — one round trip</h6>
<p>
<pre>
1. Page loads with ?status=2 (Sent) already in the URL.
2. urlParameterProvider (Step 2) tells GridView to redisplay
   the Status <select> at "Sent" — Client <select> stays at its own URL value, if any.
3. User picks a Client from its dropdown. onChange submits
   the ONE shared <form> → GET inv/index?client_id=7&status=2
4. NoOpFilterFactory (Step 3) stops GridView from trying — and
   crashing — to auto-filter on the virtual filterClient/filterStatus columns.
5. InvIndexFilter (Step 4) hydrates $filterClient = '7', $filterStatus = '2'.
6. filterCombined() (Step 5) ANDs client.id = 7 AND status_id = 2
   into one query → the grid shows only client 7's Sent invoices,
   and BOTH dropdowns redisplay their selected values.
</pre>
</p>

<h6 id="other-grids">Same two-part fix, applied per grid</h6>
<p>
    Each grid configures its own <code>GridView</code> independently, so the fix isn't
    automatic across grids — it was applied to each in turn:
</p>
<p><pre>
inv/index         → InvsListWidget + InvsColumnBuilder      (12 filterable columns)
                     combining engine: InvCombinedFilterTrait::filterCombined()
quote/index       → QuotesListWidget + QuotesColumnBuilder  (4 filterable columns)
                     combining engine: QuoteCombinedFilterTrait::filterCombined()
salesorder/index  → SalesOrdersListWidget + SalesOrdersColumnBuilder (1 column —
                     nothing to combine with, just the display-retention half)
product/index     → ProductsListWidget (inline filter: definitions, 3 columns)
                     combining engine: ProductRepository::filterCombined()
</pre></p>
<p>
    Product's filterable columns (<code>family_id</code>, <code>product_sku</code>,
    <code>product_price</code>) are real entity columns, not virtual <code>filterXxx</code>
    names — but <code>NoOpFilterFactory</code> is applied there too, for consistency:
    the default factory for widget-based filters is <code>LikeFilterFactory</code>, which
    would otherwise do a semantically wrong <code>LIKE</code> match on
    <code>family_id</code> if left to GridView's auto-filter.
</p>

</div>
