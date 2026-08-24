<?php

declare(strict_types=1);

use App\Invoice\Asset\BootstrapCssOnlyAsset;
use App\Invoice\Asset\BootstrapJsOnlyAsset;
use App\Webshop\Currency\CurrencyContext;
use App\Webshop\Currency\CurrencyPreferenceService;
use App\Webshop\Currency\CurrencySymbols;
use App\Webshop\Delivery\DeliveryAddress;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Bootstrap5\Modal;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\Html as TagHtml;
use Yiisoft\Html\Tag\Meta;
use Yiisoft\Html\Tag\Style;
use Yiisoft\Html\Tag\Title;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Yii\View\Renderer\Csrf;

/**
 * Storefront chrome for the public, unauthenticated `/shop` routes —
 * ported from the standalone webshop app's own `layout/main.php` (see
 * docs/WEBSHOP_INPROCESS_MERGE_AUGUST_2026.md), not a fork of
 * `@views/layout/templates/soletrader/main.php`: that layout is wired to
 * ~30 `ViewInjection`-supplied marketing/company/locale parameters this
 * storefront has no use for, where this one already did everything a
 * storefront chrome needs (Deliver-to widget, search, currency dropdown,
 * cart badge) in far fewer, purpose-built parameters.
 *
 * @var WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 * @var CurrentRoute $currentRoute
 * @var TranslatorInterface $translator
 * @var string $content
 * @var int $cartCount
 * @var ?DeliveryAddress $deliveryAddress
 * @var CurrencyContext $currency
 * @var bool $isGuest
 * @var Flash $flash
 * @var AssetManager $assetManager
 * @var Csrf $csrf
 */

// $this->beginPage() must be the first statement after the docblock —
// matches the ddd-template layout's own note on this Psalm-parser quirk.
$this->beginPage();
$siteName = 'Webshop';

// A root-relative path, not (string) $currentRoute->getUri() — confirmed
// live that the latter is a *full* absolute URI (scheme+host+path) in
// this app's own request/URI-factory setup, unlike the standalone
// webshop app this layout was ported from, and
// WebControllerService::getRedirectToSameOriginPathResponse()'s own
// same-origin guard (deliberately) treats anything containing '://' as
// a potential open redirect and falls back to '/' — silently bouncing
// every delivery-address/currency save back to the homepage instead of
// back to the current page. A bare path sidesteps the ambiguity
// entirely rather than trying to validate an absolute URL's origin here.
$currentPath = $currentRoute->getUri()?->getPath() ?? '/';

// A small inline shopping-bag glyph rather than an image file — no asset
// pipeline needed for a single static icon. stroke="currentColor" so it
// follows the navbar-brand link's own text color automatically.
$logoSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"'
    . ' fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"'
    . ' stroke-linejoin="round" class="me-2" aria-hidden="true">'
    . '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/>'
    . '<path d="M3 6h18"/>'
    . '<path d="M16 10a4 4 0 0 1-8 0"/>'
    . '</svg>';

// Same stroke="currentColor" convention as $logoSvg above.
$pinSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"'
    . ' fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"'
    . ' stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>'
    . '<circle cx="12" cy="10" r="3"/>'
    . '</svg>';

// Session-only display preference — App\Webshop\Delivery\
// DeliveryAddressService's own docblock has the full picture. Amazon-style
// navbar widget: pin icon + "Deliver to {first name}" over the city/
// postcode, opening a small Bootstrap modal to set/change it.
$delivery = $deliveryAddress;
$deliveryModalId = 'delivery-address-modal';

$deliveryTrigger = new Button()
    ->addAttributes(['type' => 'button', 'data-bs-toggle' => 'modal', 'data-bs-target' => '#' . $deliveryModalId])
    ->addClass('btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 mx-2')
    ->content(
        $pinSvg
        . '<span class="d-flex flex-column lh-sm text-start">'
        . '<small class="text-muted" style="font-size: .7rem;">'
        . Html::encode($delivery !== null && $delivery->name !== '' ? 'Deliver to ' . $delivery->name : 'Deliver to')
        . '</small>'
        . '<strong style="font-size: .85rem;">'
        . Html::encode($delivery !== null && $delivery->locationLine() !== '' ? $delivery->locationLine() : 'Add address')
        . '</strong>'
        . '</span>',
    )
    ->encode(false);

