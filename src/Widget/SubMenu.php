<?php

declare(strict_types=1);

namespace App\Widget;

use Yiisoft\Bootstrap5\Dropdown;
use Yiisoft\Bootstrap5\DropdownItem;
use Yiisoft\Router\UrlGeneratorInterface as UrlGenerator;

final class SubMenu
{
    /**
     * e.g. $items = [
     * ].
     */
    public static function generate(string $title, UrlGenerator $urlGenerator, array $items = []): string
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
             * @var array  $value
             */
            foreach ($levelItemsArray as $key => $value) {
                $actionName = (string) $value[0];
                /**
                 * @psalm-var array<string, \Stringable|null|scalar> $value[1]
                 */
                $actionArguments = $value[1];
                $builtItems[]    = DropdownItem::link(
                    $key,
                    $urlGenerator->generate(
                        $actionName,
                        $actionArguments,
                    ),
                );
            }
            $finalString = Dropdown::widget()
                ->togglerContent($title)->items(...$builtItems)->render();
        }

        return $finalString;
    }
}
