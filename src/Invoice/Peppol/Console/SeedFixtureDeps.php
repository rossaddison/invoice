<?php

declare(strict_types=1);

namespace App\Invoice\Peppol\Console;

use App\Invoice\Client\ClientRepository;
use App\Invoice\ClientPeppol\ClientPeppolRepository;
use App\Invoice\Contract\ContractRepository;
use App\Invoice\Delivery\DeliveryRepository;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository;
use App\Invoice\DeliveryParty\DeliveryPartyRepository;
use App\Invoice\Group\GroupRepository;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\InvItem\InvItemRepository;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository;
use App\Invoice\InvItemAmount\InvItemAmountRepository;
use App\Invoice\PostalAddress\PostalAddressRepository;
use App\Invoice\Product\ProductRepository;
use App\Invoice\SalesOrder\SalesOrderRepository;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\UnitPeppol\UnitPeppolRepository;
use App\Invoice\Upload\UploadRepository;
use App\User\UserRepository;
use Cycle\Database\DatabaseManager;

/**
 * Repository bundle for SeedPeppolHappyPathFixtureCommand -- kept out of
 * that command's own constructor (Sonar S107) the same way
 * InvPeppolCoreDeps/NetworkDeps/etc. keep InvController's action methods
 * under the same limit.
 */
final class SeedFixtureDeps
{
    public function __construct(
        public readonly ClientRepository $clientRepo,
        public readonly PostalAddressRepository $paR,
        public readonly ClientPeppolRepository $cpR,
        public readonly DeliveryLocationRepository $dlR,
        public readonly ProductRepository $productRepo,
        public readonly GroupRepository $gR,
        public readonly TaxRateRepository $trR,
        public readonly UnitPeppolRepository $unpR,
        public readonly InvRepository $invRepo,
        public readonly InvItemRepository $iiR,
        public readonly InvItemAmountRepository $iiaR,
        public readonly InvAmountRepository $iaR,
        public readonly DeliveryRepository $delRepo,
        public readonly ContractRepository $contractRepo,
        public readonly DeliveryPartyRepository $delPartyRepo,
        public readonly UploadRepository $upR,
        public readonly InvAllowanceChargeRepository $aciR,
        public readonly InvItemAllowanceChargeRepository $aciiR,
        public readonly SalesOrderItemRepository $soiR,
        public readonly SalesOrderRepository $soR,
        public readonly UserRepository $uR,
        public readonly DatabaseManager $dbal,
    ) {}
}
