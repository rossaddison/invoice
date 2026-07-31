<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Quote;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Group\Group;
use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Quote\QuoteDeletionService as QDS;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\Quote\QuoteService;
use App\Invoice\Setting\SettingRepository as SR;
use App\User\UserRepository as UR;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers QuoteService::saveQuote() for both an existing draft quote (which
 * gets a generated quote number) and a brand-new quote (which goes through
 * initNewQuote()'s defaulting), plus deleteQuote()'s two-step deletion.
 */
#[Test]
final class QuoteServiceTest
{
    public function existingDraftQuoteWithoutNumberGetsNumberGeneratedAndSaved(): void
    {
        $client = m::mock(Client::class);
        $group = m::mock(Group::class);
        $user = m::mock(User::class);

        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        /** @var \Mockery\Expectation $e */
        $e = $cR->expects('repoClientquery');
        $e->once()->with(2)->andReturn($client);

        /** @var GR&m\MockInterface $gR */
        $gR = m::mock(GR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $gR->expects('repoGroupquery');
        $e2->once()->with(3)->andReturn($group);

        /** @var UR&m\MockInterface $uR */
        $uR = m::mock(UR::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $uR->expects('findById');
        $e3->once()->with(4)->andReturn($user);

        /** @var QDS&m\MockInterface $deletionService */
        $deletionService = m::mock(QDS::class);
        $deletionService->shouldNotReceive('delete');

        /** @var QuoteRepository&m\MockInterface $repo */
        $repo = m::mock(QuoteRepository::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $repo->expects('save');
        $e4->once();

        $sR = m::mock(SR::class);
        $sR->shouldNotReceive('repoCount');
        $sR->shouldNotReceive('getSetting');

        /** @var GR&m\MockInterface $gRParam */
        $gRParam = m::mock(GR::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $gRParam->expects('generateNumber');
        $e5->once()->with(3, true)->andReturn('Q-0001');

        $service = new QuoteService($repo, $cR, $gR, $uR, $deletionService);

        $model = new Quote();
        $model->setId(5);

        $array = [
            'client_id' => 2,
            'group_id' => 3,
            'user_id' => 4,
            'status_id' => 1,
            'date_created' => '2026-01-15',
            'inv_id' => 6,
            'so_id' => 7,
            'delivery_location_id' => 8,
            'discount_amount' => 10.5,
            'url_key' => 'abc',
            'password' => 'pw',
            'notes' => 'note',
        ];

        $result = $service->saveQuote($user, $model, $array, $sR, $gRParam);

        Assert::same($model, $result);
        Assert::same('Q-0001', $model->getNumber());
        Assert::same(6, $model->getInvId());
        Assert::same(7, $model->getSoId());
        Assert::same(2, $model->reqClientId());
        Assert::same(3, $model->reqGroupId());
        Assert::same(1, $model->reqStatusId());
        Assert::same(8, $model->getDeliveryLocationId());
        Assert::same(10.5, $model->getDiscountAmount());
        Assert::same('abc', $model->getUrlKey());
        Assert::same('pw', $model->getPassword());
        Assert::same('note', $model->getNotes());
    }

    public function newQuoteWithoutIdentityInitializesDefaultsAndSaves(): void
    {
        $client = m::mock(Client::class);

        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        /** @var \Mockery\Expectation $e */
        $e = $cR->expects('repoClientquery');
        $e->once()->with(9)->andReturn($client);

        /** @var GR&m\MockInterface $gR */
        $gR = m::mock(GR::class);
        $gR->shouldNotReceive('repoGroupquery');

        /** @var UR&m\MockInterface $uR */
        $uR = m::mock(UR::class);
        $uR->shouldNotReceive('findById');

        /** @var QDS&m\MockInterface $deletionService */
        $deletionService = m::mock(QDS::class);

        /** @var QuoteRepository&m\MockInterface $repo */
        $repo = m::mock(QuoteRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repo->expects('save');
        $e2->once();

        $service = new QuoteService($repo, $cR, $gR, $uR, $deletionService);

        $model = new Quote();

        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $user->shouldReceive('reqId');
        $e3->once()->andReturn(77);

        /** @var SR&m\MockInterface $sR */
        $sR = m::mock(SR::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $sR->shouldReceive('getSetting');
        $e4->once()->with('generate_quote_number_for_draft')->andReturn('0');
        /** @var \Mockery\Expectation $e5 */
        $e5 = $sR->shouldReceive('repoCount');
        $e5->once()->with('quotes_expire_after')->andReturn(0);

        /** @var GR&m\MockInterface $gRParam */
        $gRParam = m::mock(GR::class);
        $gRParam->shouldNotReceive('generateNumber');

        $array = [
            'client_id' => 9,
            'status_id' => 1,
            'date_created' => '2026-02-01',
        ];

        $result = $service->saveQuote($user, $model, $array, $sR, $gRParam);

        Assert::same($model, $result);
        Assert::same(0, $model->getInvId());
        Assert::same(0, $model->getSoId());
        Assert::same('', $model->getNumber());
        Assert::same(1, $model->reqStatusId());
        Assert::same($user, $model->getUser());
        Assert::same(77, $model->reqUserId());
        Assert::same(0.00, $model->getDiscountAmount());
    }

    public function deleteQuoteCallsDeletionServiceThenRepositoryDelete(): void
    {
        /** @var Quote&m\MockInterface $quote */
        $quote = m::mock(Quote::class);

        /** @var QDS&m\MockInterface $deletionService */
        $deletionService = m::mock(QDS::class);
        /** @var \Mockery\Expectation $e */
        $e = $deletionService->expects('delete');
        $e->once()->with($quote);

        /** @var QuoteRepository&m\MockInterface $repo */
        $repo = m::mock(QuoteRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repo->expects('delete');
        $e2->once()->with($quote);

        $cR = m::mock(CR::class);
        $gR = m::mock(GR::class);
        $uR = m::mock(UR::class);

        $service = new QuoteService($repo, $cR, $gR, $uR, $deletionService);
        $service->deleteQuote($quote);
    }
}
