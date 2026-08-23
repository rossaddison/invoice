<?php

declare(strict_types=1);

namespace Tests\Testo\Api;

use App\Api\CurrencyController;
use App\Invoice\Setting\SettingRepository;
use Mockery as m;
use Psr\Http\Message\ResponseInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;

/**
 * Covers CurrencyController — the same four Setting rows
 * SettingRepository::currencyConverter() already converts real invoice
 * amounts with, just handed back as JSON for an external partner (e.g.
 * webshop) instead of used to convert a value here.
 */
#[Test]
final class CurrencyControllerTest
{
    public function indexReturnsAllFourSettingRows(): void
    {
        /** @var SettingRepository&m\MockInterface $settingRepository */
        $settingRepository = m::mock(SettingRepository::class);
        $settingRepository->shouldReceive('getSetting')->once()->with('currency_code_from')->andReturn('GBP');
        $settingRepository->shouldReceive('getSetting')->once()->with('peppol_document_currency')->andReturn('EUR');
        $settingRepository->shouldReceive('getSetting')->once()->with('currency_from_to')->andReturn('1.17');
        $settingRepository->shouldReceive('getSetting')->once()->with('currency_to_from')->andReturn('0.85');

        /** @var ResponseInterface&m\MockInterface $expectedResponse */
        $expectedResponse = m::mock(ResponseInterface::class);
        /** @var DataResponseFactoryInterface&m\MockInterface $responseFactory */
        $responseFactory = m::mock(DataResponseFactoryInterface::class);
        $responseFactory->shouldReceive('createResponse')->once()->with([
            'native' => 'GBP',
            'document' => 'EUR',
            'native_to_document_rate' => '1.17',
            'document_to_native_rate' => '0.85',
        ])->andReturn($expectedResponse);

        $controller = new CurrencyController($responseFactory);

        Assert::same($expectedResponse, $controller->index($settingRepository));
    }
}
