<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget;

/**
 * Dropdown-filter option arrays for InvsListWidget.
 *
 * Replaces the seven individual withOptions*DropDownFilter() setters on the
 * widget, so that the total method count stays at or below 20 (S1448).
 *
 * Takes a single keyed array rather than one parameter per filter to stay
 * within S107 (≤ 7 constructor parameters) as more filters are added.
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

    /** @psalm-var array<array-key, array<array-key, string>|string> */
    public readonly array $categorySecondaryRun;

    /**
     * @psalm-param array{
     *     invNumber?: array<array-key, array<array-key, string>|string>,
     *     creditInvNumber?: array<array-key, array<array-key, string>|string>,
     *     familyName?: array<array-key, array<array-key, string>|string>,
     *     clients?: array<array-key, array<array-key, string>|string>,
     *     clientGroup?: array<array-key, array<array-key, string>|string>,
     *     yearMonth?: array<array-key, array<array-key, string>|string>,
     *     status?: array<array-key, array<array-key, string>|string>,
     *     categorySecondaryRun?: array<array-key, array<array-key, string>|string>,
     * } $options
     */
    public function __construct(array $options = [])
    {
        $this->invNumber             = $options['invNumber'] ?? [];
        $this->creditInvNumber       = $options['creditInvNumber'] ?? [];
        $this->familyName            = $options['familyName'] ?? [];
        $this->clients               = $options['clients'] ?? [];
        $this->clientGroup           = $options['clientGroup'] ?? [];
        $this->yearMonth             = $options['yearMonth'] ?? [];
        $this->status                = $options['status'] ?? [];
        $this->categorySecondaryRun  = $options['categorySecondaryRun'] ?? [];
    }
}
