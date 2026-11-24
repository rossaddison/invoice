<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder\Trait;

use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Invoice\Client\ClientOptionLabels;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\SalesOrder\SalesOrderRepository as SoR;
use App\Invoice\UserClient\UserClientRepository as UCR;

trait SalesOrderOptionsTrait
{
    private function optionsData(
        int $client_id,
        CR $clientRepo,
        DR $delRepo,
        GR $groupRepo,
        SoR $salesOrderRepo,
        UCR $ucR,
    ): array {
        $dLocs = $delRepo->repoClientquery($client_id);
        $optionsDataDeliveryLocations = [];
        /**
         * @var DeliveryLocation $dLoc
         */
        foreach ($dLocs as $dLoc) {
            $dLocId = $dLoc->reqId();
            $optionsDataDeliveryLocations[$dLocId] =
                ($dLoc->getAddress1() ?? '')
                    . ', ' . ($dLoc->getAddress2() ?? '') . ', '
                    . ($dLoc->getCity() ?? '') . ', '
                    . ($dLoc->getZip() ?? '');
        }
        $optionsDataGroup = [];
        /**
         * @var \App\Infrastructure\Persistence\Group\Group $group
         */
        foreach ($groupRepo->findAllPreloaded() as $group) {
            $optionsDataGroup[$group->reqId()] = $group->getName();
        }

        $optionsDataSalesOrderStatus = [];
        /**
         * @var string $key
         * @var array $status
         */
        foreach ($salesOrderRepo->getStatuses($this->translator) as $key => $status) {
            $optionsDataSalesOrderStatus[$key] = (string) $status['label'];
        }
        return [
            'client' => $clientRepo->optionsData($ucR),
            'deliveryLocation' => $optionsDataDeliveryLocations,
            'group' => $optionsDataGroup,
            'salesOrderStatus' => $optionsDataSalesOrderStatus,
        ];
    }

    /**
     * Keyed by client id, so two clients with the same name are two entries and the filter can tell them apart
     * (SalesOrderRepository::filterClient() matches on the id). It used to be keyed by the full name.
     *
     * @return array<int, string>
     */
    public function optionsDataClientsFilter(SoR $soR): array
    {
        // Get all the sales orders that have been made for clients
        $salesorders = $soR->findAllPreloaded();
        $clients = [];
        /**
         * @var SalesOrder $salesorder
         */
        foreach ($salesorders as $salesorder) {
            $clients[] = $salesorder->getClient();
        }
        return ClientOptionLabels::forFilter($clients);
    }
}
