<?php

declare(strict_types=1);

use App\Invoice\Asset\InvoiceCdnAsset as InvCdn;
use App\Invoice\Asset\InvoiceNodeModulesAsset as InvNm;
use App\Invoice\Asset\MonospaceAsset;
// PCI Compliant Payment Gateway Assets
// Stripe's own asset (StripeVersionTenAsset) is registered from within
// payment_information_stripe_pci.php instead of here, so it only loads on
// the Stripe payment page rather than on every page in the app.
use App\Invoice\Asset\pciAsset\BraintreeDropInOneThirtyThreeSevenAsset;
use App\Asset\AppCdnAsset as AppCdn;
use App\Asset\AppNodeModulesAsset as AppNm;
use App\Widget\PerformanceMetrics;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\Html as TagHtml;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Link;
use Yiisoft\Html\Tag\Meta;
use Yiisoft\Html\Tag\Style;
use Yiisoft\Html\Tag\Title;
use App\Invoice\Asset\BootstrapCdnJsOnlyAsset as BsCdn;
use App\Invoice\Asset\BootstrapJsOnlyAsset as BsNm;
use Yiisoft\Bootstrap5\ButtonSize;
use Yiisoft\Bootstrap5\Dropdown;
use Yiisoft\Bootstrap5\DropdownItem;
use Yiisoft\Bootstrap5\ButtonVariant;
use Yiisoft\Bootstrap5\Nav;
use Yiisoft\Bootstrap5\NavBar;
use Yiisoft\Bootstrap5\NavBarExpand;
use Yiisoft\Bootstrap5\NavLink;
use Yiisoft\Bootstrap5\NavStyle;

/**
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Infrastructure\Persistence\User\User|null $user
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 * @var bool $bootstrap5CdnNotNodeModule
 * @var bool $appCdnNotNodeModule
 * @var bool $invCdnNotNodeModule
 * @var bool $guestStickyNavbar Per-observer preference (not the
 *     admin-controlled `bootstrap5_layout_invoice_navbar_sticky` setting
 *     `resources/views/layout/invoice.php`'s own gear-dropdown toggle
 *     controls for staff) — see App\ViewInjection\LayoutViewInjection::
 *     resolveUserState() and docs/STICKY_NAVBAR_AND_GRID_HEADER_SEPTEMBER_2026.md's
 *     "per-observer" section. Always `false` for an anonymous visitor
 *     (`$isGuest`), since there's no `UserInv` row to read a preference
 *     from.
 * @var string $guestStickyNavbarOnUrl Empty for an anonymous visitor —
 *     see `$guestStickyNavbar` above. Two distinct URLs, not one shared
 *     toggle URL: both buttons linking to the same "flip whatever it
 *     currently is" endpoint was a real bug (clicking "off" while
 *     already off turned it back on) found from a live "not working"
 *     report.
 * @var string $guestStickyNavbarOffUrl
 * @var bool $guestStickyGridHeader Same per-observer reasoning as
 *     `$guestStickyNavbar` above, for `inv/guest.php`'s own grid header
 *     row rather than this layout's navbar.
 * @var string $guestStickyGridHeaderOnUrl
 * @var string $guestStickyGridHeaderOffUrl
 * @var string $bootstrap5LayoutGuestNavbarFont
 * @var string $bootstrap5LayoutGuestNavbarFontSize
 * @var int $bootstrap5FormInputHeight
 * @var int $bootstrap5FormFontSize
 * @var bool $hasQuotesOrSalesOrders Whether to show the Quote/SalesOrder
 *     nav dropdowns at all — see App\ViewInjection\
 *     GuestLayoutViewParameters's own docblock for why this isn't gated
 *     on "webshop customer vs. traditional client" (no such distinction
 *     exists in this app) but on whether the observer's assigned
 *     client(s) actually have any.
 * @var string $csrf
 * @var string $content
 * @var string $brandLabel
 * @var string $companyLogoHeight
 * @var string $companyLogoMargin
 * @var string $companyLogoWidth
 * @var string $logoPath
 * @var DropdownItem $afZA
 * @var DropdownItem $arBH
 * @var DropdownItem $az
 * @var DropdownItem $beBY
 * @var DropdownItem $bs
 * @var DropdownItem $zhCN
 * @var DropdownItem $zhTW
 * @var DropdownItem $en
 * @var DropdownItem $fil
 * @var DropdownItem $fr
 * @var DropdownItem $gdGB
 * @var DropdownItem $haNG
 * @var DropdownItem $heIL
 * @var DropdownItem $igNG
 * @var DropdownItem $nl
 * @var DropdownItem $de
 * @var DropdownItem $id
 * @var DropdownItem $it
 * @var DropdownItem $ja
 * @var DropdownItem $pl
 * @var DropdownItem $ptBR
 * @var DropdownItem $ru
 * @var DropdownItem $sk
 * @var DropdownItem $sl
 * @var DropdownItem $es
 * @var DropdownItem $uk
 * @var DropdownItem $uz
 * @var DropdownItem $vi
 * @var DropdownItem $yoNG
 * @var DropdownItem $zuZA
 * @var string $currentLocaleFlag
 * @var array<string,string> $localeFlags
 * @var string $guestPageSizeUrlTemplate
 * @var int $guestCurrentPageSize
 */
