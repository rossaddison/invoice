<?php

declare(strict_types=1);

namespace App\Command\Invoice;

use App\Invoice\Entity\Client;
use App\Invoice\Entity\ClientCustom;
use App\Invoice\Entity\ClientNote;
use App\Invoice\Entity\ClientPeppol;
use App\Invoice\Entity\Company;
use App\Invoice\Entity\CompanyPrivate;
use App\Invoice\Entity\Contract;
use App\Invoice\Entity\CustomField;
use App\Invoice\Entity\CustomValue;
use App\Invoice\Entity\Delivery;
use App\Invoice\Entity\DeliveryLocation;
use App\Invoice\Entity\DeliveryParty;
use App\Invoice\Entity\EmailTemplate;
use App\Invoice\Entity\Family;
use App\Invoice\Entity\FromDropDown;
use App\Invoice\Entity\Group;
use App\Invoice\Entity\PostalAddress;
use App\Invoice\Entity\ProductCustom;
use App\Invoice\Entity\Product;
use App\Invoice\Entity\ProductImage;
use App\Invoice\Entity\ProductProperty;
use App\Invoice\Entity\Profile;
use App\Invoice\Entity\Project;
use App\Invoice\Entity\Sumex;
use App\Invoice\Entity\Task;
use App\Invoice\Entity\TaxRate;
use App\Invoice\Entity\Unit;
use App\Invoice\Entity\UnitPeppol;
use App\Invoice\Entity\Upload;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Cycle\Command\CycleDependencyProxy;

final class NonUserRelatedTruncate4Command extends Command
{
    protected static $defaultName = 'invoice/nonuserrelated/truncate4';

    public function __construct(
        private CycleDependencyProxy $promise,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setDescription('Truncates, i.e removes all records, in the tables not related to the user.')
            ->setHelp('product_custom, product_image, product_property, product, task, tax_rate, unit_peppol, unit, family, group, '
                    .  'profile, company_private, company, client_note, contract, email_template, from_drop_down, delivery_party, '
                    . 'delivery, delivery_location, project, upload, postal_address, sumex, client_custom, client_peppol, client tables will be truncated until there are no records left in them.');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** Note tables must be truncated in this sequence in order to avoid integrity constraint violations **/ 
        
        $io = new SymfonyStyle($input, $output);
        
        $tables = [
            'product_custom', 'product_image', 'product_property', 'product', 'task', 'tax_rate', 'unit_peppol', 'unit', 'family', 'group', 
            'profile', 'company_private', 'company', 'client_note', 'contract', 'email_template', 'from_drop_down', 'delivery_party', 'delivery', 'delivery_location', 
            'project', 'upload', 'postal_address', 'sumex', 'client_custom', 'client_peppol', 'client', 
            'custom_value', 'custom_field'
        ];
        
        foreach ($tables as $table) {
            $this->promise
                ->getDatabaseProvider()
                ->database()
                ->delete($table)
                ->run();
        }
        
        if (0 === count(($this->promise
                ->getORM()
                ->getRepository(ProductCustom::class))->findAll()) +
            count(($this->promise
                ->getORM()
                ->getRepository(ProductImage::class))->findAll()) +
            count(($this->promise
                ->getORM()
                ->getRepository(ProductProperty::class))->findAll()) +    
            count(($this->promise
                ->getORM()
                ->getRepository(Product::class))->findAll()) +    
            count(($this->promise
                ->getORM()
                ->getRepository(Task::class))->findAll()) +
            count(($this->promise
                ->getORM()
                ->getRepository(TaxRate::class))->findAll()) +
            count(($this->promise
                ->getORM()
                ->getRepository(UnitPeppol::class))->findAll()) +     
            count(($this->promise
                ->getORM()
                ->getRepository(Unit::class))->findAll()) +    
            count(($this->promise
                ->getORM()
                ->getRepository(Family::class))->findAll()) + 
            count(($this->promise
                ->getORM()
                ->getRepository(Group::class))->findAll()) +
            count(($this->promise
                ->getORM()
                ->getRepository(Profile::class))->findAll()) +
            count(($this->promise
                ->getORM()
                ->getRepository(CompanyPrivate::class))->findAll()) +
            count(($this->promise
                ->getORM()
                ->getRepository(Company::class))->findAll()) +
            count(($this->promise
                ->getORM()
                ->getRepository(ClientNote::class))->findAll()) + 
            count(($this->promise
                ->getORM()
                ->getRepository(Contract::class))->findAll()) +
            count(($this->promise
                ->getORM()
                ->getRepository(EmailTemplate::class))->findAll()) +
            count(($this->promise
                ->getORM()
                ->getRepository(FromDropDown::class))->findAll()) +    
            count(($this->promise
                ->getORM()
                ->getRepository(DeliveryParty::class))->findAll()) +    
            count(($this->promise
                ->getORM()
                ->getRepository(Delivery::class))->findAll()) + 
            count(($this->promise
                ->getORM()
                ->getRepository(DeliveryLocation::class))->findAll()) +
            count(($this->promise
                ->getORM()
                ->getRepository(Project::class))->findAll()) +    
            count(($this->promise
                ->getORM()
                ->getRepository(Upload::class))->findAll()) +
            count(($this->promise
                ->getORM()
                ->getRepository(PostalAddress::class))->findAll()) +
            count(($this->promise
                ->getORM()
                ->getRepository(Sumex::class))->findAll()) +    
            count(($this->promise
                ->getORM()
                ->getRepository(ClientCustom::class))->findAll()) +
            count(($this->promise
                ->getORM()
                ->getRepository(ClientPeppol::class))->findAll()) + 
            count(($this->promise
                ->getORM()
                ->getRepository(Client::class))->findAll()) +         
            count(($this->promise
                ->getORM()
                ->getRepository(CustomValue::class))->findAll()) +
            count(($this->promise
                ->getORM()
                ->getRepository(CustomField::class))->findAll())    
        ) 
        {     
            $io->success('Done');
            return ExitCode::OK;
        } else {            
            $io->error('Unspecified error');
            return ExitCode::UNSPECIFIED_ERROR;
        }    
    }
}
