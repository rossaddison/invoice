<?php

declare(strict_types=1);

namespace App\Command\Invoice;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Group\Group;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAllowanceCharge\InvAllowanceCharge;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvItemAllowanceCharge\InvItemAllowanceCharge;
use App\Infrastructure\Persistence\InvItemAmount\InvItemAmount;
use App\Infrastructure\Persistence\InvTaxRate\InvTaxRate;
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Unit\Unit;
use App\Infrastructure\Persistence\UserClient\UserClient;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Infrastructure\Persistence\User\User;
use Cycle\ORM\EntityManager;
use Doctrine\Inflector\InflectorFactory;
use Faker\Factory;
use Faker\Generator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Yii\Console\ExitCode;

final class ItemsCommand extends Command
{
    protected static string $defaultName = 'invoice/items';

    /** @var User[] */
    private array $users = [];
    /** @var Client[] */
    private array $clients = [];
    /** @var InvTaxRate[] */
    private array $invTaxRates = [];
    /** @var UserClient[] */
    private array $userClients = [];
    /** @var UserInv[] */
    private array $userInvs = [];
    /** @var Family[] */
    private array $families = [];
    /** @var Group[] */
    private array $groups = [];
    /** @var Product[] */
    private array $products = [];
    private array $productNames = [
        'Mouse', 'Keyboard', 'Screen', 'Hard drive', 'Box', 'Motherboard'
    ];
    private array $productDescriptions = [
        '3-button', 'US', '24inch x 16inch', '1 TB', 'Standard', 'Intel i15'
    ];
    /** @var TaxRate[] */
    private array $taxRates = [];
    /** @var Unit[] */
    private array $units = [];
    /** @var Inv[] */
    private array $inv = [];
    /** @var InvAmount[] */
    private array $invAmount = [];
    /** @var InvItem[] */
    private array $invItems = [];
    /** @var InvItemAmount[] */
    private array $invItemAmounts = [];
    /** @var AllowanceCharge[] */
    private array $allowanceCharges = [];
    /** @var InvItemAllowanceCharge[] */
    private array $invItemAllowanceCharges = [];
    /** @var InvAllowanceCharge[] */
    private array $invAllowanceCharges = [];

    private int $invId = 0;

    private int $invItemId = 0;

    private int $allowanceChargeId = 0;

    private const int DEFAULT_COUNT = 5;

    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly LoggerInterface $logger,
        private Generator $faker,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function configure(): void
    {
        $this
            ->setDescription('Add one invoice')
            ->setHelp('This command adds an invoice with randomly generated invoice items')
            ->addArgument('count', InputArgument::OPTIONAL, 'Count', self::DEFAULT_COUNT);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = (int) $input->getArgument('count');
        // get faker
        if (!class_exists(Factory::class)) {
            $io->error('Faker should be installed. Run `composer install --dev`');

            return ExitCode::UNSPECIFIED_ERROR;
        }
        $this->faker = Factory::create();

        try {
            /* Add minimal user information */
            $this->addUsers($count);
            $this->addClients($count);

            /* Assign between one and five clients to each user randomly */
            $this->addUserClients();

            /* Add more extensive user information */
            $this->addUserInvs($count);

            $this->addFamilies($count);
            $this->addTaxRates();
            $this->addUnits($count);
            /* Families and TaxRates are used to build the Product */
            $this->addProducts($count);
            /* Add 3 Groups - Order, Quote, Invoice */
            $this->addGroups();
            /* Create base AllowanceCharge entities */
            $this->addAllowanceCharges();
            /* Create one invoice with 5 invoice items with Summary Taxes at the end
             * and with the Summary Tax calculated by including invoice item tax
             */
            $this->addInv(5, true, true);
            (new EntityWriter($this->entityManager))->write($this->users);
        } catch (Throwable $t) {
            $io->error($t->getMessage());
            $this->logger->error($t->getMessage(), ['exception' => $t]);

            return (int) $t->getCode() ?: ExitCode::UNSPECIFIED_ERROR;
        }

        $renderer = new ItemsTableRenderer(
            $this->invTaxRates,
            $this->invAllowanceCharges,
            $this->allowanceCharges,
            $this->invItems,
            $this->invItemAllowanceCharges,
            $this->taxRates,
        );
        $summaryTableData = $renderer->renderInvItemTable($output);
        $renderer->renderSummaryTable($output, $summaryTableData);

        $io->success('Done');
        return ExitCode::OK;
    }

