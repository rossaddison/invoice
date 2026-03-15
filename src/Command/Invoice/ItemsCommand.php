<?php

declare(strict_types=1);

namespace App\Command\Invoice;

use App\Invoice\Entity\AllowanceCharge;
use App\Invoice\Entity\Client;
use App\Invoice\Entity\Group;
use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvAllowanceCharge;
use App\Invoice\Entity\InvAmount;
use App\Invoice\Entity\InvItem;
use App\Invoice\Entity\InvItemAllowanceCharge;
use App\Invoice\Entity\InvItemAmount;
use App\Invoice\Entity\InvTaxRate;
use App\Invoice\Entity\Family;
use App\Invoice\Entity\Product;
use App\Invoice\Entity\TaxRate;
use App\Invoice\Entity\Unit;
use App\Invoice\Entity\UserClient;
use App\Invoice\Entity\UserInv;
use App\User\User;
use Cycle\ORM\EntityManager;
use Doctrine\Inflector\InflectorFactory;
use Exception;
use Faker\Factory;
use Faker\Generator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
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
    private array $productNames = ['Mouse', 'Keyboard', 'Screen', 'Hard drive', 'Box', 'Motherboard'];
    private array $productDescriptions = ['3-button', 'US', '24inch x 16inch', '1 TB', 'Standard', 'Intel i15'];
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

            /* Assign one client to each user although more than one client can be assigned to each user */
            $this->addUserClients($count);

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
            $this->saveEntities();
        } catch (Throwable $t) {
            $io->error($t->getMessage());
            $this->logger->error($t->getMessage(), ['exception' => $t]);

            return (int) $t->getCode() ?: ExitCode::UNSPECIFIED_ERROR;
        }

        $summaryTableData = $this->renderInvItemTable($output);
        $this->renderSummaryTable($output, $summaryTableData);

        $io->success('Done');
        return ExitCode::OK;
    }

    private function renderSummaryTable(OutputInterface $output, array $summaryTableData): void
    {
        $table = new Table($output);
        $table->addRow([
            'After Item Discount',
            "\033[32m" . $this->format((float) $summaryTableData['After Discount']) . "\033[0m",
        ]);
        $table->addRow([
            'Add: Item Tax Total',
            "\033[32m" . $this->format((float) $summaryTableData['Item Tax']) . "\033[0m",
        ]);
        $table->addRow(new TableSeparator());
        $withItemTax = (float) $summaryTableData['After Discount'] + (float) $summaryTableData['Item Tax'];
        $table->addRow([
            'With Item Tax',
            "\033[32m" . $this->format($withItemTax) . "\033[0m",
        ]);
        
        // Display Invoice Taxes right after 'With Item Tax'
        $table->addRow(new TableSeparator());
        $totalInvTaxRate = 0;
        $taxRates = $this->invTaxRates;
        $firstRate = $taxRates[0];
        $secondRate = $taxRates[1];
        /* Assume the two InvTaxRates apply tax to the 'With Item Tax' amount */
        foreach ($taxRates as $invTaxRate) {
            $totalInvTaxRate = $totalInvTaxRate + ($invTaxRate->getInv_tax_rate_amount() ?? 0.00);
        }
        $table->addRow([
            'Invoice Taxes (20% ' . $this->format($firstRate->getInv_tax_rate_amount() ?? 0.00) . ', 15% ' . $this->format($secondRate->getInv_tax_rate_amount() ?? 0.00) . ')',
            $this->format($totalInvTaxRate),
        ]);
        
        // Add InvAllowanceCharge section (shipping, handling, etc.)
        $table->addRow(new TableSeparator());
        $invAllowanceChargeTotal = 0;
        foreach ($this->invAllowanceCharges as $invAllowanceCharge) {
            $amount = (float) $invAllowanceCharge->getAmount();
            $offSet = ((int) $invAllowanceCharge->getAllowance_charge_id()) - 1;
            $allowanceCharge = $this->allowanceCharges[$offSet] ?? null;
            $isCharge = $allowanceCharge?->getIdentifier() ?? false;
            $reason = $allowanceCharge?->getReason() ?? 'N/A';
            
            if ($isCharge) {
                $table->addRow([
                    'Add: ' . $reason . ' (charge)',
                    "\033[32m" . $this->format($amount) . "\033[0m",
                ]);
                $invAllowanceChargeTotal += $amount;
            } else {
                $table->addRow([
                    'Less: ' . $reason . ' (allowance)',
                    "\033[31m" . $this->formatBracketed($amount) . "\033[0m",
                ]);
                $invAllowanceChargeTotal -= $amount;
            }
        }
        $afterAllowanceCharge = $withItemTax + $totalInvTaxRate + $invAllowanceChargeTotal;
        $beforeDiscountTotal = $afterAllowanceCharge;
        $table->addRow(new TableSeparator());
        $table->addRow([
            'Before Invoice Discount Total',
            $this->format($beforeDiscountTotal),
        ]);
        $discount = 0.10 * $beforeDiscountTotal;
        $table->addRow([
            '(Invoice Discount as 10% of Before Discount Total)',
            "\033[31m" . $this->formatBracketed($discount) . "\033[0m",
        ]);
        $total = $beforeDiscountTotal - $discount;
        $table->addRow(new TableSeparator());
        $table->addRow([
            'Total',
            $this->format($total),
        ]);
        $table->render();
    }

    private function format(float $number): string
    {
        $formatted_number = sprintf('%.2f', $number);
        return $aligned_number = str_pad($formatted_number, 10, ' ', STR_PAD_LEFT);
    }

    private function formatBracketed(float $number): string
    {
        $formatted_number = sprintf('%.2f', -abs($number));
        return str_pad($formatted_number, 10, ' ', STR_PAD_LEFT);
    }

    private function renderInvItemTable(OutputInterface $output): array
    {
        $table = new Table($output);
        $invItems = $this->invItems;
        $table->setHeaders(
            [
                'Name',
                'Desc',
                'Qty',
                'P/U',
                '(D/U)',
                'Subtot',
                '(ESD)',
                'After Disc',
                'Tax %',
                'Tax',
                'Total',
            ],
        );

        $itemCount = count($invItems);
        $currentIndex = 0;
        $discountedSubTotal = 0;
        $itemTaxTotal = 0;
        $grandTotal = 0;
        foreach ($invItems as $invItem) {
            $quantity = $invItem->getQuantity() ?? 0;
            $price = $invItem->getPrice() ?? 0;
            $percentage = $invItem->getTax_rate_id() == '1' ? 15 : 20;
            $subTotal = $quantity * $price;
            $discount = 1;
            $netDiscount = ($quantity * ($price - $discount));
            $totalDiscount = $quantity * $discount;
            $itemTax = $netDiscount * ($percentage / 100);
            $itemTotal = $netDiscount + $itemTax;
            
            // Check if this item has any allowances or charges
            $itemId = $currentIndex + 1;
            $hasAllowancesOrCharges = false;
            foreach ($this->invItemAllowanceCharges as $invItemAllowanceCharge) {
                if ($invItemAllowanceCharge->getInv_item_id() == (string)$itemId) {
                    $hasAllowancesOrCharges = true;
                    break;
                }
            }
            
            // Use purple color for items without allowances/charges
            $colorCode = $hasAllowancesOrCharges ? '' : "\033[35m";
            $colorEnd = $hasAllowancesOrCharges ? '' : "\033[0m";
            
            $table->addRow(
                [
                    $colorCode . ($invItem->getName() ?? 'None Available') . $colorEnd,
                    $colorCode . ($invItem->getDescription() ?? 'None Available') . $colorEnd,
                    $colorCode . (float) $quantity . $colorEnd,
                    $colorCode . $this->format((float) $price) . $colorEnd,
                    $colorCode . $this->format($discount) . $colorEnd,
                    $colorCode . $this->format($subTotal) . $colorEnd,
                    "\033[31m" . $this->formatBracketed($totalDiscount) . "\033[0m",
                    $colorCode . $this->format($subTotal - $totalDiscount) . $colorEnd,
                    $colorCode . $this->format($percentage) . $colorEnd,
                    $colorCode . $this->format($itemTax) . $colorEnd,
                    $colorCode . $this->format($itemTotal) . $colorEnd,
                ],
            );

            // Display InvItemAllowanceCharge entries for this item and calculate subtotals
            $itemAllowanceAmount = 0;
            $itemAllowanceTax = 0;
            $itemChargeAmount = 0;
            $itemChargeTax = 0;
            
            foreach ($this->invItemAllowanceCharges as $invItemAllowanceCharge) {
                if ($invItemAllowanceCharge->getInv_item_id() == (string)$itemId) {
                    $allowanceChargeId = (int)$invItemAllowanceCharge->getAllowance_charge_id();
                    $allowanceCharge = $this->allowanceCharges[$allowanceChargeId - 1] ?? null;
                    $isCharge = $allowanceCharge?->getIdentifier() ?? false;
                    $reason = $allowanceCharge?->getReason() ?? 'N/A';
                    $amount = (float)$invItemAllowanceCharge->getAmount();
                    $vatOrTax = (float)$invItemAllowanceCharge->getVatOrTax();
                    
                    // Get the tax rate percentage from the AllowanceCharge entity
                    $taxRateId = (int)($allowanceCharge?->getTaxRateId() ?? 1);
                    $taxRatePercent = 0;
                    foreach ($this->taxRates as $taxRate) {
                        if ((int)$taxRate->getTaxRateId() === $taxRateId) {
                            $taxRatePercent = $taxRate->getTaxRatePercent();
                            break;
                        }
                    }
                    
                    if ($isCharge) {
                        $itemChargeAmount += $amount;
                        $itemChargeTax += $vatOrTax;
                        $chargeTotal = $amount + $vatOrTax;
                        $table->addRow([
                            '  → ' . $reason,
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            "\033[32m" . $this->format($amount) . "\033[0m",
                            $this->format($taxRatePercent ?? 0.00),
                            $this->format($vatOrTax),
                            "\033[32m" . $this->format($chargeTotal) . "\033[0m",
                        ]);
                    } else {
                        $itemAllowanceAmount += $amount;
                        $itemAllowanceTax += $vatOrTax;
                        $allowanceTotal = $amount + $vatOrTax;
                        $table->addRow([
                            '  → ' . $reason,
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            "\033[31m" . $this->formatBracketed($amount) . "\033[0m",
                            $this->format($taxRatePercent ?? 0.00),
                            "\033[31m" . $this->formatBracketed($vatOrTax) . "\033[0m",
                            "\033[31m" . $this->formatBracketed($allowanceTotal) . "\033[0m",
                        ]);
                    }
                }
            }
            
            // Add subtotal row if there are any allowances or charges
            if ($itemAllowanceAmount > 0 || $itemChargeAmount > 0) {
                $subtotalTax = $itemTax + $itemChargeTax - $itemAllowanceTax;
                $subtotalAmount = $itemTotal + ($itemChargeAmount + $itemChargeTax) - ($itemAllowanceAmount + $itemAllowanceTax);
                
                $table->addRow([
                    '  Subtotal',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    "\033[35m" . $this->format($subtotalTax) . "\033[0m",
                    "\033[35m" . $this->format($subtotalAmount) . "\033[0m",
                ]);
                
                // Use the subtotal for grand total calculation
                $grandTotal += $subtotalAmount;
            } else {
                // Use the base item total for items without allowances/charges
                $grandTotal += $itemTotal;
            }

            if ($currentIndex < $itemCount - 1) {
                $table->addRow(new TableSeparator());
            }

            $currentIndex++;

            /* Build Summary Totals - After Discount includes base items and allowance/charge amounts (excluding tax) */
            $discountedSubTotal += $netDiscount + $itemChargeAmount - $itemAllowanceAmount;
            $itemTaxTotal += $itemTax + $itemChargeTax - $itemAllowanceTax;
        }
        $table->addRow(new TableSeparator());
        $table->addRow(
            [
                '','','','','','','',
                "\033[32m" . $this->format($discountedSubTotal) . "\033[0m",
                '',
                "\033[32m" . $this->format($itemTaxTotal) . "\033[0m",
                "\033[32m" . $this->format($grandTotal) . "\033[0m",
            ],
        );

        $table->setColumnMaxWidth(2, 15);
        $table->setColumnMaxWidth(3, 15);
        $table->render();
        $output->writeln('');
        $output->writeln('Note: ESD = Early Settlement Discount');
        return [
            'After Discount' => $discountedSubTotal,
            'Item Tax' => $itemTaxTotal,
        ];
    }

    private function saveEntities(): void
    {
        (new EntityWriter($this->entityManager))->write($this->users);
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
            $client->setClient_full_name($clientName);
            $client->setClient_age($age);
            $this->clients[] = $client;
        }
    }

    private function addUserClients(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            foreach ($this->users as $user) {
                $userClient = new UserClient();
                $userClient->setUser_id($this->faker->numberBetween(1, $count));
                $userClient->setClient_id($this->faker->numberBetween(1, $count));
                $this->userClients[] = $userClient;
            }
        }
    }

    private function addUserInvs(int $count): void
    {
        /**
         * UserInv is an extension table of User carrying more detailed data of the user e.g. active
         * One UserInv can be responsible for paying off more than one Client
         * UserInv details relate to the 'user' that will pay off the Clients
         * Let us assume that each UserInv has only one Client to pay off.
         */
        for ($i = 0; $i < $count; $i++) {
            foreach ($this->users as $user) {
                $userInv = new UserInv();
                $userInv->setUser_id($this->faker->numberBetween(1, $count));
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
            $family->setFamily_name($familyName);
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
            $unit->setUnit_name($unitName);
            $unit->setUnit_name_plrl($unitNamePlural);
            $this->units[] = $unit;
        }
    }

    private function addGroups(): void
    {
        $groups = ['Order', 'Quote', 'Invoice'];
        /**
         * @var int $key
         * @var string $value
         */
        foreach ($groups as $key => $value) {
            $i_group = new Group();
            $i_group->setName($value . ' Group');
            $i_group->setIdentifier_format(substr($value, 0, 2) . '{{{id}}}');
            $i_group->setNext_id(1);
            $i_group->setLeft_pad(0);
            $this->groups[] = $i_group;
        }
    }

    private function addProducts(int $count): void
    {
        for ($i = 1; $i < $count; $i++) {
            $product = new Product();
            $product->setProduct_name((string) $this->productNames[$i - 1]);
            $product->setProduct_price($this->faker->numberBetween(20, 30));
            $product->setPurchase_price($this->faker->numberBetween(10, 19));
            $product->setFamily_id($i);
            $product->setTax_rate_id(1);
            $product->setUnit_id($i);
            $this->products[] = $product;
        }
    }

    private function addInv(
        int $count,
        bool $summaryTaxesExist = true,
        bool $includeItemTaxesInSummaryTaxSoApplyAfter = true,
    ): void {
        if (empty($this->users)) {
            throw new Exception('No users');
        }
        if (empty($this->userClients)) {
            throw new Exception('No clients have been associated with users');
        }
        if (empty($this->userInvs)) {
            throw new Exception('No users that have been made active for invoicing!');
        }
        $this->inv = [];
        $this->invId = 1;
        $this->invItemId = 1;
        $inv = new Inv();
        $inv->setUser_id($this->faker->numberBetween(1, $count));
        $inv->setGroup_id(2);
        $inv->setClient_id($this->faker->numberBetween(1, $count));
        $this->addInvItems($count);
        $this->addInvItemAllowanceCharges();
        $this->addInvAllowanceCharges();
        $invAmount = $this->addInvAmount($inv, $summaryTaxesExist, $includeItemTaxesInSummaryTaxSoApplyAfter);
        $this->invAmount[0] = $invAmount;
        $this->inv[] = $inv;
        $this->invId = +1;
    }

    private function addInvAmount(
        Inv $inv,
        bool $summaryTaxesExist = true,
        bool $includeItemTaxInSummaryTaxSoApplyAfter = true,
    ): InvAmount {
        $itemSubTotal = 0;
        $itemTaxTotal = 0;
        $itemDiscountTotal = 0;
        $invAmount = new InvAmount();
        $invAmount->setInv_id($this->invId);
        $invAmount->setSign(1);
        foreach ($this->invItemAmounts as $invItemAmount) {
            $itemSubTotal = $itemSubTotal + ($invItemAmount->getSubtotal() ?? 0.00);
            $itemDiscountTotal = $itemDiscountTotal + ($invItemAmount->getDiscount() ?? 0.00);
            $itemTaxTotal = $itemTaxTotal + ($invItemAmount->getTax_total() ?? 0.00);
        }
        
        // Add item-level allowances and charges
        $itemAllowanceChargeAmountTotal = 0;
        $itemAllowanceChargeTaxTotal = 0;
        foreach ($this->invItemAllowanceCharges as $invItemAllowanceCharge) {
            $allowanceChargeId = (int)$invItemAllowanceCharge->getAllowance_charge_id();
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
        $invAmount->setItem_subtotal($itemAfterDiscount);
        $invAmount->setItem_tax_total($itemTaxTotalWithAllowanceCharges);
        
        // Calculate invoice-level allowances and charges total
        $invAllowanceChargeTotal = 0;
        foreach ($this->invAllowanceCharges as $invAllowanceCharge) {
            $amount = (float) $invAllowanceCharge->getAmount();
            $offSet = ((int) $invAllowanceCharge->getAllowance_charge_id()) - 1; 
            $allowanceCharge = $this->allowanceCharges[$offSet] ?? null;
            $isCharge = $allowanceCharge?->getIdentifier() ?? false;
            if ($isCharge) {
                $invAllowanceChargeTotal += $amount;
            } else {
                $invAllowanceChargeTotal -= $amount;
            }
        }
        
        // Assume setInclude_item_tax is true in the calculation of additional InvTaxRates
        $invTaxRateTotal = $summaryTaxesExist ? $this->addInvTaxRates(
            $inv,
            $includeItemTaxInSummaryTaxSoApplyAfter ? $itemAfterDiscount : 0.00,
            $includeItemTaxInSummaryTaxSoApplyAfter ? $itemTaxTotalWithAllowanceCharges : 0.00,
        ) : 0.00;
        return $invAmount;
    }

    /* Create two Summary Table Invoice Tax Rates */
    private function addInvTaxRates(
        Inv $inv,
        float $invAmountItemSubTotal,
        float $invAmountItemTaxTotal,
    ): void {
        /** Assume Invoice Taxes are applied to the 'With Item Tax' amount (item subtotal + item tax) **/
        $baseAmount = $invAmountItemSubTotal + $invAmountItemTaxTotal;
        
        $invTaxRateTwenty = new InvTaxRate();
        $invTaxRateTwenty->setInv_id($this->invId);
        $invTaxRateTwenty->setTax_rate_id(2);
        $invTaxRateAmountTwenty = $baseAmount * 0.2;
        $invTaxRateTwenty->setInv_tax_rate_amount($invTaxRateAmountTwenty);
        $this->invTaxRates[] = $invTaxRateTwenty;

        $invTaxRateFifteen = new InvTaxRate();
        $invTaxRateFifteen->setInv_id($this->invId);
        $invTaxRateFifteen->setTax_rate_id(1);
        $invTaxRateAmountFifteen = $baseAmount * 0.15;
        $invTaxRateFifteen->setInv_tax_rate_amount($invTaxRateAmountFifteen);
        $this->invTaxRates[] = $invTaxRateFifteen;
    }

    private function addInvItems(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $invItemAmount = 0;
            $invItem = new InvItem();
            /** TaxRate 15%, TaxRate 20% **/
            $chosenTaxRateId = $this->faker->numberBetween(1, 2);
            $invItem->setTax_rate_id($chosenTaxRateId);
            $invItem->setProduct_id($this->faker->numberBetween(1, $count));
            $invItem->setInv_id($this->invId);
            $invItem->setName((string) $this->productNames[$i]);
            $quantity = $this->faker->numberBetween(1, 4);
            $invItem->setQuantity($quantity);
            $invItem->setDescription((string) $this->productDescriptions[$i]);
            $price = (float) $this->faker->numberBetween(1, 4);
            $invItem->setPrice($price);
            $invItem->setDiscount_amount(1);
            $invItemAmount = $this->addInvItemAmount($invItem, $price, $quantity, $this->invItemId, $chosenTaxRateId);
            $this->invItems[] = $invItem;
            $this->invItemAmounts[] = $invItemAmount;
            $this->invItemId = +1;
        }
    }

    private function addInvItemAmount(InvItem $invItem, float $price, float $quantity, int $invItemId, int $chosenTaxRateId): InvItemAmount
    {
        $invItemAmount = new InvItemAmount();
        $invItemAmount->setInv_item_id($invItemId);
        $subTotal = $price * $quantity;
        $invItemAmount->setSubtotal($subTotal);
        $invItemAmount->setDiscount(1 * $quantity);
        $netDiscount = $subTotal - (1 * $quantity);
        $taxtotal = ($chosenTaxRateId == 1 ? $netDiscount * 15 / 100 : $netDiscount *  20 / 100);
        $invItemAmount->setTax_total($taxtotal);
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
        
        foreach ($this->invItems as $index => $invItem) {
            $invItemAmount = $this->invItemAmounts[(int) $index] ?? null;
            
            // Add an allowance (discount) to some items
            if (((int) $index) % 2 === 0) {
                $amount = (float) $this->faker->numberBetween(100, 300) / 100; // Random 1.00 to 3.00
                $taxRate = 0.15; // 15% tax rate for allowances
                $vatOrTax = $amount * $taxRate;
                
                $invItemAllowanceCharge = new InvItemAllowanceCharge();
                $invItemAllowanceCharge->setInv_id($this->invId);
                $invItemAllowanceCharge->setInv_item_id(((int) $index) + 1);
                $invItemAllowanceCharge->setAllowance_charge_id($itemLevelAllowanceChargeId);
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
                $invItemCharge->setInv_id($this->invId);
                $invItemCharge->setInv_item_id(((int)$index) + 1);
                $invItemCharge->setAllowance_charge_id($itemLevelChargeId);
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
        $shippingCharge->setInv_id($this->invId);
        $shippingCharge->setAllowance_charge_id(2); // References "Shipping"
        $shippingCharge->setAmount(25.00);
        $shippingCharge->setVatOrTax(3.75);
        $this->invAllowanceCharges[] = $shippingCharge;

        // Add Handling charge
        $handlingCharge = new InvAllowanceCharge();
        $handlingCharge->setInv_id($this->invId);
        $handlingCharge->setAllowance_charge_id(3); // References "Handling"
        $handlingCharge->setAmount(15.00);
        $handlingCharge->setVatOrTax(2.25);
        $this->invAllowanceCharges[] = $handlingCharge;

        // Add an invoice-level discount (allowance)
        $invoiceAllowance = new InvAllowanceCharge();
        $invoiceAllowance->setInv_id($this->invId);
        $invoiceAllowance->setAllowance_charge_id(1); // References "Discount"
        $invoiceAllowance->setAmount(10.00);
        $invoiceAllowance->setVatOrTax(1.50);
        $this->invAllowanceCharges[] = $invoiceAllowance;
    }
}
