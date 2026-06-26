<?php

declare(strict_types=1);

namespace App\Command\Invoice;

use App\Infrastructure\Persistence\{
   Client\Client, ClientCustom\ClientCustom, ClientNote\ClientNote,
   ClientPeppol\ClientPeppol, Company\Company, CompanyPrivate\CompanyPrivate,
   Contract\Contract, CustomField\CustomField, CustomValue\CustomValue,
   Delivery\Delivery, DeliveryLocation\DeliveryLocation,
   DeliveryParty\DeliveryParty, EmailTemplate\EmailTemplate,
   Family\Family, FromDropDown\FromDropDown, Group\Group,
   PostalAddress\PostalAddress, ProductCustom\ProductCustom,
   Product\Product, ProductImage\ProductImage, ProductProperty\ProductProperty,
   Profile\Profile, Project\Project, Task\Task, TaxRate\TaxRate, Unit\Unit,
   UnitPeppol\UnitPeppol, Upload\Upload
};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Cycle\Command\CycleDependencyProxy;

final class NonUserRelatedTruncate4Command extends Command
{
    protected static string $defaultName = 'invoice/nonuserrelated/truncate4';

    public function __construct(
        private readonly CycleDependencyProxy $promise,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function configure(): void
    {
        $this
            ->setDescription('Truncates, i.e removes all records, in the tables not related to the user.')
            ->setHelp('product_custom, product_image, product_property, product, task, tax_rate, unit_peppol, unit, family, group, '
                    . 'profile, company_private, company, client_note, contract, email_template, from_drop_down, delivery_party, '
                    . 'delivery, delivery_location, project, upload, postal_address, client_custom, client_peppol, client tables will be truncated until there are no records left in them.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** Note tables must be truncated in this sequence in order to avoid integrity constraint violations **/

        $io = new SymfonyStyle($input, $output);

        $tables = [
            'product_custom', 'product_image', 'product_property', 'product', 'task', 'tax_rate', 'unit_peppol', 'unit', 'family', 'group',
            'profile', 'company_private', 'company', 'client_note', 'contract', 'email_template', 'from_drop_down', 'delivery_party', 'delivery', 'delivery_location',
            'project', 'upload', 'postal_address', 'client_custom', 'client_peppol', 'client',
            'custom_value', 'custom_field',
        ];

        foreach ($tables as $table) {
            $this->promise
                ->getDatabaseProvider()
                ->database()
                ->delete($table)
                ->run();
        }

        if ($this->countRemainingRecords() === 0) {
            $io->success('Done');
            return ExitCode::OK;
        }
        $io->error('Unspecified error');
        return ExitCode::UNSPECIFIED_ERROR;
    }

    /** @param class-string $class */
    private function repoCount(string $class): int
    {
        $findAll = $this->promise->getORM()->getRepository($class)->findAll();
        return count(is_array($findAll) ? $findAll : iterator_to_array($findAll));
    }

    private function countRemainingRecords(): int
    {
        $classes = [
            ProductCustom::class, ProductImage::class, ProductProperty::class,
            Product::class, Task::class, TaxRate::class, UnitPeppol::class,
            Unit::class, Family::class, Group::class,
            Profile::class, CompanyPrivate::class, Company::class,
            ClientNote::class, Contract::class, EmailTemplate::class,
            FromDropDown::class, DeliveryParty::class, Delivery::class,
            DeliveryLocation::class, Project::class, Upload::class,
            PostalAddress::class, ClientCustom::class, ClientPeppol::class,
            Client::class, CustomValue::class, CustomField::class,
        ];
        $total = 0;
        foreach ($classes as $class) {
            $total += $this->repoCount($class);
        }
        return $total;
    }
}