    private function addUsers(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $login = $this->faker->name;
            $email = $this->faker->email;
            $password = $this->faker->password;
            $user = new User($login, $email, $password);
            $this->users[] = $user;
        }
    }

    private function addClients(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $clientName = $this->faker->name;
            $age = $this->faker->numberBetween(18, 100);
            $client = new Client();
            $client->setClientFullName($clientName);
            $client->setClientAge($age);
            $this->clients[] = $client;
        }
    }

    private function addUserClients(): void
    {
        foreach ($this->users as $user) {
            $numberOfClients = $this->faker->numberBetween(1, 5);

            $clients = $this->faker->randomElements(
                $this->clients,
                $numberOfClients
            );

            /**
             * @var \App\Infrastructure\Persistence\Client\Client
             */
            foreach ($clients as $client) {
                $userClient = new UserClient();

                $userClient->setUserId($user->reqId());
                $userClient->setClientId($client->reqId());

                $this->userClients[] = $userClient;
            }
        }
    }

    private function addUserInvs(int $count): void
    {
        /**
         * UserInv is an extension table of User carrying more detailed data
         * of the user e.g. active
         * One UserInv can be responsible for paying off more than one Client
         * UserInv details relate to the 'user' that will pay off the Clients
         * Let us assume that each UserInv has only one Client to pay off.
         */
        for ($i = 0; $i < $count; $i++) {
            foreach ($this->users as $user) {
                $userInv = new UserInv();
                $userInv->setUserId($user->reqId());
                $userInv->setActive(true);
                $this->userInvs[] = $userInv;
            }
        }
    }

    private function addFamilies(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $familyName = $this->faker->name;
            $family = new Family();
            $family->setFamilyName($familyName);
            $this->families[] = $family;
        }
    }

    /* Create two basic tax rates */
    private function addTaxRates(): void
    {
        $taxRateName15 = 'Fifteen';
        $taxRatePercent15 = 15;
        $taxRate15 = new TaxRate();
        $taxRate15->setTaxRateId(1);
        $taxRate15->setTaxRateName($taxRateName15);
        $taxRate15->setTaxRatePercent($taxRatePercent15);
        $this->taxRates[] = $taxRate15;

        $taxRateName20 = 'Twenty';
        $taxRatePercent20 = 20;
        $taxRate20 = new TaxRate();
        $taxRate20->setTaxRateId(2);
        $taxRate20->setTaxRateName($taxRateName20);
        $taxRate20->setTaxRatePercent($taxRatePercent20);
        $this->taxRates[] = $taxRate20;
    }

    private function addUnits(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $inflector = InflectorFactory::create()->build();
            $unitName = $this->faker->name;
            $unitNamePlural = $inflector->pluralize($unitName);
            $unit = new Unit();
            $unit->setUnitName($unitName);
            $unit->setUnitNamePlrl($unitNamePlural);
            $this->units[] = $unit;
        }
    }

    private function addGroups(): void
    {
        $groups = ['Order', 'Quote', 'Invoice'];
        /**
         * @var string $value
         */
        foreach ($groups as $value) {
            $i_group = new Group();
            $i_group->setName($value . ' Group');
            $i_group->setIdentifierFormat(substr($value, 0, 2) . '{{{id}}}');
            $i_group->setNextId(1);
            $i_group->setLeftPad(0);
            $this->groups[] = $i_group;
        }
    }

    private function addProducts(int $count): void
    {
        for ($i = 1; $i < $count; $i++) {
            $product = new Product();
            $product->setProductName((string) $this->productNames[$i - 1]);
            $product->setProductPrice($this->faker->numberBetween(20, 30));
            $product->setPurchasePrice($this->faker->numberBetween(10, 19));
            $product->setFamilyId($i);
            $product->setTaxRateId(1);
            $product->setUnitId($i);
            $this->products[] = $product;
        }
    }

    private function addInv(
        int $count,
        bool $summaryTaxesExist = true,
        bool $includeItemTaxesInSummaryTaxSoApplyAfter = true,
    ): void {
        if (empty($this->users)) {
            throw new ItemsCommandException('No users');
        }
        if (empty($this->userClients)) {
            throw new ItemsCommandException('No clients have been associated with users');
        }
        if (empty($this->userInvs)) {
            throw new ItemsCommandException('No users that have been made active for invoicing!');
        }
        $this->inv = [];
        $this->invId = 1;
        $this->invItemId = 1;
        $inv = new Inv();
        $inv->setUserId($this->faker->numberBetween(1, $count));
        $inv->setGroupId(2);
        $inv->setClientId($this->faker->numberBetween(1, $count));
        $this->addInvItems($count);
        $this->addInvItemAllowanceCharges();
        $this->addInvAllowanceCharges();
        $invAmount = $this->addInvAmount($summaryTaxesExist, $includeItemTaxesInSummaryTaxSoApplyAfter);
        $this->invAmount[0] = $invAmount;
        $this->inv[] = $inv;
        $this->invId = +1;
    }

    private function addInvAmount(
        bool $summaryTaxesExist = true,
        bool $includeItemTaxInSummaryTaxSoApplyAfter = true,
    ): InvAmount {
        $itemSubTotal = 0;
        $itemTaxTotal = 0;
        $itemDiscountTotal = 0;
        $invAmount = new InvAmount();
        $invAmount->setInvId($this->invId);
        $invAmount->setSign(1);
        foreach ($this->invItemAmounts as $invItemAmount) {
            $itemSubTotal = $itemSubTotal + ($invItemAmount->getSubtotal() ?? 0.00);
            $itemDiscountTotal = $itemDiscountTotal + ($invItemAmount->getDiscount() ?? 0.00);
            $itemTaxTotal = $itemTaxTotal + ($invItemAmount->getTaxTotal() ?? 0.00);
        }

        // Add item-level allowances and charges
        $itemAllowanceChargeAmountTotal = 0;
        $itemAllowanceChargeTaxTotal = 0;
        foreach ($this->invItemAllowanceCharges as $invItemAllowanceCharge) {
            $allowanceChargeId = $invItemAllowanceCharge->reqAllowanceChargeId();
            $allowanceCharge = $this->allowanceCharges[$allowanceChargeId - 1] ?? null;
            $isCharge = $allowanceCharge?->getIdentifier() ?? false;
            $amount = (float)$invItemAllowanceCharge->getAmount();
            $tax = (float)$invItemAllowanceCharge->getVatOrTax();

            if ($isCharge) {
                $itemAllowanceChargeAmountTotal += $amount;
                $itemAllowanceChargeTaxTotal += $tax;
            } else {
                $itemAllowanceChargeAmountTotal -= $amount;
                $itemAllowanceChargeTaxTotal -= $tax;
            }
        }

        $itemAfterDiscount = $itemSubTotal - $itemDiscountTotal + $itemAllowanceChargeAmountTotal;
        $itemTaxTotalWithAllowanceCharges = $itemTaxTotal + $itemAllowanceChargeTaxTotal;
        $invAmount->setItemSubtotal($itemAfterDiscount);
        $invAmount->setItemTaxTotal($itemTaxTotalWithAllowanceCharges);

        // Call addInvTaxRates to populate invoice tax rates (returns void)
        if ($summaryTaxesExist) {
            $this->addInvTaxRates(
                $includeItemTaxInSummaryTaxSoApplyAfter ? $itemAfterDiscount : 0.00,
                $includeItemTaxInSummaryTaxSoApplyAfter ? $itemTaxTotalWithAllowanceCharges : 0.00,
            );
        }
        return $invAmount;
    }

    /* Create two Summary Table Invoice Tax Rates */
    private function addInvTaxRates(
        float $invAmountItemSubTotal,
        float $invAmountItemTaxTotal,
    ): void {
        /** Assume Invoice Taxes are applied to the 'With Item Tax' amount (item subtotal + item tax) **/
        $baseAmount = $invAmountItemSubTotal + $invAmountItemTaxTotal;

        $invTaxRateTwenty = new InvTaxRate();
        $invTaxRateTwenty->setInvId($this->invId);
        $invTaxRateTwenty->setTaxRateId(2);
        $invTaxRateAmountTwenty = $baseAmount * 0.2;
        $invTaxRateTwenty->setInvTaxRateAmount($invTaxRateAmountTwenty);
        $this->invTaxRates[] = $invTaxRateTwenty;

        $invTaxRateFifteen = new InvTaxRate();
        $invTaxRateFifteen->setInvId($this->invId);
        $invTaxRateFifteen->setTaxRateId(1);
        $invTaxRateAmountFifteen = $baseAmount * 0.15;
        $invTaxRateFifteen->setInvTaxRateAmount($invTaxRateAmountFifteen);
        $this->invTaxRates[] = $invTaxRateFifteen;
    }

    private function addInvItems(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $invItem = new InvItem();
            /** TaxRate 15%, TaxRate 20% **/
            $chosenTaxRateId = $this->faker->numberBetween(1, 2);
            $invItem->setTaxRateId($chosenTaxRateId);
            $invItem->setProductId($this->faker->numberBetween(1, $count));
            $invItem->setInvId($this->invId);
            $invItem->setName((string) $this->productNames[$i]);
            $quantity = $this->faker->numberBetween(1, 4);
            $invItem->setQuantity($quantity);
            $invItem->setDescription((string) $this->productDescriptions[$i]);
            $price = (float) $this->faker->numberBetween(1, 4);
            $invItem->setPrice($price);
            $invItem->setDiscountAmount(1);
            $invItemAmount = $this->addInvItemAmount($price, $quantity, $this->invItemId, $chosenTaxRateId);
            $this->invItems[] = $invItem;
            $this->invItemAmounts[] = $invItemAmount;
            $this->invItemId = +1;
        }
    }

    private function addInvItemAmount(float $price, float $quantity, int $invItemId, int $chosenTaxRateId): InvItemAmount
    {
        $invItemAmount = new InvItemAmount();
        $invItemAmount->setInvItemId($invItemId);
        $subTotal = $price * $quantity;
        $invItemAmount->setSubtotal($subTotal);
        $invItemAmount->setDiscount(1 * $quantity);
        $netDiscount = $subTotal - (1 * $quantity);
        $taxtotal = ($chosenTaxRateId == 1 ? $netDiscount * 15 / 100 : $netDiscount *  20 / 100);
        $invItemAmount->setTaxTotal($taxtotal);
        // Initial total without charges/allowances
        $invItemAmount->setTotal($netDiscount + $taxtotal);
        return $invItemAmount;
    }

    /**
     * Create base AllowanceCharge entities that can be referenced
     * These define the types of allowances/charges available
     */
    private function addAllowanceCharges(): void
    {
        $this->allowanceChargeId = 1;

        // Allowance (discount) - level 0 (Overall/Invoice level)
        $allowance = new AllowanceCharge();
        $allowance->setId($this->allowanceChargeId++);
        $allowance->setIdentifier(false); // false = allowance
        $allowance->setLevel(0); // 0 = Overall/Invoice level
        $allowance->setReasonCode('95');
        $allowance->setReason('Discount');
        $allowance->setMultiplierFactorNumeric(0);
        $allowance->setAmount(10);
        $allowance->setBaseAmount(0);
        $allowance->setTaxRateId(1);
        $this->allowanceCharges[] = $allowance;

        // Charge - Shipping (Invoice level)
        $shipping = new AllowanceCharge();
        $shipping->setId($this->allowanceChargeId++);
        $shipping->setIdentifier(true); // true = charge
        $shipping->setLevel(0); // 0 = Overall/Invoice level
        $shipping->setReasonCode('FC');
        $shipping->setReason('Shipping');
        $shipping->setMultiplierFactorNumeric(0);
        $shipping->setAmount(20);
        $shipping->setBaseAmount(0);
        $shipping->setTaxRateId(1);
        $this->allowanceCharges[] = $shipping;

        // Charge - Handling (Invoice level)
        $handling = new AllowanceCharge();
        $handling->setId($this->allowanceChargeId++);
        $handling->setIdentifier(true); // true = charge
        $handling->setLevel(0); // 0 = Overall/Invoice level
        $handling->setReasonCode('HD');
        $handling->setReason('Handling');
        $handling->setMultiplierFactorNumeric(0);
        $handling->setAmount(15);
        $handling->setBaseAmount(0);
        $handling->setTaxRateId(1);
        $this->allowanceCharges[] = $handling;

        // Item-level allowance
        $itemAllowance = new AllowanceCharge();
        $itemAllowance->setId($this->allowanceChargeId++);
        $itemAllowance->setIdentifier(false); // false = allowance
        $itemAllowance->setLevel(1); // 1 = InvoiceLine/Item level
        $itemAllowance->setReasonCode('95');
        $itemAllowance->setReason('Item Allowance');
        $itemAllowance->setMultiplierFactorNumeric(0);
        $itemAllowance->setAmount(2);
        $itemAllowance->setBaseAmount(0);
        $itemAllowance->setTaxRateId(1);
        $this->allowanceCharges[] = $itemAllowance;

        // Item-level charge
        $itemCharge = new AllowanceCharge();
        $itemCharge->setId($this->allowanceChargeId++);
        $itemCharge->setIdentifier(true); // true = charge
        $itemCharge->setLevel(1); // 1 = InvoiceLine/Item level
        $itemCharge->setReasonCode('AE');
        $itemCharge->setReason('Item Charge');
        $itemCharge->setMultiplierFactorNumeric(0);
        $itemCharge->setAmount(1);
        $itemCharge->setBaseAmount(0);
        $itemCharge->setTaxRateId(1);
        $this->allowanceCharges[] = $itemCharge;
    }

    /**
     * Add InvItemAllowanceCharge entities for each invoice item
     * These are item-level allowances/charges
     */
    private function addInvItemAllowanceCharges(): void
    {
        $itemLevelAllowanceChargeId = 4; // References the "Item Allowance" AllowanceCharge
        $itemLevelChargeId = 5; // References the "Item Charge" AllowanceCharge

        foreach (array_keys($this->invItems) as $index) {
            $invItemAmount = $this->invItemAmounts[(int) $index] ?? null;

            // Add an allowance (discount) to some items
            if (((int) $index) % 2 === 0) {
                $amount = (float) $this->faker->numberBetween(100, 300) / 100; // Random 1.00 to 3.00
                $taxRate = 0.15; // 15% tax rate for allowances
                $vatOrTax = $amount * $taxRate;

                $invItemAllowanceCharge = new InvItemAllowanceCharge();
                $invItemAllowanceCharge->setInvId($this->invId);
                $invItemAllowanceCharge->setInvItemId(((int) $index) + 1);
                $invItemAllowanceCharge->setAllowanceChargeId($itemLevelAllowanceChargeId);
                $invItemAllowanceCharge->setAmount($amount);
                $invItemAllowanceCharge->setVatOrTax($vatOrTax);
                $this->invItemAllowanceCharges[] = $invItemAllowanceCharge;

                // Update InvItemAmount allowance field and recalculate total
                if ($invItemAmount) {
                    $invItemAmount->setAllowance($amount);
                    // Recalculate total: current_total - allowance_amount - allowance_tax
                    $currentTotal = $invItemAmount->getTotal() ?? 0.00;
                    $invItemAmount->setTotal($currentTotal - $amount - $vatOrTax);
                }
            }

            // Add a charge (surcharge) to some items
            if (((int)$index) % 3 === 0) {
                $amount = (float) $this->faker->numberBetween(100, 200) / 100; // Random 1.00 to 2.00
                $taxRate = 0.15; // 15% tax rate for charges
                $vatOrTax = $amount * $taxRate;

                $invItemCharge = new InvItemAllowanceCharge();
                $invItemCharge->setInvId($this->invId);
                $invItemCharge->setInvItemId(((int)$index) + 1);
                $invItemCharge->setAllowanceChargeId($itemLevelChargeId);
                $invItemCharge->setAmount($amount);
                $invItemCharge->setVatOrTax($vatOrTax);
                $this->invItemAllowanceCharges[] = $invItemCharge;

                // Update InvItemAmount charge field and recalculate total
                if ($invItemAmount) {
                    $invItemAmount->setCharge($amount);
                    // Recalculate total: current_total + charge_amount + charge_tax
                    $currentTotal = $invItemAmount->getTotal() ?? 0.00;
                    $invItemAmount->setTotal($currentTotal + $amount + $vatOrTax);
                }
            }
        }
    }

    /**
     * Add InvAllowanceCharge entities at the invoice level
     * These are for shipping, handling, packaging, etc.
     * They appear between "With Item Tax" and "Invoice Taxes"
     */
    private function addInvAllowanceCharges(): void
    {
        // Add Shipping charge
        $shippingCharge = new InvAllowanceCharge();
        $shippingCharge->setInvId($this->invId);
        $shippingCharge->setAllowanceChargeId(2); // References "Shipping"
        $shippingCharge->setAmount(25.00);
        $shippingCharge->setVatOrTax(3.75);
        $this->invAllowanceCharges[] = $shippingCharge;

        // Add Handling charge
        $handlingCharge = new InvAllowanceCharge();
        $handlingCharge->setInvId($this->invId);
        $handlingCharge->setAllowanceChargeId(3); // References "Handling"
        $handlingCharge->setAmount(15.00);
        $handlingCharge->setVatOrTax(2.25);
        $this->invAllowanceCharges[] = $handlingCharge;

        // Add an invoice-level discount (allowance)
        $invoiceAllowance = new InvAllowanceCharge();
        $invoiceAllowance->setInvId($this->invId);
        $invoiceAllowance->setAllowanceChargeId(1); // References "Discount"
        $invoiceAllowance->setAmount(10.00);
        $invoiceAllowance->setVatOrTax(1.50);
        $this->invAllowanceCharges[] = $invoiceAllowance;
    }
}
