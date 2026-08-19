<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Company;

use App\Infrastructure\Persistence\Company\Company;
use App\Invoice\Company\CompanyRepository;
use App\Invoice\Company\CompanyService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers CompanyService: saveCompany's field assignment (current, address,
 * contact, social media, and arbitration groups) and deleteCompany.
 */
#[Test]
final class CompanyServiceTest
{
    private function makeService(?CompanyRepository $repository = null): CompanyService
    {
        /** @var CompanyRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(CompanyRepository::class);
        return new CompanyService($repository);
    }

    public function saveCompanySetsAllFieldsAndSaves(): void
    {
        $model = new Company();
        $array = [
            'current' => '1',
            'name' => 'Acme Ltd',
            'address_1' => '1 Main St',
            'address_2' => 'Suite 2',
            'city' => 'London',
            'state' => 'Greater London',
            'zip' => 'SW1A 1AA',
            'country' => 'UK',
            'phone' => '020-1234-5678',
            'fax' => '020-1234-5679',
            'email' => 'info@acme.example',
            'web' => 'https://acme.example',
            'seo_description' => 'Acme Ltd invoices',
            'slack' => 'acme-slack',
            'facebook' => 'acme-fb',
            'twitter' => 'acme-tw',
            'linkedin' => 'acme-li',
            'whatsapp' => '+441234567890',
            'arbitrationBody' => 'LCIA',
            'arbitrationJurisdiction' => 'England and Wales',
        ];

        /** @var CompanyRepository&m\MockInterface $repository */
        $repository = m::mock(CompanyRepository::class);
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveCompany($model, $array);

        Assert::same(1, $model->getCurrent());
        Assert::same('Acme Ltd', $model->getName());
        Assert::same('1 Main St', $model->getAddress1());
        Assert::same('Suite 2', $model->getAddress2());
        Assert::same('London', $model->getCity());
        Assert::same('Greater London', $model->getState());
        Assert::same('SW1A 1AA', $model->getZip());
        Assert::same('UK', $model->getCountry());
        Assert::same('020-1234-5678', $model->getPhone());
        Assert::same('020-1234-5679', $model->getFax());
        Assert::same('info@acme.example', $model->getEmail());
        Assert::same('https://acme.example', $model->getWeb());
        Assert::same('Acme Ltd invoices', $model->getSeoDescription());
        Assert::same('acme-slack', $model->getSlack());
        Assert::same('acme-fb', $model->getFacebook());
        Assert::same('acme-tw', $model->getTwitter());
        Assert::same('acme-li', $model->getLinkedIn());
        Assert::same('+441234567890', $model->getWhatsapp());
        Assert::same('LCIA', $model->getArbitrationBody());
        Assert::same('England and Wales', $model->getArbitrationJurisdiction());
    }

    public function saveCompanySetsCurrentToZeroWhenNotOne(): void
    {
        $model = new Company();
        $array = ['current' => '0'];

        /** @var CompanyRepository&m\MockInterface $repository */
        $repository = m::mock(CompanyRepository::class);
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveCompany($model, $array);

        Assert::same(0, $model->getCurrent());
        Assert::same('', $model->getName());
    }

    public function deleteCompanyCallsRepositoryDelete(): void
    {
        $model = new Company();

        /** @var CompanyRepository&m\MockInterface $repository */
        $repository = m::mock(CompanyRepository::class);
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteCompany($model);
    }
}
