<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\HomeCareRunSheet;

use Anthropic\Messages\Base64ImageSource\MediaType;
use App\Infrastructure\Persistence\HomeCareRunSheetItem\HomeCareRunSheetItem;
use App\Invoice\HomeCareRunSheetItem\HomeCareRunSheetItemRepository as RSIR;
use App\Invoice\HomeCareRunSheet\HomeCareRunSheetVisionService;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Worker\WorkerRepository as WR;
use Mockery as m;
use Testo\Assert\ExpectException;
use Testo\Test;

/**
 * Covers only the two guard clauses HomeCareRunSheetVisionService::readScan()
 * checks before it would construct a real Anthropic\Client and make a network
 * call — this codebase has no existing precedent for intercepting a real
 * outbound Anthropic/vision API call in a unit test (same reasoning as
 * LowStockNotifierTest for TelegramHelper). The live-verification session
 * (see project_homecare_run_signoff_design memory) confirmed the actual API
 * call itself reaches Anthropic with the configured key; a successful
 * extraction has never been observed end-to-end, blocked on account credit.
 *
 * @see HomeCareRunSheetVisionService
 */
#[Test]
final class HomeCareRunSheetVisionServiceTest
{
    public function doesNothingWhenGivenNoItems(): void
    {
        /** @var RSIR&m\MockInterface $rsiR */
        $rsiR = m::mock(RSIR::class);
        $rsiR->shouldNotReceive('save');

        /** @var WR&m\MockInterface $wR */
        $wR = m::mock(WR::class);
        $wR->shouldNotReceive('findAllActive');

        /** @var SR&m\MockInterface $sR */
        $sR = m::mock(SR::class);
        $sR->shouldNotReceive('getSetting');

        $service = new HomeCareRunSheetVisionService($rsiR, $wR, $sR);
        $service->readScan([], 'not-real-image-bytes', MediaType::IMAGE_JPEG);
    }

    #[ExpectException(\RuntimeException::class)]
    public function throwsWhenNoVisionApiKeyIsConfigured(): void
    {
        /** @var RSIR&m\MockInterface $rsiR */
        $rsiR = m::mock(RSIR::class);

        $item = new HomeCareRunSheetItem(run_sheet_id: 5, inv_id: 311);

        /** @var WR&m\MockInterface $wR */
        $wR = m::mock(WR::class);
        $wR->shouldNotReceive('findAllActive');

        /** @var SR&m\MockInterface $sR */
        $sR = m::mock(SR::class);
        $sR->shouldReceive('getSetting')->once()->with('homecare_vision_api_key')->andReturn('');

        $service = new HomeCareRunSheetVisionService($rsiR, $wR, $sR);
        $service->readScan([$item], 'not-real-image-bytes', MediaType::IMAGE_JPEG);
    }
}
