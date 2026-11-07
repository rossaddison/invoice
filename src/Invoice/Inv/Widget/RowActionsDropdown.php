<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget;

use Yiisoft\Html\Html;

/**
 * The opening markup of the per-row "download PDF / email" dropdown in the
 * invoice grids (staff inv/index and inv/guest). It is the `before` string of
 * an ActionColumn, which is one fixed string reused for EVERY row, so it must
 * not carry an `id`: it used to be `id="dropdownMenuButton"` (with the menu's
 * aria-labelledby pointing at it), repeated on every row of the page --
 * duplicate ids are invalid HTML (WCAG 4.1.1) and left each menu labelled by
 * whichever row's button the browser found first.
 *
 * A Bootstrap dropdown needs no id: the toggle has its own aria-label and the
 * menu's button group is named with an aria-label.
 *
 * The caller appends the ActionButtons (each one a `.dropdown-item`) and, via
 * the column's `after`, the closing `</div></div></div>`.
 */
final class RowActionsDropdown
{
    public static function open(string $label): string
    {
        return Html::openTag('div', ['class' => 'dropdown'])
            . Html::openTag('button', [
                'class' => 'btn btn-info dropdown-toggle',
                'type' => 'button',
                'data-bs-toggle' => 'dropdown',
                'aria-haspopup' => 'true',
                'aria-expanded' => 'false',
                'aria-label' => $label,
            ])
            . Html::closeTag('button')
            . Html::openTag('div', ['class' => 'dropdown-menu'])
            . Html::openTag('div', ['class' => 'btn-group', 'role' => 'group', 'aria-label' => $label]);
    }
}
