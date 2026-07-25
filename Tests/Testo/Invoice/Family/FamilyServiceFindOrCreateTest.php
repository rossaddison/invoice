<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Family;

use App\Infrastructure\Persistence\Family\Family;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Family\FamilyService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers FamilyService::findOrCreateByStreetName() — the HomeCare signup
 * flow's street-to-run resolution: reuse a pre-entered Family (street/run)
 * when staff already created one under the same secondary category,
 * otherwise create a new one.
 */
#[Test]
final class FamilyServiceFindOrCreateTest
{
    public function existingFamilyIsReusedAndNotSaved(): void
    {
        $existing = m::mock(Family::class);

        /** @var FamilyRepository&m\MockInterface $repo */
        $repo = m::mock(FamilyRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('repoFamilyByNameAndSecondaryCategoryQuery');
        $e->once()->with('Elm Street', 7)->andReturn($existing);
        $repo->shouldNotReceive('save');

        $service = new FamilyService($repo);

        Assert::same($existing, $service->findOrCreateByStreetName('Elm Street', 7));
    }

    public function noMatchCreatesAndSavesNewFamilyWithSecondaryCategory(): void
    {
        /** @var FamilyRepository&m\MockInterface $repo */
        $repo = m::mock(FamilyRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('repoFamilyByNameAndSecondaryCategoryQuery');
        $e->once()->with('Oak Avenue', 7)->andReturn(null);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repo->expects('save');
        $e2->once();

        $service = new FamilyService($repo);
        $family = $service->findOrCreateByStreetName('Oak Avenue', 7);

        Assert::same('Oak Avenue', $family->getFamilyName());
        Assert::same(7, $family->getCategorySecondaryId());
    }

    public function sameStreetNameDifferentSecondaryCategoryCreatesSeparateFamily(): void
    {
        /** @var FamilyRepository&m\MockInterface $repo */
        $repo = m::mock(FamilyRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('repoFamilyByNameAndSecondaryCategoryQuery');
        $e->once()->with('Elm Street', 3)->andReturn(null);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repo->expects('save');
        $e2->once();

        $service = new FamilyService($repo);
        $family = $service->findOrCreateByStreetName('Elm Street', 3);

        Assert::same('Elm Street', $family->getFamilyName());
        Assert::same(3, $family->getCategorySecondaryId());
    }
}
