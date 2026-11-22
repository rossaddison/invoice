<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Setting;

use Testo\Assert;
use Testo\Test;

/**
 * The Content-Security-Policy (public/.htaccess and ContentSecurityPolicyMiddleware) allows scripts from 'self' and
 * named CDNs only, with no 'unsafe-inline'. The browser therefore refuses an inline onclick="..." or onChange="..."
 * attribute, and logs one console error for each. On setting/debug_index that meant two dead filter dropdowns and a
 * delete button whose confirm() never ran, so one click deleted a setting.
 *
 * The page uses the app's CSP-safe forms instead: class "native-reset" (the delegated listener in
 * src/typescript/data-actions.ts submits the form) and data-confirm (the same listener asks first).
 */
#[Test]
final class DebugIndexCspTest
{
    private function root(): string
    {
        return dirname(__DIR__, 4);
    }

    /** The view's code with its comments taken out, so an explanation of the rule does not trip the check for it. */
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

    public function theViewHasNoInlineScriptAttributes(): void
    {
        $view = $this->view();

        Assert::same(preg_match('/[\'"]on[a-z]+[\'"]\s*=>|\son(click|change)\s*=/i', $view), 0);
    }

    public function bothFilterDropdownsUseTheNativeResetClass(): void
    {
        $view = $this->view();

        Assert::same(substr_count($view, 'DropdownFilter::widget()'), 2);
        Assert::same(substr_count($view, "->addAttributes(['class' => 'native-reset'])"), 2);
        Assert::false(str_contains($view, 'new DropdownFilter()'));
    }

    public function theDeleteButtonConfirmsThroughDataConfirm(): void
    {
        $view = $this->view();

        Assert::true(str_contains($view, "'data-confirm' => \$translator->translate('delete.record.warning')"));
    }

    public function theDelegatedListenerThatServicesThoseAttributesStillExists(): void
    {
        $script = (string) file_get_contents($this->root() . '/src/typescript/data-actions.ts');

        Assert::true(str_contains($script, "classList.contains('native-reset')"));
        Assert::true(str_contains($script, "closest<HTMLElement>('[data-confirm]')"));
        Assert::true(str_contains($script, 'e.preventDefault()'));
    }
}
