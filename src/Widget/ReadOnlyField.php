<?php

declare(strict_types=1);

namespace App\Widget;

use Yiisoft\Html\Html;

/**
 * Label + plain-text value pair for a pure display/view page — replaces a
 * disabled Field:: widget, which renders identically to an editable one and
 * has confirmed live to mislead a user into trying to change a value on a
 * view-only page (see docs/READONLY_VIEW_FIELDS_AUGUST_2026.md).
 * Bootstrap's form-control-plaintext class is purpose-built for exactly
 * this: a value shown inline with no input-box chrome, while keeping the
 * same label/spacing layout a real form field would have.
 */
final class ReadOnlyField
{
    /**
     * @param array<string, string> $wrapperAttributes
     */
    public static function render(
        string $label,
        ?string $value,
        array $wrapperAttributes = ['class' => 'mb-3'],
    ): void {
        echo Html::openTag('div', $wrapperAttributes);
        echo Html::label($label)->attributes(['class' => 'text-muted mb-0'])->render();
        echo Html::div($value ?? '', ['class' => 'form-control-plaintext'])->render();
        echo Html::closeTag('div');
    }
}