// Settings ... View ... General
$assetManager->register($appCdnNotNodeModule ? AppCdn::class : AppNm::class);
$assetManager->register($invCdnNotNodeModule ? InvCdn::class : InvNm::class);
$assetManager->register($bootstrap5CdnNotNodeModule ? BsCdn::class : BsNm::class);
$s->getSetting('monospace_amounts') == 1 ?
        $assetManager->register(MonospaceAsset::class) : '';
$assetManager->register(BraintreeDropInOneThirtyThreeSevenAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());

$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());
$t = $translator;
$isGuest = $user === null;
$itemFontArray = [
    'style' => 'font-size: ' . $bootstrap5LayoutGuestNavbarFontSize . 'px;'
    . ' color: black;'];
$this->beginPage();
?>
<!DOCTYPE html>
<?php
echo new TagHtml()->lang($currentRoute->getArgument('_language') ?? 'en');
echo Html::openTag('head');
echo Meta::documentEncoding('utf-8');
echo Meta::data('viewport', 'width=device-width, initial-scale=1');
// HomeCare offline PWA (see docs/HOMECARE_OFFLINE_PWA_AUGUST_2026.md) —
// harmless/generic for both plain client guests and worker guests; only
// the actual "Download for Offline" UI on guest.php itself is
// worker-only. CSP already permits this: manifest-src 'self' /
// worker-src 'self' (config/web/params.php).
echo new Link()->rel('manifest')->href('/manifest.json');
echo Meta::data('theme-color', '#1e73b8');
 echo new Style()->content(
    ':root {'
    . ' --guest-nav-fs: ' . $bootstrap5LayoutGuestNavbarFontSize . 'px;'
    . ' --guest-nav-ff: ' . $bootstrap5LayoutGuestNavbarFont . ';'
    . ' --guest-input-height: ' . $bootstrap5FormInputHeight . 'px;'
    . ' --guest-form-fs: ' . $bootstrap5FormFontSize . 'px;'
    // Read by any sticky-positioned content below the navbar (e.g.
    // inv/guest's sticky grid header, overrides.css) so it sticks just
    // under a sticky navbar instead of being covered by it — same
    // reasoning as invoice.php's own identical declaration. 0 when the
    // navbar itself isn't sticky, since nothing needs to clear it then;
    // initStickyNavbarOffset() (src/typescript/sticky-navbar-offset.ts,
    // already active here — guest.php loads the same invoice-typescript-
    // iife.js bundle invoice.php does) overwrites this fallback with a
    // live measurement once JS runs.
    . ' --sticky-content-top: '
    . ($guestStickyNavbar ? 'var(--navbar-height)' : '0px') . ';'
    . ' }'
  )->render();
