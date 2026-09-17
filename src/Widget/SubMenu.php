<?php

declare(strict_types=1);

namespace App\Widget;

use Yiisoft\Router\UrlGeneratorInterface as UrlGenerator;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\I;

final class SubMenu
{
    /**
     * Renders a flyout submenu: a toggle row plus a nested link list that
     * opens to the right of its parent dropdown on hover/keyboard-focus (see
     * .dropdown-submenu in components.css -- no JS needed, since Bootstrap5's
     * own Dropdown only auto-closes on clicks *outside* its .dropdown-menu,
     * so this nested <ul> never fights the parent dropdown's open/close
     * state).
     *
     * The caller wraps the returned string in
     * DropdownItem::listContent($html, ['class' => 'dropdown-submenu']) so
     * the outer <li> gets position:relative for the flyout to anchor
     * against.
     *
     * e.g. $items = [
     *     0 => ['items' => ['Label' => ['route/name', ['arg' => 'value']]]],
     * ]
     *
     * @param string $title
     * @param UrlGenerator $urlGenerator
     * @param string $navBarFont
     * @param string $navBarFontSize
     * @param array $items
     * @return string
     */
    public static function generate(
        string $title,
        UrlGenerator $urlGenerator,
        string $navBarFont,
        string $navBarFontSize,
        array $items = [],
    ): string {
        $finalString = '';
        /**
         * @var array $levelItem
         */
        foreach ($items as $levelItem) {
            $builtItems = '';
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
                $builtItems .= '<li><a class="dropdown-item" href="'
                    . Html::encode($urlGenerator->generate($actionName, $actionArguments))
                    . '" style="font-size: ' . Html::encode($navBarFontSize) . 'px;'
                    . ' font-family: ' . Html::encode($navBarFont) . ';">'
                    . Html::encode($key)
                    . '</a></li>';
            }
            $finalString = '<span class="dropdown-item dropdown-submenu-toggle" tabindex="0"'
                . ' role="menuitem" aria-haspopup="true"'
                . ' style="font-size: ' . Html::encode($navBarFontSize) . 'px;'
                . ' font-family: ' . Html::encode($navBarFont) . ';">'
                . Html::encode($title)
                . new I()->addClass('bi bi-chevron-right submenu-caret')->render()
                . '</span>'
                . '<ul class="dropdown-menu dropdown-menu-submenu">'
                . $builtItems
                . '</ul>';
        }
        return $finalString;
    }
}
