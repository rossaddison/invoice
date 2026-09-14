<?php

declare(strict_types=1);

use Yiisoft\Aliases\Aliases;
use Yiisoft\ErrorHandler\Renderer\HtmlRenderer;

return [
    // Replaces the stock yiisoft/error-handler production template (plain
    // Verdana, red/maroon headings) with a branded, self-contained page
    // that renders even when the DB is unreachable -- see
    // resources/views/errors/production.php for why it can't go through
    // the app's normal layout. verboseTemplate (YII_DEBUG=true only) is
    // left untouched -- that one is already stock-detailed on purpose.
    HtmlRenderer::class => static fn (Aliases $aliases): HtmlRenderer => new HtmlRenderer(
        template: $aliases->get('@resources/errors/production.php'),
    ),
];
