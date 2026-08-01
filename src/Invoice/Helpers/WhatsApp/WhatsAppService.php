<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\WhatsApp;

use App\Invoice\Setting\SettingRepository;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Json\Json;

/**
 * Thin wrapper around Meta's WhatsApp Business Cloud API — there's no
 * official (or widely-used third-party) PHP SDK for it, unlike Telegram's
 * phptg/bot-api, so this talks to the REST API directly via a PSR-18
 * client, the same pattern PeppolSendService already uses for Oxalis.
 *
 * @see https://developers.facebook.com/documentation/business-messaging/whatsapp/get-started
 */
final class WhatsAppService
{
    private const string API_VERSION = 'v21.0';
    private const string API_BASE_URL = 'https://graph.facebook.com';

    public function __construct(
        private readonly SettingRepository $settings,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->enabled()
            && $this->phoneNumberId() !== ''
            && $this->accessToken() !== ''
            && $this->templateName() !== '';
    }

    private function enabled(): bool
    {
        return $this->settings->getSetting('enable_whatsapp') === '1';
    }

    private function phoneNumberId(): string
    {
        return $this->settings->getSetting('whatsapp_phone_number_id');
    }

    private function accessToken(): string
    {
        $encoded = $this->settings->getSetting('whatsapp_access_token');
        return $encoded !== '' ? (string) $this->settings->decode($encoded) : '';
    }

    private function templateName(): string
    {
        return $this->settings->getSetting('whatsapp_template_name');
    }

    private function templateLanguage(): string
    {
        return $this->settings->getSetting('whatsapp_template_language') ?: 'en_GB';
    }

    /**
     * Sends the configured message template. WhatsApp only allows free-form
     * text within a 24-hour window after the customer last messaged the
     * business — every automated notification like this one falls outside
     * that window and must use a pre-approved template instead (see
     * resources/views/invoice/setting/views/partial_settings_whatsapp.php
     * step 5).
     *
     * @param list<string> $templateParameters Positional {{1}}, {{2}}... values for the template body.
     */
    public function sendTemplateMessage(string $toPhoneNumber, array $templateParameters = []): WhatsAppSendResult
    {
        if (!$this->isConfigured()) {
            return new WhatsAppSendResult(false, null, 'WhatsApp is not configured.');
        }

        $body = [
            'messaging_product' => 'whatsapp',
            'to' => $toPhoneNumber,
            'type' => 'template',
            'template' => [
                'name' => $this->templateName(),
                'language' => ['code' => $this->templateLanguage()],
                'components' => $this->buildComponents($templateParameters),
            ],
        ];

        try {
            $request = $this->requestFactory
                ->createRequest('POST', $this->messagesUrl())
                ->withHeader('Authorization', 'Bearer ' . $this->accessToken())
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream(Json::encode($body)));

            $response = $this->httpClient->sendRequest($request);
            return $this->resolveResult($response->getStatusCode(), (string) $response->getBody());
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('WhatsApp send failed.', ['exception' => $e]);
            return new WhatsAppSendResult(false, null, $e->getMessage());
        }
    }

    private function messagesUrl(): string
    {
        return self::API_BASE_URL . '/' . self::API_VERSION . '/' . $this->phoneNumberId() . '/messages';
    }

    /**
     * @param list<string> $templateParameters
     * @return list<array{type: string, parameters: list<array{type: string, text: string}>}>
     */
    private function buildComponents(array $templateParameters): array
    {
        if ($templateParameters === []) {
            return [];
        }
        return [[
            'type' => 'body',
            'parameters' => array_map(
                static fn (string $value): array => ['type' => 'text', 'text' => $value],
                $templateParameters,
            ),
        ]];
    }

    private function resolveResult(int $statusCode, string $responseBody): WhatsAppSendResult
    {
        if ($statusCode >= 400) {
            $this->logger->error('WhatsApp send failed.', ['status' => $statusCode, 'body' => $responseBody]);
            return new WhatsAppSendResult(false, null, 'WhatsApp API HTTP ' . $statusCode . ': ' . $responseBody);
        }

        /** @var array{messages?: list<array{id?: string}>} $decoded */
        $decoded = (array) Json::decode($responseBody);
        $messageId = $decoded['messages'][0]['id'] ?? null;
        return new WhatsAppSendResult(true, $messageId, null);
    }
}
