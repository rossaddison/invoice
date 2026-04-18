<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Invoice\Entity\
{
    Contract, Delivery, Inv, PaymentMethod, PostalAddress, Setting, Upload,
    UserClient
};
use App\Infrastructure\Persistence\{
    Client\Client,
    DeliveryLocation\DeliveryLocation,
    Group\Group,
    TaxRate\TaxRate
};
use App\Invoice\{
    Client\ClientRepository as CR,
    Contract\ContractRepository as ContractRepo,
    Delivery\DeliveryRepository as DelRepo,
    DeliveryLocation\DeliveryLocationRepository as DLR,
    Group\GroupRepository as GR,
    Inv\InvRepository as IR,
    PaymentMethod\PaymentMethodRepository as PMR,
    PostalAddress\PostalAddressRepository as paR,
    UserClient\UserClientRepository as UCR,
};
use App\Invoice\Helpers\Peppol\PeppolArrays;
use Yiisoft\{
    Data\Reader\DataReaderInterface as DRI,
    Data\Reader\SortableDataInterface as SDI,
};

trait OptionsData
{
    private function editOptionsData(
        PeppolArrays $peppol_array,
        Inv $inv,
        int $client_id,
        CR $clientRepo,
        ContractRepo $contractRepo,
        DelRepo $deliveryRepo,
        DLR $delRepo,
        GR $groupRepo,
        IR $invRepo,
        paR $paR,
        PMR $pmRepo,
        UCR $ucR,
    ): array {
        $contracts = $contractRepo->repoClient($inv->getClientId());
        $optionsDataContract = [];
        /**
         * @var Contract $contract
         */
        foreach ($contracts as $contract) {
            $id = $contract->getId();
            if (null !== $id) {
                $optionsDataContract[$id] = ($contract->getName() ?? '')
                    . ' ' . ($contract->getReference() ?? '');
            }
        }
        $deliverys = $deliveryRepo->findAllPreloaded();
        $optionsDataDelivery = [];
        /**
         * @var Delivery $delivery
         */
        foreach ($deliverys as $delivery) {
            $delivery_id = $delivery->getId();
            /**
             * @var \DateTimeImmutable $startDate
             */
            $startDate = $delivery->getStartDate();
            /**
             * @var \DateTimeImmutable $endDate
             */
            $endDate = $delivery->getEndDate();
            if (null != $delivery_id) {
                $optionsDataDelivery[$delivery_id]
                = $startDate->format($this->dateHelper->style())
                . ' ----- '
                . $endDate->format($this->dateHelper->style())
                . ' ---- '
                . $this->sR->getSetting('stand_in_code')
                . ' ---- '
                . $peppol_array->getCurrentStandInCodeValue($this->sR);
            }
        }

        $dLocs = $delRepo->repoClientquery((string) $client_id);
        $optionsDataDeliveryLocations = [];
        /**
         * @var DeliveryLocation $dLoc
         */
        foreach ($dLocs as $dLoc) {
            $dLocId = $dLoc->reqId();
            $optionsDataDeliveryLocations[$dLocId] = ($dLoc->getAddress1()
                ?? '') . ', ' . ($dLoc->getAddress2() ?? '') . ', '
                    . ($dLoc->getCity() ?? '') . ', '
                    . ($dLoc->getZip() ?? '');
            
        }
        $optionsDataGroup = [];
        /**
         * @var Group $group
         */
        foreach ($groupRepo->findAllPreloaded() as $group) {
            $optionsDataGroup[$group->reqId()] = $group->getName();
        }

        $optionsDataPaymentMethod = [];
        /**
         * @var PaymentMethod $pymntMthd
         */
        foreach ($pmRepo->findAllPreloaded() as $pymntMthd) {
            if ($pymntMthd->getActive()) {
                $optionsDataPaymentMethod[$pymntMthd->getId()] =
                    $pymntMthd->getName();
            }
        }
        $optionsDataPaymentTerm = [];
        /**
         * @var string $key
         * @var string $value
         */
        foreach ($this->sR->getPaymentTermArray(
                $this->translator) as $key => $value) {
            $optionsDataPaymentTerm[$key] = $value;
        }
        $optionsDataPostalAddress = [];
        /**
         * @var PostalAddress $postalAddress
         */
        foreach ($paR->repoClientAll((string) $client_id) as $postalAddress) {
            $optionsDataPostalAddress[$postalAddress->getId()] =
                    $postalAddress->getStreetName()
                        . ', ' . $postalAddress->getAdditionalStreetName()
                        . ', ' . $postalAddress->getBuildingNumber() . ', '
                        . $postalAddress->getCityName();
        }

        $optionsDataInvoiceStatus = [];
        /**
         * @var string $key
         * @var array $status
         */
        foreach ($invRepo->getStatuses($this->translator) as $key => $status) {
            $optionsDataInvoiceStatus[$key] = (string) $status['label'];
        }
        return [
            'client' => $clientRepo->optionsData($ucR),
            'contract' => $optionsDataContract,
            'delivery' => $optionsDataDelivery,
            'deliveryLocation' => $optionsDataDeliveryLocations,
            'group' => $optionsDataGroup,
            'invoiceStatus' => $optionsDataInvoiceStatus,
            'paymentMethod' => $optionsDataPaymentMethod,
            'paymentTerm' => $optionsDataPaymentTerm,
            'postalAddress' => $optionsDataPostalAddress,
        ];
    }
    
    public function optionsDataClientsFilter(IR $iR): array
    {
        $optionsDataClients = [];
        // Get all the invoices that have been made out to clients with user
        // accounts
        $invs = $iR->findAllPreloaded();
        /**
         * @var Inv $inv
         */
        foreach ($invs as $inv) {
            $client = $inv->getClient();
            if (null !== $client) {
                if (strlen($client->getClientFullName()) > 0) {
                    $fullName = $client->getClientFullName();
                    $optionsDataClients[$client->getClientFullName()] =
                        !empty($fullName) ? $fullName : '';
                }
            }
        }
        return $optionsDataClients;
    }