$deliveryModal = Modal::widget()
    ->id($deliveryModalId)
    ->title('Delivery address')
    ->body(
        new Form()
            ->post($urlGenerator->generate('shop/delivery-address'))
            ->csrf($csrf)
            ->open()
        . Html::hiddenInput('redirect', $currentPath)
        . Html::div(
            Html::label('First name', 'delivery-name')->render()
            . Html::input('text', 'name', $delivery?->name ?? '', [
                'id' => 'delivery-name',
                'class' => 'form-control',
                'maxlength' => '100',
                'placeholder' => 'e.g. John',
            ])->render(),
            ['class' => 'mb-3'],
        )->encode(false)->render()
        . Html::div(
            Html::label('Last name', 'delivery-surname')->render()
            . Html::input('text', 'surname', $delivery?->surname ?? '', [
                'id' => 'delivery-surname',
                'class' => 'form-control',
                'maxlength' => '100',
                'placeholder' => 'e.g. Smith',
            ])->render(),
            ['class' => 'mb-3'],
        )->encode(false)->render()
        . Html::div(
            Html::label('City', 'delivery-city')->render()
            . Html::input('text', 'city', $delivery?->city ?? '', [
                'id' => 'delivery-city',
                'class' => 'form-control',
                'maxlength' => '100',
                'placeholder' => 'e.g. Glasgow',
            ])->render(),
            ['class' => 'mb-3'],
        )->encode(false)->render()
        . Html::div(
            Html::label('Postcode', 'delivery-postcode')->render()
            . Html::input('text', 'postcode', $delivery?->postcode ?? '', [
                'id' => 'delivery-postcode',
                'class' => 'form-control',
                'maxlength' => '20',
                'placeholder' => 'e.g. G32 6AB',
            ])->render(),
            ['class' => 'mb-3'],
        )->encode(false)->render()
        . Html::submitButton('Save delivery address', ['class' => 'btn btn-primary w-100'])->render()
        . new Form()->close(),
    )
    ->triggerButton($deliveryTrigger)
    ->verticalCentered()
    ->render();

// Amazon-style navbar search — GET straight to shop/catalog/index, same
// route/query params (`q`, `category[]`) the sidebar's own filter form
// (resources/views/shop/catalog/index.php) reads via ProductFilter.
$searchIconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"'
    . ' fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"'
    . ' stroke-linejoin="round" aria-hidden="true">'
    . '<circle cx="11" cy="11" r="8"/>'
    . '<path d="m21 21-4.35-4.35"/>'
    . '</svg>';

$searchForm = Html::openTag('form', [
    'method' => 'get',
    'action' => $urlGenerator->generate('shop/catalog/index'),
    'class' => 'd-flex flex-grow-1 mx-2',
    'role' => 'search',
])
    . '<div class="input-group">'
    . Html::select('category[]')
        ->optionsData(['' => 'All'])
        ->addAttributes(['class' => 'form-select flex-grow-0 search-department', 'aria-label' => 'Search category'])
        ->render()
    . Html::input('search', 'q', null, [
        'class' => 'form-control',
        'placeholder' => 'Search ' . $siteName,
        'aria-label' => 'Search ' . $siteName,
    ])->render()
    . Html::button($searchIconSvg, ['type' => 'submit', 'class' => 'btn search-submit', 'aria-label' => 'Search'])
        ->encode(false)
        ->render()
    . '</div>'
    . Html::closeTag('form');

