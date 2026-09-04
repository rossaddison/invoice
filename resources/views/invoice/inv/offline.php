<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Html as TagHtml;
use Yiisoft\Html\Tag\Meta;
use Yiisoft\Html\Tag\Style;
use Yiisoft\Html\Tag\Title;

/**
 * The HomeCare offline-shell page (see docs/HOMECARE_OFFLINE_PWA_AUGUST_2026.md
 * and src/Invoice/Inv/Trait/Guest.php::guestOffline()). Deliberately a
 * standalone, self-contained document — NOT wrapped in the normal
 * layout/guest.php layout — since this is the page the service worker
 * (src/typescript/sw.ts) precaches so it still loads with zero
 * connectivity; the ordinary layout pulls in a live CSRF token and
 * session-dependent markup that would go stale if ever served from a
 * cache. All content is rendered client-side from IndexedDB by the
 * dedicated homecare-offline-shell.js bundle (its own small esbuild
 * entry, deliberately NOT the full invoice-typescript-iife.js bundle,
 * to keep what the service worker has to precache small) — nothing
 * server-rendered here beyond an empty container.
 *
 * Follows _html_php_conventions.php: no raw HTML, everything via
 * Yiisoft\Html\Html, one-space-per-level indentation, numbered
 * openTag/closeTag comments — same style layout/guest.php itself uses
 * for its own <html>/<head>/<body> scaffold.
 *
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */

$offlineStyle = <<<CSS
body { font-family: sans-serif; margin: 0; padding: 1rem; color: #222; background: #f7f7f7; }
h1 { font-size: 1.3rem; margin: 0 0 1rem; }
.offline-empty { color: #666; padding: 2rem 1rem; text-align: center; }
.offline-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; }
.offline-card h2 { font-size: 1.05rem; margin: 0 0 0.4rem; }
.offline-card .status { display: inline-block; font-size: 0.8rem; padding: 0.1rem 0.5rem; border-radius: 999px; background: #e9ecef; margin-bottom: 0.4rem; }
.offline-card .client { font-weight: 600; margin: 0.3rem 0; }
.offline-card .address, .offline-card .contact, .offline-card .note { font-size: 0.9rem; color: #444; margin: 0.15rem 0; }
.offline-card ul.items { margin: 0.5rem 0 0; padding-left: 1.1rem; font-size: 0.9rem; }
.offline-downloaded-at { font-size: 0.8rem; color: #777; margin-bottom: 1rem; }
CSS;

$homeCareOfflineShellLabels = json_encode([
    'empty' => $translator->translate('homecare.offline.empty'),
    'downloadedAt' => $translator->translate('homecare.offline.downloaded.at'),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
?>
<!DOCTYPE html>
<?php
echo new TagHtml()->lang('en'); //0
echo Html::openTag('head'); //1
echo Meta::documentEncoding('utf-8');
echo Meta::data('viewport', 'width=device-width, initial-scale=1');
echo new Title()->content($translator->translate('homecare.offline.title'));
echo new Style()->content($offlineStyle);
echo Html::closeTag('head'); //1
echo Html::openTag('body'); //1
echo Html::tag('h1', Html::encode($translator->translate('homecare.offline.title'))); //2
echo Html::tag('div', '', ['id' => 'homecare-offline-root']); //2
echo Html::tag(
    'script',
    $homeCareOfflineShellLabels,
    ['type' => 'application/json', 'id' => 'homecare-offline-shell-i18n']
); //2
// Plain root-relative path — @baseUrl resolves empty in this app's own
// config (config/common/params.php), and every other root-relative
// asset reference (e.g. @assetsUrl => '@baseUrl/assets') already works
// the same way in production, so this deliberately doesn't try to
// compute a deployment-path prefix that isn't actually configured
// anywhere.
// type="module" (not ->defer()) — this bundle uses top-level await
// (typescript:S7785), which requires ES module scope; module scripts
// are deferred by the HTML spec on their own, so no separate defer is
// needed.
echo Html::script()->url('/homecare-offline-shell.js')->type('module'); //2
echo Html::closeTag('body'); //1
echo Html::closeTag('html'); //0
