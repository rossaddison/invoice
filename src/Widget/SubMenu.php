<?php

declare(strict_types=1);

namespace App\Widget;

use Yiisoft\Router\UrlGeneratorInterface as UrlGenerator;
use Yiisoft\Bootstrap5\Dropdown;
use Yiisoft\Bootstrap5\DropdownItem;

final class SubMenu
{
    /**
     * e.g. $items = [
            ]
     * @param string $title
     * @param UrlGenerator $urlGenerator
     * @param array $items
     * @param string $navBarFont
     * @param string $navBarFontSize
     * @return string
     */
    public static function generate(
        string $title,
        UrlGenerator $urlGenerator,
        array $items = [],
        string $navBarFont,
        string $navBarFontSize,
        ): string
    {
        $finalString = '';
        /**
         * @var array $levelItem
         */
        foreach ($items as $levelItem) {
            $builtItems = [];
            /**
             * @var array $levelItem['items']
             */
            $levelItemsArray = $levelItem['items'];
            /**
             * @var string $key
             * @var array $value
             */
            foreach ($levelItemsArray as $key => $value) {
                $actionName = (string) $value[0];
                /**
                 * @psalm-var array<string, \Stringable|null|scalar> $value[1]
                 */
                $actionArguments = $value[1];
                $builtItems[] = DropdownItem::link(
                    $key,
                    $urlGenerator->generate(
                        $actionName,
                        $actionArguments,
                    ),
                    itemAttributes: ['style' => 'font-size: '
                        . $navBarFontSize . 'px;color: black;']
                );
            }
            $finalString = Dropdown::widget()
                           ->togglerContent($title)
                           ->addCssStyle([
                              'font-size' =>
                                $navBarFontSize . 'px',
                              'font-family' =>
                                $navBarFont])
                           ->items(...$builtItems)
                           ->render();
        }
        return $finalString;
    }
}
