<?php

declare(strict_types=1);

namespace App\Command\Invoice;

use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Unit\Unit;
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
 * Reuses an existing Family/TaxRate/Unit if one is already on file
 * rather than creating a duplicate every run — safe to run more than
 * once.
 */
final class ProductSeedDemoCommand extends Command
{
    protected static string $defaultName = 'product/seed-demo';

    /**
     * @var list<array{name: string, sku: string, description: string, price: float}>
     */
    private const array DEMO_PRODUCTS = [
        ['name' => 'Wireless Mouse', 'sku' => 'WS-MOUSE-01', 'description' => 'Ergonomic wireless mouse, USB receiver.', 'price' => 19.99],
        ['name' => 'Mechanical Keyboard', 'sku' => 'WS-KEYB-01', 'description' => 'Tactile mechanical keyboard, UK layout.', 'price' => 54.50],
        ['name' => '27" Monitor', 'sku' => 'WS-MON-27', 'description' => '27 inch 1440p IPS monitor.', 'price' => 189.00],
        ['name' => 'USB-C Hub', 'sku' => 'WS-HUB-01', 'description' => '7-in-1 USB-C hub, HDMI + card reader.', 'price' => 29.95],
        ['name' => '1TB External SSD', 'sku' => 'WS-SSD-1TB', 'description' => 'Portable USB 3.2 external SSD.', 'price' => 84.99],
    ];

    public function __construct(
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
            ->setHelp('Creates (or reuses) a Family/TaxRate/Unit, then a small'
                . ' set of demo products with real prices — enough for the'
                . ' webshop storefront\'s product feed to show something.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $family = $this->resolveFamily();
        $taxRate = $this->resolveTaxRate();
        $unit = $this->resolveUnit();

        $created = 0;
        foreach (self::DEMO_PRODUCTS as $data) {
            if ($this->productRepository->withName($data['name']) !== null) {
                $io->writeln('Skipped (already exists): ' . $data['name']);
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

        $io->success($created . ' product(s) created.');
        return ExitCode::OK;
    }

    private function resolveFamily(): Family
    {
        $existing = $this->familyRepository->withName('Webshop Demo');
        if ($existing !== null) {
            return $existing;
        }

        $family = new Family(family_name: 'Webshop Demo');
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
