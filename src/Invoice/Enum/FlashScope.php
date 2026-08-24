<?php

declare(strict_types=1);

namespace App\Invoice\Enum;

use Yiisoft\Bootstrap5\AlertVariant;

// Namespaces a flash message's session key by which layout should render
// it. `Yiisoft\Session\Flash\Flash` (yiisoft/session) has no concept of
// this itself — see docs/WEBSHOP_INPROCESS_MERGE_AUGUST_2026.md — it's a
// flat array<string $key, mixed $value> keyed only by alert severity
// ('warning', 'info', ...), so any two independent flash-reading layouts
// sharing one session (the staff/guest-portal `App\Invoice\BaseController::
// alert()` -> resources/views/invoice/layout/alert.php reader, and the
// storefront's own resources/views/layout/templates/storefront/main.php
// reader) can otherwise read and render each other's messages. Prefixing
// the *key* (not the value) is deliberate: `Flash::get(string $key)` only
// marks that one key's counter for deletion, unlike `getAll()`, which
// marks every key it iterates regardless of whether the caller actually
// renders it — so a reader that only looks up its own scope's keys never
// prematurely expires a message meant for a different layout.
//
// Unscoped (no prefix) remains the staff/guest-portal default — every
// existing `flashMessage()` call site outside App\Webshop\* is untouched.
enum FlashScope: string
{
    case Shop = 'shop';

    public function prefix(string $level): string
    {
        return $this->value . '.' . $level;
    }

    /**
     * The fixed set of alert-severity level names a flash reader should
     * enumerate — derived from `AlertVariant` (the single source of truth
     * for what a rendered alert can actually look like) rather than a
     * second, hand-maintained literal list.
     *
     * @return list<string>
     */
    public static function levels(): array
    {
        return array_map(
            static fn (AlertVariant $variant): string => strtolower($variant->name),
            AlertVariant::cases(),
        );
    }
}