echo Meta::data('robots', 'NOINDEX,NOFOLLOW');
echo new Title()->content($s->getSetting('custom_title') ?: 'Yii-Invoice');
$this->head();
echo Html::closeTag('head');
echo Html::openTag('body');
echo Html::tag('Noscript', Html::tag('div',
    $t->translate('please.enable.js'),
    ['class' => 'alert alert-danger no-margin']));
// Deliberately no <header> wrapper around the NavBar -- position: sticky
// is bounded by its element's own containing block (the nearest
// ancestor box), and a <header> containing nothing but the navbar is
// exactly the navbar's own height, giving position: sticky zero room to
// actually stay pinned: it unsticks and scrolls away the instant that
// tiny <header> box itself scrolls out of view, which is immediately.
// Confirmed live and empirically (a real headless-browser scroll test
// against the real published CSS: getComputedStyle reported
// position: sticky throughout, but the element's bounding rect still
// moved 1:1 with scroll) as the actual root cause of a "sticky navbar
// not working" report -- the on/off-button value bug fixed alongside
// this was real too, but insufficient on its own. invoice.php's own
// navbar (confirmed working) has never had a <header> wrapper -- it's a
// direct child of <body>, which spans the full page height and gives
// position: sticky somewhere to actually stick. Matched here instead of
// giving <header> a stretched height, since that's what's already
// proven to work.
$this->beginBody();
$navBar = NavBar::widget()
    ->addAttributes([])
    //->addClass('navbar navbar-light bg-light navbar-expand-sm text-white')
    ->addClass('navbar bg-body-tertiary')
    ->brandImage($logoPath)
    ->brandImageAttributes(['margin' => $companyLogoMargin,
        'width' => $companyLogoWidth,
        'height' => $companyLogoHeight])
    ->brandUrl($urlGenerator->generate('site/index'))
    ->container(false)
    ->containerAttributes([])
    ->addCssStyle([
      'font-size' => $bootstrap5LayoutGuestNavbarFontSize,
      'font-family' => $bootstrap5LayoutGuestNavbarFont,
    ])
    ->expand(NavBarExpand::LG)
    ->id('navbar')
    ->innerContainerAttributes(['class' => 'container-md']);

// Bootstrap's own .sticky-top utility class -- see this file's own
// @var docblock for $guestStickyNavbar. Unlike invoice.php's own
// identical conditional, bg-body-tertiary doesn't need to move inside
// this "if" here -- it's already applied unconditionally above, so a
// non-sticky guest navbar was never at risk of the transparent-bar
// issue invoice.php's own docblock describes.
if ($guestStickyNavbar) {
    $navBar = $navBar->addClass('sticky-top');
}

echo $navBar->begin();

