<?php

declare(strict_types=1);

/**
 * The default layout for the public pages (front page, login, signup) and for visitors without an
 * invoice role. It only chooses which template draws the page, from the business_type setting:
 * windowcleaning/main.php for a window-cleaning business, soletrader/main.php for everyone else.
 *
 * Both templates take the same layout parameters, which render() merges in from the layout
 * injections, so nothing is passed here except the page content.
 *
 * @var Yiisoft\View\WebView $this
 * @var string $content
 * @var bool $windowCleaning
 */

// '//' is relative to the views base path (@views); an '@views' alias is not resolved by render()
echo $this->render(
    $windowCleaning
        ? '//layout/templates/windowcleaning/main.php'
        : '//layout/templates/soletrader/main.php',
    ['content' => $content],
);
