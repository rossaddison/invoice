<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use Yiisoft\Assets\AssetBundle;

final class StorageAccessApiAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@resources/asset';

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [
    ];

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        /*
         * Chrome is moving towards a new experience that allows users to
         * choose to browse without third-party non-partitioned cookies.
         *
         * @see https://developers.google.com/privacy-sandbox/cookies/storage-access-api
         */
        'storageAccessApiAsset/chrome/chrome.js',
    ];

    public array $depends = [
    ];
}
