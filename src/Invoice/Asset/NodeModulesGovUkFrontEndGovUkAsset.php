<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use Yiisoft\Assets\AssetBundle;

class NodeModulesGovUkFrontEndGovUkAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    /**
     * Related logic: see https://frontend.design-system.service.gov.uk/installing-with-npm/#get-the-css-assets-and-javascript-working
     * Related logic: see config/common/params 'yiisoft/aliases @npm'.
     */
    public ?string $sourcePath = '@npm/govuk-frontend/dist/govuk';

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [
        'govuk-frontend.min.css',
    ];

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        'govuk-frontend.min.js',
    ];
}