// The currency toggle — this shop's own Peppol dual-currency pair (see
// App\Webshop\Currency\CurrencyContext's own docblock), not an arbitrary
// multi-currency picker: exactly the two currencies that setup actually
// has, so there's nothing to render when there's only one.
//
// Styled after Amazon's own currency-picker dropdown rather than a
// centered modal: an anchored panel under a plain "{CODE} ▾" trigger
// (Bootstrap's native .dropdown-toggle, no JS beyond what
// bootstrap.bundle.js already provides), "€ - EUR - Euro"-style rows
// with no per-row flag.
$currencyWidget = '';
$info = $currency->info();
if ($info !== null && $info->hasDualCurrency()) {
    $currencyOption = static function (string $value, string $code, bool $checked): string {
        $id = 'currency-option-' . $value;
        return '<div class="form-check mb-1">'
            . Html::radio('preference', $value, ['id' => $id, 'class' => 'form-check-input'])
                ->checked($checked)
                ->render()
            . '<label class="form-check-label" for="' . Html::encode($id) . '">'
            . Html::encode(CurrencySymbols::pickerLabel($code))
            . '</label>'
            . '</div>';
    };

    $flagImg = static function (string $currencyCode): string {
        $countryCode = CurrencySymbols::flagCountryCode($currencyCode);
        return $countryCode !== null
            ? '<img src="https://flagcdn.com/16x12/' . $countryCode . '.png" width="16" height="12"'
                . ' alt="" class="me-1">'
            : '';
    };
    $nativeFlagImg = $flagImg($info->native);

    $currencyTrigger = new Button()
        ->addAttributes(['type' => 'button', 'data-bs-toggle' => 'dropdown', 'aria-expanded' => 'false'])
        ->addClass('btn btn-outline-secondary btn-sm d-flex align-items-center dropdown-toggle mx-2')
        ->content($flagImg((string) $currency->activeCode()) . Html::encode((string) $currency->activeCode()))
        ->encode(false);

    $currencyWidget = '<div class="dropdown">'
        . $currencyTrigger->render()
        . '<div class="dropdown-menu currency-dropdown-menu p-3">'
        . '<h6 class="dropdown-header px-0 pt-0">Change currency</h6>'
        . '<hr class="dropdown-divider mx-0">'
        . new Form()
            ->post($urlGenerator->generate('shop/currency'))
            ->csrf($csrf)
            ->open()
        . Html::hiddenInput('redirect', $currentPath)
        . $currencyOption(CurrencyPreferenceService::NATIVE, $info->native, !$currency->isShowingDocumentCurrency())
        . $currencyOption(CurrencyPreferenceService::DOCUMENT, $info->document, $currency->isShowingDocumentCurrency())
        . '<hr class="dropdown-divider mx-0">'
        . '<p class="text-muted small mb-2 d-flex align-items-center">'
        . $nativeFlagImg
        . 'You are always billed in ' . Html::encode($info->native) . ' at checkout'
        . ' (1 ' . Html::encode($info->native) . ' = ' . number_format($info->nativeToDocumentRate, 4)
        . ' ' . Html::encode($info->document) . ').'
        . '</p>'
        . Html::submitButton('Save changes', ['class' => 'btn btn-primary btn-sm w-100'])->render()
        . new Form()->close()
        . '</div>'
        . '</div>';
}

// "Returns & Orders" — unlike the standalone webshop app this was merged
// from (which had no account/session of its own and had to link out to
// invoice's /forgotpassword page), this now runs in the exact same
// session as the rest of the app: a logged-in customer goes straight to
// their own order history (`inv/guest`), a guest goes to the normal
// login page. Both routes live under the `/{_language}` group `/shop`
// itself deliberately sits outside — `_language` has to be passed
// explicitly here since this request has no locale segment of its own
// for Locale middleware to have set as the current default (confirmed
// live during the merge: omitting it throws the same "expects at least
// argument values for [_language,...]" error `App\Api\OrderService`'s
// own docblock once documented for the pre-merge login-link handoff).
$returnsOrdersUrl = $isGuest
    ? $urlGenerator->generate('auth/login', ['_language' => 'en'])
    : $urlGenerator->generate('inv/guest', ['_language' => 'en']);
$returnsOrdersWidget = Html::a(
    '<span class="d-flex flex-column lh-sm text-start">'
    . '<small class="text-muted" style="font-size: .7rem;">Returns</small>'
    . '<strong style="font-size: .85rem;">&amp; Orders</strong>'
    . '</span>',
    $returnsOrdersUrl,
    ['class' => 'btn btn-outline-secondary btn-sm d-flex align-items-center mx-2'],
)
    ->encode(false)
    ->render();

