<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use Yiisoft\Assets\AssetBundle;

/**
 * yiisoft/yii-dataview's delegated `change` listener for the marker
 * attributes UseInlineJsInterface::useInlineJs(false) renders instead of an
 * inline `onChange`/`onchange` handler (data-yii-dataview-dropdown-filter-onchange,
 * data-yii-dataview-page-size-onchange) — CSP script-src 'self' blocks the
 * inline handler the widget renders by default; this external file is the
 * vendor's own opt-out mechanism (yiisoft/yii-dataview#355), added here since
 * the package itself ships assets/no-inline-js.js but no AssetBundle to load
 * it (reported upstream:
 * https://github.com/yiisoft/yii-dataview/pull/355#issuecomment-5493774415).
 *
 * Register only on pages that actually call useInlineJs(false) on a
 * DropdownFilter/SelectPageSize/InputPageSize -- see
 * project_yii_dataview_csp_upstream_resolution memory for which filters
 * have switched from this app's own equivalent (native-reset class +
 * data-actions.ts's delegated listener) to this vendor mechanism.
 */
final class YiiDataViewNoInlineJsAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@vendor/yiisoft/yii-dataview/assets';

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        'no-inline-js.js',
    ];
}
