<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder\Trait;

use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
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
        foreach ($salesOrderRepo->getStatuses($this->translator) as
            $key => $status) {
            $optionsDataSalesOrderStatus[$key] = (string) $status['label'];
        }
        return [
            'client' => $clientRepo->optionsData($ucR),
            'deliveryLocation' => $optionsDataDeliveryLocations,
            'group' => $optionsDataGroup,
            'salesOrderStatus' => $optionsDataSalesOrderStatus,
        ];
    }

    /** @return array<string, string> */
    public function optionsDataClientsFilter(SoR $soR): array
    {
        $optionsDataClients = [];
        // Get all the sales orders that have been made for clients
        $salesorders = $soR->findAllPreloaded();
        /**
         * @var SalesOrder $salesorder
         */
        foreach ($salesorders as $salesorder) {
            $client = $salesorder->getClient();
            if (null !== $client && strlen($client->getClientFullName()) > 0) {
                $fullName = $client->getClientFullName();
                $optionsDataClients[$client->getClientFullName()] =
                    !empty($fullName) ? $fullName : '';
            }
        }
        return $optionsDataClients;
    }
}