$currentPath = $currentRoute->getUri()?->getPath();
if ((null !== $currentPath) && !$isGuest) {
    // Client
    echo Dropdown::widget()
    ->addTogglerCssStyle([
        'font-size' => $bootstrap5LayoutGuestNavbarFontSize . 'px',
        'font-family' => $bootstrap5LayoutGuestNavbarFont,
    ])
    ->togglerVariant(ButtonVariant::INFO)
    ->togglerContent($t->translate('client'))
    ->togglerSize(ButtonSize::LARGE)
    ->items(
        DropdownItem::link($t->translate('view'),
            $urlGenerator->generate('client/guest'),
            itemAttributes: $itemFontArray),
    )
    ->render();

    // Quote / SalesOrder — a webshop-checkout customer never has either
    // (see App\ViewInjection\GuestLayoutViewParameters's own docblock),
    // so showing these nav buttons to them is empty, confusing clutter,
    // not a real destination. $hasQuotesOrSalesOrders covers both in one
    // flag since neither makes sense to show without the other missing.
    if ($hasQuotesOrSalesOrders) {
    // Quote
    echo Dropdown::widget()
    ->addTogglerCssStyle([
        'font-size' => $bootstrap5LayoutGuestNavbarFontSize . 'px',
        'font-family' => $bootstrap5LayoutGuestNavbarFont,
    ])
    ->togglerVariant(ButtonVariant::INFO)
    ->togglerContent($t->translate('quote'))
    ->togglerSize(ButtonSize::LARGE)
    ->items(
        DropdownItem::link($t->translate('view'),
            $urlGenerator->generate('quote/guest'),
            itemAttributes: $itemFontArray),
    )
    ->render();

    // SalesOrder
    echo Dropdown::widget()
    ->addTogglerCssStyle([
        'font-size' => $bootstrap5LayoutGuestNavbarFontSize . 'px',
        'font-family' => $bootstrap5LayoutGuestNavbarFont,
    ])
    ->togglerVariant(ButtonVariant::INFO)
    ->togglerContent($t->translate('salesorder'))
    ->togglerSize(ButtonSize::LARGE)
    ->items(
        DropdownItem::link($t->translate('view'),
            $urlGenerator->generate('salesorder/guest'),
            itemAttributes: $itemFontArray)
    )
    ->render();
    }

    // Invoice
    echo Dropdown::widget()
    ->addTogglerCssStyle([
        'font-size' => $bootstrap5LayoutGuestNavbarFontSize . 'px',
        'font-family' => $bootstrap5LayoutGuestNavbarFont,
    ])
    ->togglerVariant(ButtonVariant::INFO)
    ->togglerContent($t->translate('invoice'))
    ->togglerSize(ButtonSize::LARGE)
    ->items(
        DropdownItem::link($t->translate('view'),
            $urlGenerator->generate('inv/guest'),
            itemAttributes: $itemFontArray),
    )
    ->render();

    // Payment
    echo Dropdown::widget()
    ->addTogglerCssStyle([
        'font-size' => $bootstrap5LayoutGuestNavbarFontSize . 'px',
        'font-family' => $bootstrap5LayoutGuestNavbarFont,
    ])
    ->togglerVariant(ButtonVariant::INFO)
    ->togglerContent((string)  new I()->addClass('bi bi-coin')
            . ' ' . $t->translate('payment'))
    ->togglerSize(ButtonSize::LARGE)
    ->items(
        DropdownItem::link($t->translate('view'),
            $urlGenerator->generate('payment/guest'),
            itemAttributes: $itemFontArray),
        DropdownItem::link($t->translate('online.log'),
            $urlGenerator->generate('payment/guestOnlineLog'),
            itemAttributes: $itemFontArray),
    )
    ->render();

    // Settings
    echo Dropdown::widget()
    ->addTogglerCssStyle([
        'font-size' => $bootstrap5LayoutGuestNavbarFontSize . 'px',
        'font-family' => $bootstrap5LayoutGuestNavbarFont,
    ])
    ->togglerVariant(ButtonVariant::INFO)
    ->togglerContent((string)  new I()->addClass('bi bi-gears')
            . ' ' . $t->translate('settings'))
    ->togglerSize(ButtonSize::LARGE)
    ->items(
        DropdownItem::link($t->translate('view'),
            $urlGenerator->generate('userinv/guest'),
            itemAttributes: $itemFontArray),
        DropdownItem::link($t->translate('password.change'),
            $urlGenerator->generate('auth/change'),
            itemAttributes: $itemFontArray),
        DropdownItem::link($t->translate('email.log'),
            $urlGenerator->generate('invsentlog/guest'),
            itemAttributes: $itemFontArray),
        DropdownItem::divider(),
        DropdownItem::listContent(
            '<h6 class="dropdown-header"'
            . ' style="font-size:' . $bootstrap5LayoutGuestNavbarFontSize . 'px;"'
            . ' data-bs-toggle="tooltip" data-bs-placement="right"'
            . ' title="' . Html::encode($t->translate('default.list.limit.hint')) . '">'
            . (new I())->addClass('bi bi-list-ol')->render()
            . ' ' . Html::encode($t->translate('default.list.limit'))
            . '</h6>'
        ),
        DropdownItem::listContent(
            '<div class="px-3 py-1">'
            . '<div id="page-size-btn-group" class="btn-group btn-group-sm" role="group" aria-label="'
            . Html::encode($t->translate('default.list.limit')) . '">'
            . implode('', array_map(
                static fn(int $size): string =>
                    '<a hx-get="' . Html::encode(str_replace('__SIZE__', (string) $size, $guestPageSizeUrlTemplate)) . '"'
                    . ' hx-swap="none"'
                    . ' href="' . Html::encode(str_replace('__SIZE__', (string) $size, $guestPageSizeUrlTemplate)) . '"'
                    . ' class="btn btn-outline-secondary' . ($size === $guestCurrentPageSize ? ' active' : '') . '">'
                    . $size . '</a>',
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 25, 50, 100, 200]
            ))
            . '</div></div>'
        ),
        DropdownItem::divider(),
        // Per-observer sticky-navbar/sticky-grid-header preferences --
        // see this file's own @var docblocks and
        // docs/STICKY_NAVBAR_AND_GRID_HEADER_SEPTEMBER_2026.md's
        // "per-observer" section. A plain link (not htmx, unlike the
        // page-size buttons above): both preferences are read while
        // building this layout's own <head>/navbar markup server-side,
        // so a full page reload is needed for the new state to actually
        // show, not just persisted in the background.
        DropdownItem::listContent(
            '<h6 class="dropdown-header"'
            . ' style="font-size:' . $bootstrap5LayoutGuestNavbarFontSize . 'px;"'
            . ' data-bs-toggle="tooltip" data-bs-placement="right"'
            . ' title="' . Html::encode($t->translate('sticky.navbar.hint')) . '">'
            . (new I())->addClass('bi bi-pin-angle')->render()
            . ' ' . Html::encode($t->translate('sticky.navbar'))
            . '</h6>'
        ),
        DropdownItem::listContent(
            '<div class="px-3 py-1">'
            . '<div class="btn-group btn-group-sm" role="group" aria-label="'
            . Html::encode($t->translate('sticky.navbar')) . '">'
            . '<a href="' . Html::encode($guestStickyNavbarOnUrl) . '"'
            . ' class="btn ' . ($guestStickyNavbar ? 'btn-success' : 'btn-outline-secondary') . '">'
            . Html::encode($t->translate('on')) . '</a>'
            . '<a href="' . Html::encode($guestStickyNavbarOffUrl) . '"'
            . ' class="btn ' . (!$guestStickyNavbar ? 'btn-success' : 'btn-outline-secondary') . '">'
            . Html::encode($t->translate('off')) . '</a>'
            . '</div></div>'
        ),
        DropdownItem::listContent(
            '<h6 class="dropdown-header"'
            . ' style="font-size:' . $bootstrap5LayoutGuestNavbarFontSize . 'px;"'
            . ' data-bs-toggle="tooltip" data-bs-placement="right"'
            . ' title="' . Html::encode($t->translate('sticky.grid.header.hint')) . '">'
            . (new I())->addClass('bi bi-table')->render()
            . ' ' . Html::encode($t->translate('sticky.grid.header'))
            . '</h6>'
        ),
        DropdownItem::listContent(
            '<div class="px-3 py-1">'
            . '<div class="btn-group btn-group-sm" role="group" aria-label="'
            . Html::encode($t->translate('sticky.grid.header')) . '">'
            . '<a href="' . Html::encode($guestStickyGridHeaderOnUrl) . '"'
            . ' class="btn ' . ($guestStickyGridHeader ? 'btn-success' : 'btn-outline-secondary') . '">'
            . Html::encode($t->translate('on')) . '</a>'
            . '<a href="' . Html::encode($guestStickyGridHeaderOffUrl) . '"'
            . ' class="btn ' . (!$guestStickyGridHeader ? 'btn-success' : 'btn-outline-secondary') . '">'
            . Html::encode($t->translate('off')) . '</a>'
            . '</div></div>'
        ),
    )
    ->render();
    // Translate
    echo Dropdown::widget()
    ->addAttributes([
        'data-bs-toggle' => 'tooltip',
        'title' => $t->translate('language'),
        'url' => '#',
    ])
    ->addTogglerCssStyle([
        'font-size' => $bootstrap5LayoutGuestNavbarFontSize . 'px',
        'font-family' => $bootstrap5LayoutGuestNavbarFont,
    ])
    ->togglerVariant(ButtonVariant::INFO)
    ->togglerContent($currentLocaleFlag
            . ' '
            . new I()->addClass('bi bi-translate'))
    ->togglerSize(ButtonSize::LARGE)
    ->items(
        // Related logic: config/web/params, src/ViewInjection/LayoutViewInjection
        $afZA, $arBH, $az, $beBY, $bs, $zhCN, $zhTW, $en,
        $fil, $fr, $gdGB, $haNG, $heIL, $igNG, $nl, $de,
        $id, $it, $ja, $pl, $ptBR, $ru, $sk, $sl, $es,
        $uk, $uz, $vi, $yoNG, $zuZA
    )->render();
}

