<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers\WhatsApp;

use App\Invoice\Helpers\WhatsApp\WhatsAppService;
use App\Invoice\Setting\SettingRepository;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Test;

#[Test]
final class WhatsAppServiceTest
{

    /**
     * @return LoggerInterface&m\MockInterface
     */
    private function makeLoggerInterfaceSpy(): LoggerInterface
    {
        /** @var LoggerInterface&m\MockInterface $mock */
        $mock = m::spy(LoggerInterface::class);
        return $mock;
    }
    private const string TO = '+447700900123';

    /**
     * @param array<string, string> $settings
     */
    private function makeSettings(array $settings): SettingRepository&m\MockInterface
    {
        /** @var SettingRepository&m\MockInterface $sR */
        $sR = m::mock(SettingRepository::class);
        $e = $sR->shouldReceive('getSetting');
        $e->andReturnUsing(static fn (string $key): string => $settings[$key] ?? '');
        $e2 = $sR->shouldReceive('decode');
        $e2->andReturnUsing(static fn (string $value): string => $value);

        return $sR;
    }

    /**
     * @param array<string, string> $settings
     */
    private function makeService(array $settings, ?ClientInterface $httpClient = null): WhatsAppService
    {
        $factory = new Psr17Factory();

        /** @var ClientInterface&m\MockInterface $httpClient */
        $httpClient = $httpClient ?? m::mock(ClientInterface::class);
        return new WhatsAppService(
            $this->makeSettings($settings),
            $httpClient,
            $factory,
            $factory,
            $this->makeLoggerInterfaceSpy(),
        );
    }

    /**
     * @return array<string, string>
     */
    private function configuredSettings(): array
    {
        return [
            'enable_whatsapp' => '1',
            'whatsapp_phone_number_id' => '123456',
            'whatsapp_access_token' => 'token',
            'whatsapp_template_name' => 'invoice_ready',
            'whatsapp_template_language' => 'en_GB',
        ];
    }

    public function isConfiguredReturnsFalseWhenDisabled(): void
    {
        $settings = $this->configuredSettings();
        $settings['enable_whatsapp'] = '0';

        Assert::false($this->makeService($settings)->isConfigured());
    }

    public function isConfiguredReturnsFalseWhenTemplateNameMissing(): void
    {
        $settings = $this->configuredSettings();
        $settings['whatsapp_template_name'] = '';

        Assert::false($this->makeService($settings)->isConfigured());
    }

    public function isConfiguredReturnsTrueWhenAllSettingsPresent(): void
    {
        Assert::true($this->makeService($this->configuredSettings())->isConfigured());
    }

    public function sendTemplateMessageFailsFastWhenNotConfigured(): void
    {
        $settings = $this->configuredSettings();
        $settings['enable_whatsapp'] = '0';

        $result = $this->makeService($settings)->sendTemplateMessage(self::TO);

        Assert::false($result->sent);
        Assert::null($result->messageId);
    }

    public function sendTemplateMessageReturnsMessageIdOnSuccess(): void
    {
        $factory = new Psr17Factory();
        $responseBody = '{"messages":[{"id":"wamid.HBg"}]}';

        $capturedPath = '';
        /** @var ClientInterface&m\MockInterface $httpClient */
        $httpClient = m::mock(ClientInterface::class);
        $e = $httpClient->shouldReceive('sendRequest');
        $e->once()->andReturnUsing(function (RequestInterface $request) use (&$capturedPath, $factory, $responseBody): ResponseInterface {
            $capturedPath = $request->getUri()->getPath();
            return $factory->createResponse(200)->withBody($factory->createStream($responseBody));
        });

        $result = $this->makeService($this->configuredSettings(), $httpClient)
            ->sendTemplateMessage(self::TO);

        Assert::true($result->sent);
        Assert::same('wamid.HBg', $result->messageId);
        Assert::same('/v21.0/123456/messages', $capturedPath);
    }

    public function sendTemplateMessageReturnsErrorOnHttpFailureStatus(): void
    {
        $factory = new Psr17Factory();
        /** @var ClientInterface&m\MockInterface $httpClient */
        $httpClient = m::mock(ClientInterface::class);
        $e = $httpClient->shouldReceive('sendRequest');
        $e->once()->andReturn(
            $factory->createResponse(401)->withBody($factory->createStream('{"error":"invalid token"}')),
        );

        $result = $this->makeService($this->configuredSettings(), $httpClient)
            ->sendTemplateMessage(self::TO);

        Assert::false($result->sent);
        Assert::null($result->messageId);
        Assert::true($result->error !== null && str_contains($result->error, '401'));
    }
}
