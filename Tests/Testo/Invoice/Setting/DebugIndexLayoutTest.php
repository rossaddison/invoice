<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Setting;

use Testo\Assert;
use Testo\Test;

/**
 * src/Invoice/Asset/invoice/css/base.css sets `overflow-x: hidden` on html, so anything wider than the window is
 * clipped and there is no scrollbar to reach it. On setting/debug_index a long unbroken setting value stretched the
 * table past the window, and the action buttons in the last column went with it.
 *
 * The page now wraps the key and value cells (Bootstrap's text-break) and puts the grid in a scrolling container
 * (table-responsive), so it fits, and scrolls if it still does not.
 */
#[Test]
final class DebugIndexLayoutTest
{
    private function root(): string
    {
        return dirname(__DIR__, 4);
    }

    /** The view's code with its comments taken out, so an explanation of a rule does not satisfy the check for it. */
    private function view(): string
    {
        $source = (string) file_get_contents($this->root() . '/resources/views/invoice/setting/debug_index.php');
        $code = '';
        foreach (\PhpToken::tokenize($source) as $token) {
            if ($token->id !== T_COMMENT && $token->id !== T_DOC_COMMENT) {
                $code .= $token->text;
            }
        }
        return $code;
    }

    /** The code of one DataColumn: from its `property:` to the next `new DataColumn(` or `new ActionColumn(`. */
    private function column(string $property): string
    {
        $view = $this->view();
        $start = strpos($view, "property: '" . $property . "',");
        if ($start === false) {
            throw new \LogicException('column ' . $property . ' not found in debug_index.php');
        }
        $end = strpos($view, 'new ', $start);
        return substr($view, $start, ($end === false ? strlen($view) : $end) - $start);
    }

    public function theKeyAndValueCellsWrapLongText(): void
    {
        Assert::true(str_contains($this->column('setting_key'), "bodyClass: 'text-break'"));
        Assert::true(str_contains($this->column('setting_value'), "bodyClass: 'text-break'"));
    }

    public function theGridSitsInAScrollingContainer(): void
    {
        Assert::true(str_contains($this->view(), "->containerAttributes(['class' => 'table-responsive'])"));
    }

    public function eachRowKeepsItsButtonsBesideTheSettingsNameEvenWhenAValueMakesItTall(): void
    {
        Assert::true(str_contains($this->view(), "->bodyRowAttributes(['class' => 'align-top'])"));
        Assert::false(str_contains($this->view(), 'align-middle'));
    }

    public function eachRowHasTheViewEditAndDeleteButtons(): void
    {
        $view = $this->view();
        $start = strpos($view, 'new ActionColumn(');
        if ($start === false) {
            throw new \LogicException('the ActionColumn is missing from debug_index.php');
        }
        $buttons = substr($view, $start);

        foreach (['setting/view', 'setting/edit', 'setting/delete'] as $route) {
            Assert::true(str_contains($buttons, "generate('" . $route . "'"), $route . ' button is missing');
        }
        Assert::same(substr_count($buttons, 'new ActionButton('), 3);
    }

    public function theActionButtonsAreStillTheLastColumn(): void
    {
        $view = $this->view();

        Assert::true(strpos($view, 'new ActionColumn(') > strpos($view, "property: 'setting_value',"));
    }

    public function theSiteWideRuleThatClipsWideContentIsStillThereSoThePageMustCopeWithIt(): void
    {
        $css = (string) file_get_contents($this->root() . '/src/Invoice/Asset/invoice/css/base.css');

        Assert::same(preg_match('/overflow-x:\s*hidden/', $css), 1);
    }
}