if (null !== $currentPath && $isGuest) {
    echo Nav::widget()
    ->items(
        NavLink::to(
             new Label()
            ->attributes([
                'class' => 'bi bi-door-open-fill text-success',
            ])
            ->content(),
            $urlGenerator->generate('auth/login'),
            true,
            false,
            false,
        ),
        NavLink::to(
             new Label()
            ->attributes(
                [
                    'class' => 'bi bi-person-plus-fill',
                    'data-bs-toggle' => 'tooltip',
                    'title' => str_repeat(' ', 1)
                    . $t->translate('setup.create.user'),
                ],
            ),
            $urlGenerator->generate('auth/signup'),
            true,
            false,
            false,
        ),
    )
    ->styles(NavStyle::NAVBAR);
}

if (!$isGuest) {
    echo  new Form()
    ->post($urlGenerator->generate('auth/logout'))
    ->csrf($csrf)
    ->open()
    . Html::openTag('div')
    . Button::submit(null !== $user ?
        (new I())->addClass('bi bi-box-arrow-right me-1')->render()
        . Html::encode((preg_replace('/\d+/', '', $user->getLogin()) ?? '')
        . ' ' . $t->translate('logout')) :
        (new I())->addClass('bi bi-box-arrow-right me-1')->render()
        . ' ' . Html::encode($t->translate('logout')))
        ->encode(false)
        ->addClass('btn btn-outline-danger')
        ->addStyle('font-size: '
                . $bootstrap5LayoutGuestNavbarFontSize
                . 'px; padding: '
                . ((int) $bootstrap5LayoutGuestNavbarFontSize * 0.15)
                . 'px '
                . ((int) $bootstrap5LayoutGuestNavbarFontSize * 0.4) . 'px;')
    . Html::closeTag('div')
    . new Form()->close();
}
echo NavBar::end();
echo Html::openTag('div', ['id' => 'main-area']);
  echo Html::openTag('main', ['class' => 'container-fluid py-4']);
  echo $content;
  echo Html::openTag('div', [
    'id' => 'fullpage-loader',
    'style' => 'display: none'
   ]); //2
   echo Html::openTag('div', ['class' => 'loader-content']); //3
    echo new I()
          ->addAttributes(['id' => 'loader-icon'])
          ->addClass('bi bi-gear-fill')
          ->render(); //4
   echo Html::CloseTag('div'); //3
  echo Html::closeTag('div'); //2
 echo Html::closeTag('main');
echo Html::closeTag('div');
  echo Html::openTag('footer', ['class' => 'container-fluid py-4']); //2
   echo PerformanceMetrics::widget(); //3
  echo Html::closeTag('footer'); //2
 $this->endBody();
 echo Html::closeTag('body'); //1
echo Html::closeTag('html');
$this->endPage(true);