    /**
     * If one user pays for more than one client, find all clients
     */
    public function optionsDataUserClientsFilter(UCR $ucR, string $userId): array
    {
        $optionsDataClients = [];
        $userClients = $ucR->repoClientquery($userId);
        /**
         * @var UserClient userClient
         */
        foreach ($userClients as $userClient) {
            $client = $userClient->getClient();
            if (null !== $client) {
                if (strlen($client->getClientFullName()) > 0) {
                    $fullName = $client->getClientFullName();
                    $optionsDataClients[$client->getClientFullName()] =
                        !empty($fullName) ? $fullName : '';
                }
            }
        }
        return $optionsDataClients;
    }

    public function optionsDataYearMonthFilter(): array
    {
        $ym = [];
        for ($y = 2024, $now = (int) date('Y') + 10; $y <= $now; ++$y) {
            $months = [
                '01','02','03','04','05','06','07','08','09','10','11','12'
            ];
            foreach ($months as $month) {
                $yearMonth = (string) $y . '-' . $month;
                $ym[$yearMonth] = $yearMonth;
            }
        }
        return $ym;
    }

    public function optionsDataClientGroupFilter(CR $cR): array
    {
        $clientGroup = [];
        $allClients = $cR->findAllPreloaded();
        /**
         * @var Client $client
         */
        foreach ($allClients as $client) {
            if (!in_array($client->getClientGroup(), $clientGroup)) {
                /**
                 * @var string $client->getClientGroup()
                 */
                $group = $client->getClientGroup();
                if (null !== $group) {
                    $clientGroup[$group] = $group;
                }
            }
        }
        return $clientGroup;
    }

    public function optionsDataInvNumberFilter(IR $iR): array
    {
        $optionsDataInvNumbers = [];
        // Get all the invoices that have been made out to clients with user
        // accounts
        $invs = $iR->findAllPreloaded();
        /**
         * @var Inv $inv
         */
        foreach ($invs as $inv) {
            $invNumber = $inv->getNumber();
            if (null !== $invNumber) {
                if (!in_array($invNumber, $optionsDataInvNumbers)) {
                    $optionsDataInvNumbers[$invNumber] = $invNumber;
                }
            }
        }
        return $optionsDataInvNumbers;
    }

    public function optionsDataFamilyNameFilter(IR $iR): array
    {
        $optionsDataFamilyNames = [];
        // Get all the invoices that have been made out to clients with user
        // accounts
        $invs = $iR->findAllPreloaded();
        /**
         * @var Inv $inv
         */
        foreach ($invs as $inv) {
            $familyName = $inv->getFirstItemFamilyName();
            if (strlen($familyName) > 0) {
                if (!in_array($familyName, $optionsDataFamilyNames)) {
                    $optionsDataFamilyNames[$familyName] = $familyName;
                }
            }
        }
        return $optionsDataFamilyNames;
    }

    /**
     * Note function invsStatusWithSortGuest(
     *         $iR, $status, $user_clients, $sort)
     * has been used to generate
     */
    public function optionsDataCreditInvNumberFilter(IR $iR): array
    {
        $optionsData = [];
        /** @var Inv $inv */
        foreach ($iR->findAllPreloaded() as $inv) {
            $parentId = $inv->getCreditinvoiceParentId();
            if ($parentId !== '' && $parentId !== '0') {
                $parentInv = $iR->repoInvUnLoadedquery($parentId);
                if (null !== $parentInv) {
                    $number = $parentInv->getNumber();
                    if (null !== $number && !isset($optionsData[$number])) {
                        $optionsData[$number] = $number;
                    }
                }
            }
        }
        return $optionsData;
    }

    /**
     * @param SDI&DRI $invs
     * @param IR $iR
     * @return array
     */
    public function optionsDataCreditInvNumberGuestFilter(
        SDI
            &DRI $invs,
        IR $iR): array
    {
        $optionsData = [];
        /** @var Inv $inv */
        foreach ($invs as $inv) {
            $parentId = $inv->getCreditinvoiceParentId();
            if ($parentId !== '' && $parentId !== '0') {
                $parentInv = $iR->repoInvUnLoadedquery($parentId);
                if (null !== $parentInv) {
                    $number = $parentInv->getNumber();
                    if (null !== $number && !isset($optionsData[$number])) {
                        $optionsData[$number] = $number;
                    }
                }
            }
        }
        return $optionsData;
    }

    public function optionsDataInvNumberGuestFilter(
        SDI
            &DRI $invs): array
    {
        $optionsDataInvNumbers = [];
        /**
         * @var Inv $inv
         */
        foreach ($invs as $inv) {
            $invNumber = $inv->getNumber();
            if (null !== $invNumber) {
                if (!in_array($invNumber, $optionsDataInvNumbers)) {
                    $optionsDataInvNumbers[$invNumber] = $invNumber;
                }
            }
        }
        return $optionsDataInvNumbers;
    }

    public function optionsDataStatusFilter(IR $iR): array
    {
        $optionsDataStatus = [];
        $statuses = $iR->getStatuses($this->translator);

        /** @var array<int, array<string, string>> $statuses */
        foreach (array_keys($statuses) as $statusId) {
            $emoji = $iR->getSpecificStatusArrayEmoji($statusId);
            $label = $iR->getSpecificStatusArrayLabel((string) $statusId);
            $optionsDataStatus[$statusId] = $emoji . ' ' . $label;
        }

        return $optionsDataStatus;
    }
}
