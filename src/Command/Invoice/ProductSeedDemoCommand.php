<?php

declare(strict_types=1);

namespace App\Command\Invoice;

use App\Infrastructure\Persistence\CategoryPrimary\CategoryPrimary;
use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Unit\Unit;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Product\ProductRepository;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Unit\UnitRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * A handful of real, priced demo products — deliberately small and
 * targeted, unlike `invoice/items` (which generates a whole unrelated
 * graph of fake users/clients/invoices just to get some products as a
 * side effect). Exists to have something for the `webshop` storefront's
 * `GET /api/products` feed to actually show — that endpoint only
 * exposes products with a real price
 * (`ProductRepository::findAllPreloadedWithPrice()`), so a product with
 * no price never appears there regardless of this command.
 *
 * Also files each demo product under a small CategoryPrimary/
 * CategorySecondary/Family taxonomy (category / subcategory / family)
 * — the same three-level structure `Family` already carries via its
 * `category_primary_id`/`category_secondary_id` columns for HomeCare
 * streets/runs, here repurposed to give the webshop's gallery something
 * real to build checkbox filters against.
 *
 * Reuses an existing CategoryPrimary/CategorySecondary/Family/TaxRate/
 * Unit if one is already on file rather than creating a duplicate every
 * run. Existing demo products have their category/family reassigned on
 * every run (rather than being skipped outright) so that re-running
 * after a taxonomy change here still fixes up rows seeded previously —
 * safe to run more than once either way.
 */
final class ProductSeedDemoCommand extends Command
{
    protected static string $defaultName = 'product/seed-demo';

    /**
     * @var list<array{name: string, sku: string, description: string, price: float,
     *     category: string, subcategory: string, family: string}>
     */
    private const array DEMO_PRODUCTS = [
        ['name' => 'Wireless Mouse', 'sku' => 'WS-MOUSE-01', 'description' => 'Ergonomic wireless mouse, USB receiver.', 'price' => 19.99, 'category' => 'Computing', 'subcategory' => 'Input Devices', 'family' => 'Input Devices'],
        ['name' => 'Mechanical Keyboard', 'sku' => 'WS-KEYB-01', 'description' => 'Tactile mechanical keyboard, UK layout.', 'price' => 54.50, 'category' => 'Computing', 'subcategory' => 'Input Devices', 'family' => 'Input Devices'],
        ['name' => '27" Monitor', 'sku' => 'WS-MON-27', 'description' => '27 inch 1440p IPS monitor.', 'price' => 189.00, 'category' => 'Computing', 'subcategory' => 'Displays', 'family' => 'Monitors'],
        ['name' => 'USB-C Hub', 'sku' => 'WS-HUB-01', 'description' => '7-in-1 USB-C hub, HDMI + card reader.', 'price' => 29.95, 'category' => 'Accessories', 'subcategory' => 'Connectivity', 'family' => 'Hubs & Adapters'],
        ['name' => '1TB External SSD', 'sku' => 'WS-SSD-1TB', 'description' => 'Portable USB 3.2 external SSD.', 'price' => 84.99, 'category' => 'Accessories', 'subcategory' => 'Storage', 'family' => 'Storage'],
    ];

