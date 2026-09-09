<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\GatewayStatus;

use App\Infrastructure\Persistence\GatewayStatus\GatewayStatus;

/**
 * Applies GatewayStatusFilter to a list<GatewayStatus> -- three independent
 * array_filter() passes composed together, so every filter that's actually
 * set narrows the result further rather than any one silently replacing an
 * earlier one. Same discipline InvCombinedFilterTrait exists to guarantee
 * for inv/index's own multi-filter Select query, just against a flat array
 * instead of a Cycle query (this page's data is already fully loaded from
 * gateway_status's own small SQLite table -- see
 * docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md -- so there's no query to thread
 * conditions through).
 */
final class GatewayStatusRows
{
    /**
     * @param list<GatewayStatus> $gateways
     * @return list<GatewayStatus>
     */
    public static function filter(
        array $gateways,
        GatewayStatusFilter $filter,
    ): array {
        $gateways = self::filterByRegion($gateways, $filter->filterRegion);
        $gateways = self::filterBySandboxStatus(
            $gateways,
            $filter->filterSandboxStatus,
        );
        return self::filterByNeedsRetest($gateways, $filter->filterNeedsRetest);
    }

    /**
     * @param list<GatewayStatus> $gateways
     * @return list<GatewayStatus>
     */
    private static function filterByRegion(array $gateways, ?string $region): array
    {
        if ($region === null || $region === '') {
            return $gateways;
        }
        return array_values(array_filter(
            $gateways,
            static fn (GatewayStatus $gateway): bool => in_array(
                $region,
                $gateway->getRegionsList(),
                true,
            ),
        ));
    }

    /**
     * @param list<GatewayStatus> $gateways
     * @return list<GatewayStatus>
     */
    private static function filterBySandboxStatus(
        array $gateways,
        ?string $sandboxStatus,
    ): array {
        if ($sandboxStatus === null || $sandboxStatus === '') {
            return $gateways;
        }
        return array_values(array_filter(
            $gateways,
            static fn (GatewayStatus $gateway): bool =>
                $gateway->getSandboxStatus() === $sandboxStatus,
        ));
    }

    /**
     * @param list<GatewayStatus> $gateways
     * @return list<GatewayStatus>
     */
    private static function filterByNeedsRetest(
        array $gateways,
        ?string $needsRetest,
    ): array {
        if ($needsRetest !== 'yes' && $needsRetest !== 'no') {
            return $gateways;
        }
        $wantsRetest = $needsRetest === 'yes';
        return array_values(array_filter(
            $gateways,
            static fn (GatewayStatus $gateway): bool =>
                $gateway->getNeedsRetestSinceUpdate() === $wantsRetest,
        ));
    }
}
