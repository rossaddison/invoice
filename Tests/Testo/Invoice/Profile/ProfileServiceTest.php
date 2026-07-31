<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Profile;

use App\Infrastructure\Persistence\Company\Company;
use App\Infrastructure\Persistence\Profile\Profile;
use App\Invoice\Company\CompanyRepository as CR;
use App\Invoice\Profile\ProfileRepository;
use App\Invoice\Profile\ProfileService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers ProfileService: saveProfile's company-relation persistence and
 * field assignment (including the always-set current flag), and
 * deleteProfile.
 */
#[Test]
final class ProfileServiceTest
{
    private function makeService(
        ?ProfileRepository $repository = null,
        ?CR $cR = null,
    ): ProfileService {
        $repository = $repository ?? m::mock(ProfileRepository::class);
        $cR = $cR ?? m::mock(CR::class);
        return new ProfileService($repository, $cR);
    }

    public function saveProfileSetsAllFieldsAndSaves(): void
    {
        $model = new Profile();
        $array = [
            'company_id' => 2,
            'current' => '1',
            'mobile' => '07700900000',
            'email' => 'owner@acme.example',
            'description' => 'Main profile',
        ];

        $company = m::mock(Company::class);
        $cR = m::mock(CR::class);
        /** @var \Mockery\Expectation $e */
        $e = $cR->shouldReceive('repoCompanyquery');
        $e->once()->with(2)->andReturn($company);

        $repository = m::mock(ProfileRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repository->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $cR);
        $service->saveProfile($model, $array);

        Assert::same($company, $model->getCompany());
        Assert::same(2, $model->reqCompanyId());
        Assert::same(1, $model->getCurrent());
        Assert::same('07700900000', $model->getMobile());
        Assert::same('owner@acme.example', $model->getEmail());
        Assert::same('Main profile', $model->getDescription());
    }

    public function saveProfileSetsCurrentToZeroWhenNotOne(): void
    {
        $model = new Profile();
        $array = ['current' => '0'];

        $cR = m::mock(CR::class);
        $cR->shouldNotReceive('repoCompanyquery');

        $repository = m::mock(ProfileRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $cR);
        $service->saveProfile($model, $array);

        Assert::null($model->getCompany());
        Assert::same(0, $model->getCurrent());
    }

    public function deleteProfileCallsRepositoryDelete(): void
    {
        $model = new Profile();

        /** @var ProfileRepository&m\MockInterface $repository */
        $repository = m::mock(ProfileRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteProfile($model);
    }
}