// Published locally (App\Invoice\Asset\{BootstrapCssOnlyAsset,
// BootstrapJsOnlyAsset} — this app's own equivalent of the standalone
// webshop app's Yiisoft\Bootstrap5\Assets\BootstrapAsset) rather than
// loaded from a CDN.
$assetManager->register(BootstrapCssOnlyAsset::class);
$assetManager->register(BootstrapJsOnlyAsset::class);
$this->addCssFiles($assetManager->getCssFiles());
$this->addJsFiles($assetManager->getJsFiles());
?>
<!DOCTYPE html>
<?php
echo new TagHtml()->lang($currentRoute->getArgument('_language') ?? 'en');
echo Html::openTag('head');
echo Meta::documentEncoding('utf-8');
echo Meta::data('viewport', 'width=device-width, initial-scale=1');
echo new Title()->content($siteName);
// Bootstrap sizes almost everything (buttons, form-control padding,
// labels, headings) in rem, so scaling the root down from the browser
// default (16px) scales the whole page proportionally rather than
// needing per-component overrides.
// The search button's own colour is Amazon's own orange (#febd69) —
// intentionally not one of Bootstrap's own `btn-*` variants (none of
// them land on that hue), so it's a couple of plain rules here rather
// than a five-line custom Sass variant Bootstrap doesn't build.
echo new Style()->content(
    'html { font-size: 14px; }'
    . '.search-department { max-width: 5rem; }'
    . '.search-submit { background-color: #febd69; border-color: #febd69; color: #111; }'
    . '.search-submit:hover, .search-submit:focus { background-color: #f3a847; border-color: #f3a847; color: #111; }'
    // Amazon's own currency-picker panel is a compact anchored dropdown,
    // not a centered modal — see the currency-widget comment above.
    // The extra spacer (Bootstrap's own --bs-dropdown-spacer, normally
    // just 0.125rem) makes room for the pointer triangle below without
    // it overlapping the trigger button.
    . '.currency-dropdown-menu { min-width: 17rem; --bs-dropdown-spacer: 0.75rem; position: relative; }'
    . '.currency-dropdown-menu .form-check-label { font-size: .85rem; }'
    // A two-layer CSS triangle (a slightly larger border-colour one
    // behind a smaller background-colour one) so the panel reads as
    // pointing at the button that opened it, the same way a tooltip or
    // popover would — plain Bootstrap dropdowns don't have one built in.
    . '.currency-dropdown-menu::before, .currency-dropdown-menu::after {'
    . ' content: ""; position: absolute; left: 1.25rem; width: 0; height: 0;'
    . ' border-left: 7px solid transparent; border-right: 7px solid transparent; }'
    . '.currency-dropdown-menu::before {'
    . ' top: -8px; border-bottom: 8px solid var(--bs-dropdown-border-color, rgba(0, 0, 0, .15)); }'
    . '.currency-dropdown-menu::after {'
    . ' top: -7px; border-bottom: 7px solid var(--bs-dropdown-bg, #fff); }',
);
$this->head();
echo Html::closeTag('head');
echo Html::openTag('body');
$this->beginBody();

echo Html::openTag('header');
echo Html::openTag('nav', ['class' => 'navbar navbar-expand-lg bg-body-tertiary border-bottom']);
echo Html::openTag('div', ['class' => 'container-fluid']);
echo new A()
    ->href($urlGenerator->generate('shop/catalog/index'))
    ->addClass('navbar-brand d-flex align-items-center fs-4')
    ->content($logoSvg . Html::encode($siteName))
    ->encode(false)
    ->render();
echo $deliveryModal;
echo $searchForm;
echo $currencyWidget;
echo $returnsOrdersWidget;
echo Html::a(
    'Cart (' . Html::span((string) $cartCount, ['id' => 'cart-count-badge'])->render() . ')',
    $urlGenerator->generate('shop/cart/index'),
    ['class' => 'btn btn-outline-primary btn-sm ms-auto'],
)
    ->encode(false)
    ->render();
echo Html::closeTag('div');
echo Html::closeTag('nav');
echo Html::closeTag('header');

echo Html::openTag('main', ['class' => 'container py-4']);
/** @var array<string, list<string>> $flashes */
$flashes = $flash->getAll();
foreach ($flashes as $level => $messages) {
    foreach ($messages as $message) {
        $alertClass = match ($level) {
            'danger', 'warning', 'info', 'success' => $level,
            default => 'info',
        };
        echo Html::div(
            Html::encode($message),
            ['class' => 'alert alert-' . $alertClass],
        )->render();
    }
}
echo $content;
echo Html::closeTag('main');

echo Html::openTag('footer', ['class' => 'border-top py-3 text-center text-muted small']);
echo Html::encode('Webshop — a storefront built into rossaddison/invoice');
echo Html::closeTag('footer');

$this->endBody();
echo Html::closeTag('body');
echo Html::closeTag('html');
$this->endPage(true);
