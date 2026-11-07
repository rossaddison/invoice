<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Invoice\Inv\Widget\RowActionsDropdown;
use Testo\Assert;
use Testo\Test;

/**
 * The per-row dropdown's opening markup is reused verbatim for every row of a
 * grid, so it must carry no id (a repeated id is invalid HTML and mislabels
 * the menus).
 */
#[Test]
final class RowActionsDropdownTest
{
    public function carriesNoIdAndNoAriaLabelledbyBecauseItIsRepeatedForEveryRow(): void
    {
        $html = RowActionsDropdown::open('Download PDF');

        Assert::false(str_contains($html, ' id='));
        Assert::false(str_contains($html, 'aria-labelledby'));
        Assert::false(str_contains($html, 'dropdownMenuButton'));
    }

    public function theToggleAndTheMenuGroupAreBothNamedByALabel(): void
    {
        $html = RowActionsDropdown::open('Download PDF / Email');

        Assert::same(substr_count($html, 'aria-label="Download PDF / Email"'), 2);
        Assert::true(str_contains($html, 'role="group"'));
    }

    public function isAStandardBootstrapDropdownThatTheScriptsCanOpen(): void
    {
        $html = RowActionsDropdown::open('x');

        Assert::true(str_contains($html, 'data-bs-toggle="dropdown"'));
        Assert::true(str_contains($html, 'dropdown-toggle'));
        Assert::true(str_contains($html, 'class="dropdown-menu"'));
        Assert::true(str_contains($html, 'aria-expanded="false"'));
    }

    public function leavesExactlyTheTagsTheColumnsAfterStringCloses(): void
    {
        $html = RowActionsDropdown::open('x');

        // opened: dropdown, dropdown-menu, btn-group  (the toggle button is closed here)
        Assert::same(substr_count($html, '<div'), 3);
        Assert::same(substr_count($html, '</div>'), 0);
        Assert::same(substr_count($html, '<button'), substr_count($html, '</button>'));
    }
}