    public function __construct(
        private readonly CategoryPrimaryRepository $categoryPrimaryRepository,
        private readonly CategorySecondaryRepository $categorySecondaryRepository,
        private readonly FamilyRepository $familyRepository,
        private readonly TaxRateRepository $taxRateRepository,
        private readonly UnitRepository $unitRepository,
        private readonly ProductRepository $productRepository,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function configure(): void
    {
        $this
            ->setDescription('Seed a handful of real, priced demo products')
            ->setHelp('Creates (or reuses) a CategoryPrimary/CategorySecondary/Family/TaxRate/Unit'
                . ' taxonomy, then a small set of demo products with real prices filed under it'
                . ' — enough for the webshop storefront\'s product feed and gallery filters to show'
                . ' something.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $taxRate = $this->resolveTaxRate();
        $unit = $this->resolveUnit();

        $created = 0;
        $updated = 0;
        foreach (self::DEMO_PRODUCTS as $data) {
            $family = $this->resolveFamily($data['family'], $data['category'], $data['subcategory']);
            $existing = $this->productRepository->withName($data['name']);

            if ($existing !== null) {
                $existing->setFamily($family);
                $this->productRepository->save($existing);
                $updated++;
                $io->writeln('Updated category for: ' . $data['name']);
                continue;
            }

            $product = new Product();
            $product->setProductName($data['name']);
            $product->setProductSku($data['sku']);
            $product->setProductDescription($data['description']);
            $product->setProductPrice($data['price']);
            $product->setFamily($family);
            $product->setTaxrate($taxRate);
            $product->setUnit($unit);
            $product->setTrackStock(true);
            $product->setStockQuantity(50.00);
            $this->productRepository->save($product);
            $created++;
            $io->writeln('Created: ' . $data['name']);
        }

        $io->success($created . ' product(s) created, ' . $updated . ' product(s) re-categorized.');
        return ExitCode::OK;
    }

    private function resolveCategoryPrimary(string $name): CategoryPrimary
    {
        /** @var CategoryPrimary $categoryPrimary */
        foreach ($this->categoryPrimaryRepository->findAllPreloaded() as $categoryPrimary) {
            if ($categoryPrimary->getName() === $name) {
                return $categoryPrimary;
            }
        }

        $categoryPrimary = new CategoryPrimary(name: $name);
        $this->categoryPrimaryRepository->save($categoryPrimary);
        return $categoryPrimary;
    }

    private function resolveCategorySecondary(string $name, CategoryPrimary $categoryPrimary): CategorySecondary
    {
        /** @var CategorySecondary $categorySecondary */
        foreach ($this->categorySecondaryRepository->repoCategoryPrimaryIdQuery($categoryPrimary->reqId()) as $categorySecondary) {
            if ($categorySecondary->getName() === $name) {
                return $categorySecondary;
            }
        }

        // Both the scalar FK and the object relation are set — a plain
        // setCategoryPrimaryId() alone leaves the nullable BelongsTo
        // relation null at flush time (see project convention on Cycle
        // ORM BelongsTo relations needing the object, not just the FK
        // scalar).
        $categorySecondary = new CategorySecondary(category_primary_id: $categoryPrimary->reqId(), name: $name);
        $categorySecondary->setCategoryPrimary($categoryPrimary);
        $this->categorySecondaryRepository->save($categorySecondary);
        return $categorySecondary;
    }

    private function resolveFamily(string $familyName, string $categoryName, string $subcategoryName): Family
    {
        $categoryPrimary = $this->resolveCategoryPrimary($categoryName);
        $categorySecondary = $this->resolveCategorySecondary($subcategoryName, $categoryPrimary);

        $existing = $this->familyRepository->repoFamilyByNameAndSecondaryCategoryQuery(
            $familyName,
            $categorySecondary->reqId(),
        );
        if ($existing !== null) {
            return $existing;
        }

        $family = new Family(
            family_name: $familyName,
            category_primary_id: $categoryPrimary->reqId(),
            category_secondary_id: $categorySecondary->reqId(),
        );
        $this->familyRepository->save($family);
        return $family;
    }

    private function resolveTaxRate(): TaxRate
    {
        $existing = $this->taxRateRepository->repoFirstByIdQuery();
        if ($existing !== null) {
            return $existing;
        }

        $taxRate = new TaxRate(tax_rate_name: 'Standard', tax_rate_percent: 20.00);
        $this->taxRateRepository->save($taxRate);
        return $taxRate;
    }

    private function resolveUnit(): Unit
    {
        $existing = $this->unitRepository->repoFirstByIdQuery();
        if ($existing !== null) {
            return $existing;
        }

        $unit = new Unit(unit_name: 'each', unit_name_plrl: 'each');
        $this->unitRepository->save($unit);
        return $unit;
    }
}
