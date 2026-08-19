<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\CompanyPrivate;

use App\Infrastructure\Persistence\Company\Company;
use App\Infrastructure\Persistence\CompanyPrivate\CompanyPrivate;
use App\Invoice\Company\CompanyRepository as CR;
use App\Invoice\CompanyPrivate\CompanyPrivateRepository;
use App\Invoice\CompanyPrivate\CompanyPrivateService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers CompanyPrivateService: saveCompanyPrivate's company relation
 * persistence and full field assignment (including date parsing), and
 * deleteCompanyPrivate.
 */
#[Test]
final class CompanyPrivateServiceTest
{
    private function makeService(
        ?CompanyPrivateRepository $repository = null,
        ?CR $cR = null,
    ): CompanyPrivateService {
        /** @var CompanyPrivateRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(CompanyPrivateRepository::class);
        /** @var CR&m\MockInterface $cR */
        $cR = $cR ?? m::mock(CR::class);
        return new CompanyPrivateService($repository, $cR);
    }

    public function saveCompanyPrivateSetsCompanyRelationAndAllFieldsAndSaves(): void
    {
        $model = new CompanyPrivate();
        $array = [
            'company_id' => 3,
            'vat_id' => 'VAT-1',
            'tax_code' => 'TX-1',
            'iban' => 'GB33BUKB20201555555555',
            'bacs_sort_code' => '12-34-56',
            'bacs_account_number' => '12345678',
            'gln' => '1234567890123',
            'rcc' => 'RCC-1',
            'logo_filename' => 'logo.png',
            'logo_width' => 80,
            'logo_height' => 40,
            'logo_margin' => 10,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ];

        /** @var Company&m\MockInterface $company */
        $company = m::mock(Company::class);

        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        $e = $cR->expects('repoCompanyquery');
        $e->once()->with(3)->andReturn($company);

        /** @var CompanyPrivateRepository&m\MockInterface $repository */
        $repository = m::mock(CompanyPrivateRepository::class);
        $e2 = $repository->expects('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $cR);
        $service->saveCompanyPrivate($model, $array);

        Assert::same($company, $model->getCompany());
        Assert::same(3, $model->reqCompanyId());
        Assert::same('VAT-1', $model->getVatId());
        Assert::same('TX-1', $model->getTaxCode());
        Assert::same('GB33BUKB20201555555555', $model->getIban());
        Assert::same('12-34-56', $model->getBacsSortCode());
        Assert::same('12345678', $model->getBacsAccountNumber());
        Assert::same('1234567890123', $model->getGln());
        Assert::same('RCC-1', $model->getRcc());
        Assert::same('logo.png', $model->getLogoFilename());
        Assert::same(80, $model->getLogoWidth());
        Assert::same(40, $model->getLogoHeight());
        Assert::same(10, $model->getLogoMargin());
        Assert::same('2026-01-01', $model->getStartDate()?->format('Y-m-d'));
        Assert::same('2026-12-31', $model->getEndDate()?->format('Y-m-d'));
    }

    public function saveCompanyPrivateSkipsCompanyRelationWhenIdMissing(): void
    {
        $model = new CompanyPrivate();

        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        $cR->shouldNotReceive('repoCompanyquery');

        /** @var CompanyPrivateRepository&m\MockInterface $repository */
        $repository = m::mock(CompanyPrivateRepository::class);
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $cR);
        $service->saveCompanyPrivate($model, []);

        Assert::null($model->getCompany());
    }

    public function deleteCompanyPrivateCallsRepositoryDelete(): void
    {
        $model = new CompanyPrivate();

        /** @var CompanyPrivateRepository&m\MockInterface $repository */
        $repository = m::mock(CompanyPrivateRepository::class);
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteCompanyPrivate($model);
    }
}
