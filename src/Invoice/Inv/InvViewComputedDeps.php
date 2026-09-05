<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeForm;
use App\Widget\Bootstrap5ModalTranslatorMessageWithoutAction;

/**
 * Values `Trait\View::view()` computes once per request before building
 * its view-data array — bundled here, the same way `InvViewService`
 * already bundles that method's injected repositories via
 * `InvViewCoreDeps`/`InvViewItemDeps`/`InvViewMetaDeps`/
 * `InvViewAllowanceDeps`/`InvViewRelationDeps`, so extracting the array
 * itself into its own private method (SonarQube php:S138 — `view()` was
 * over the 150-line ceiling) doesn't just trade it for a php:S107
 * too-many-parameters violation instead.
 */
final class InvViewComputedDeps
{
    public function __construct(
        /** @var array<array-key, mixed> */
        public readonly array $enabled_gateways,
        public readonly string $sales_order_number,
        public readonly InvAllowanceChargeForm $invAllowanceChargeForm,
        public readonly bool $read_only,
        /** @var array<array-key, mixed> */
        public readonly array $inv_custom_values,
        public readonly bool $is_recurring,
        public readonly bool $show_buttons,
        public readonly string $url_key,
        public readonly int $client_id,
        public readonly ?int $delivery_location_id,
        public readonly Bootstrap5ModalTranslatorMessageWithoutAction $bootstrap5ModalTranslatorMessageWithoutAction,
        public readonly InvAmount $inv_amount,
    ) {
    }
}
