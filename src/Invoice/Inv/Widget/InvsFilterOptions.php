<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget;

/**
 * Dropdown-filter option arrays for InvsListWidget.
 *
 * Replaces the seven individual withOptions*DropDownFilter() setters on the
 * widget, so that the total method count stays at or below 20 (S1448).
 */
final class InvsFilterOptions
{
    /** @psalm-var array<array-key, array<array-key, string>|string> */
    public readonly array $invNumber;

    /** @psalm-var array<array-key, array<array-key, string>|string> */
    public readonly array $creditInvNumber;

    /** @psalm-var array<array-key, array<array-key, string>|string> */
    public readonly array $familyName;

    /** @psalm-var array<array-key, array<array-key, string>|string> */
    public readonly array $clients;

    /** @psalm-var array<array-key, array<array-key, string>|string> */
    public readonly array $clientGroup;

    /** @psalm-var array<array-key, array<array-key, string>|string> */
    public readonly array $yearMonth;

    /** @psalm-var array<array-key, array<array-key, string>|string> */
    public readonly array $status;

    /**
     * @psalm-param array<array-key, array<array-key, string>|string> $invNumber
     * @psalm-param array<array-key, array<array-key, string>|string> $creditInvNumber
     * @psalm-param array<array-key, array<array-key, string>|string> $familyName
     * @psalm-param array<array-key, array<array-key, string>|string> $clients
     * @psalm-param array<array-key, array<array-key, string>|string> $clientGroup
     * @psalm-param array<array-key, array<array-key, string>|string> $yearMonth
     * @psalm-param array<array-key, array<array-key, string>|string> $status
     */
    public function __construct(
        array $invNumber = [],
        array $creditInvNumber = [],
        array $familyName = [],
        array $clients = [],
        array $clientGroup = [],
        array $yearMonth = [],
        array $status = [],
    ) {
        $this->invNumber        = $invNumber;
        $this->creditInvNumber  = $creditInvNumber;
        $this->familyName       = $familyName;
        $this->clients          = $clients;
        $this->clientGroup      = $clientGroup;
        $this->yearMonth        = $yearMonth;
        $this->status           = $status;
    }
}
