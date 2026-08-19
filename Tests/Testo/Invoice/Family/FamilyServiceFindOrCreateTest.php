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
        /** @var Family&m\MockInterface $existing */
        $existing = m::mock(Family::class);

        /** @var FamilyRepository&m\MockInterface $repo */
        $repo = m::mock(FamilyRepository::class);
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
        $e = $repo->expects('repoFamilyByNameAndSecondaryCategoryQuery');
        $e->once()->with('Oak Avenue', 7)->andReturn(null);
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
        $e = $repo->expects('repoFamilyByNameAndSecondaryCategoryQuery');
        $e->once()->with('Elm Street', 3)->andReturn(null);
        $e2 = $repo->expects('save');
        $e2->once();

        $service = new FamilyService($repo);
        $family = $service->findOrCreateByStreetName('Elm Street', 3);

        Assert::same('Elm Street', $family->getFamilyName());
        Assert::same(3, $family->getCategorySecondaryId());
    }

    public function deleteFamilyCallsRepositoryDelete(): void
    {
        /** @var Family&m\MockInterface $family */
        $family = m::mock(Family::class);

        /** @var FamilyRepository&m\MockInterface $repo */
        $repo = m::mock(FamilyRepository::class);
        $e = $repo->expects('delete');
        $e->once()->with($family);

        $service = new FamilyService($repo);
        $service->deleteFamily($family);
    }

    public function saveStreetOrdersSetsSequentialPositionsAndSavesEachFamily(): void
    {
        /** @var Family&m\MockInterface $family1 */
        $family1 = m::mock(Family::class);
        $e = $family1->shouldReceive('setStreetSortOrder');
        $e->once()->with(1);

        /** @var Family&m\MockInterface $family2 */
        $family2 = m::mock(Family::class);
        $e2 = $family2->shouldReceive('setStreetSortOrder');
        $e2->once()->with(2);

        /** @var FamilyRepository&m\MockInterface $repo */
        $repo = m::mock(FamilyRepository::class);
        $e3 = $repo->expects('repoFamilyquery');
        $e3->once()->with(11)->andReturn($family1);
        $e4 = $repo->expects('repoFamilyquery');
        $e4->once()->with(22)->andReturn($family2);
        $e5 = $repo->expects('save');
        $e5->once()->with($family1);
        $e6 = $repo->expects('save');
        $e6->once()->with($family2);

        $service = new FamilyService($repo);
        $service->saveStreetOrders([11, 22]);
    }

    public function saveStreetOrdersSkipsUnknownFamilyId(): void
    {
        /** @var FamilyRepository&m\MockInterface $repo */
        $repo = m::mock(FamilyRepository::class);
        $e = $repo->expects('repoFamilyquery');
        $e->once()->with(99)->andReturn(null);
        $repo->shouldNotReceive('save');

        $service = new FamilyService($repo);
        $service->saveStreetOrders([99]);
    }
}
