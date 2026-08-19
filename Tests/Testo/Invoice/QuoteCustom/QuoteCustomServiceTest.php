<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\QuoteCustom;

use App\Infrastructure\Persistence\CustomField\CustomField;
use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\QuoteCustom\QuoteCustom;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteCustom\QuoteCustomRepository;
use App\Invoice\QuoteCustom\QuoteCustomService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers QuoteCustomService: saveQuoteCustom's quote/custom-field relation
 * persistence and field assignment, and deleteQuoteCustom.
 */
#[Test]
final class QuoteCustomServiceTest
{
    private function makeService(
        ?QuoteCustomRepository $repository = null,
        ?CFR $cfR = null,
        ?QR $qR = null,
    ): QuoteCustomService {
        /** @var QuoteCustomRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(QuoteCustomRepository::class);
        /** @var CFR&m\MockInterface $cfR */
        $cfR = $cfR ?? m::mock(CFR::class);
        /** @var QR&m\MockInterface $qR */
        $qR = $qR ?? m::mock(QR::class);
        return new QuoteCustomService($repository, $cfR, $qR);
    }

    public function saveQuoteCustomSetsAllFieldsAndSaves(): void
    {
        $model = new QuoteCustom();
        $array = [
            'quote_id' => 1,
            'custom_field_id' => 2,
            'value' => 'Reference',
        ];

        /** @var Quote&m\MockInterface $quote */
        $quote = m::mock(Quote::class);
        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $e = $qR->shouldReceive('repoQuoteUnLoadedquery');
        $e->once()->with(1)->andReturn($quote);

        /** @var CustomField&m\MockInterface $customField */
        $customField = m::mock(CustomField::class);
        /** @var CFR&m\MockInterface $cfR */
        $cfR = m::mock(CFR::class);
        $e2 = $cfR->shouldReceive('repoCustomFieldquery');
        $e2->once()->with(2)->andReturn($customField);

        /** @var QuoteCustomRepository&m\MockInterface $repository */
        $repository = m::mock(QuoteCustomRepository::class);
        $e3 = $repository->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $cfR, $qR);
        $service->saveQuoteCustom($model, $array);

        Assert::same($quote, $model->getQuote());
        Assert::same($customField, $model->getCustomField());
        Assert::same(1, $model->reqQuoteId());
        Assert::same(2, $model->reqCustomFieldId());
        Assert::same('Reference', $model->getValue());
    }

    public function saveQuoteCustomSkipsRelationsWhenIdsMissing(): void
    {
        $model = new QuoteCustom();
        $array = [];

        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $qR->shouldNotReceive('repoQuoteUnLoadedquery');

        /** @var CFR&m\MockInterface $cfR */
        $cfR = m::mock(CFR::class);
        $cfR->shouldNotReceive('repoCustomFieldquery');

        /** @var QuoteCustomRepository&m\MockInterface $repository */
        $repository = m::mock(QuoteCustomRepository::class);
        $e = $repository->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $cfR, $qR);
        $service->saveQuoteCustom($model, $array);

        Assert::null($model->getQuote());
        Assert::null($model->getCustomField());
    }

    public function deleteQuoteCustomCallsRepositoryDelete(): void
    {
        $model = new QuoteCustom();

        /** @var QuoteCustomRepository&m\MockInterface $repository */
        $repository = m::mock(QuoteCustomRepository::class);
        $e = $repository->shouldReceive('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteQuoteCustom($model);
    }
}
