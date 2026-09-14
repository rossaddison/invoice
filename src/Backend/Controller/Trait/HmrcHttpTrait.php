<?php

declare(strict_types=1);

namespace App\Backend\Controller\Trait;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

trait HmrcHttpTrait
{
    private function getWebAppViaServerHeaders(string $otpReference): array
    {
        return $this->webAppViaServerBuildArrayFromStrings(
            $this->sR->getSetting('fph_connection_method'),
            $this->sR->getSetting('fph_client_browser_js_user_agent'),
            $this->sR->getSetting('fph_client_device_id'),
            $this->sR->fphGenerateMultiFactor('TOTP', $otpReference),
            $this->sR->getGovClientPublicIp() ?? '',
            $this->sR->getGovClientPublicIpTimestamp() ?? '',
            $this->sR->getGovClientPublicPort(),
            $this->sR->getGovClientScreens(),
            $this->sR->getGovClientTimezone(),
            $this->sR->getGovClientUserIDs(),
            $this->sR->getSetting('fph_window_size'),
            $this->sR->getGovVendorForwarded(),
            $this->sR->getGovVendorLicenseIDs(),
            $this->sR->getGovVendorProductName(),
            $this->sR->getGovVendorPublicIP(),
            $this->sR->getGovVendorVersion(),
        );
    }

    private function getFphValidateHeadersUrl(): string
    {
        return 'https://test-api.service.hmrc.gov.uk/test/fraud-prevention-headers/validate';
    }

    /** $api = "self-assessment" / "vat" / "employment" / "customs" / "individuals" */
    private function getFphValidationFeedbackUrl(string $api): string
    {
        return 'https://test-api.service.hmrc.gov.uk/test/fraud-prevention-headers/' . $api . '/validation-feedback';
    }

    /**
     * Real live-testing bug fixed 2026-09-09: vatObligations()/
     * vatReturnSubmit()/selfEmploymentBusinesses() used to hardcode either
     * the production (`api.service.hmrc.gov.uk`) or sandbox
     * (`test-api.service.hmrc.gov.uk`) host directly, independent of which
     * one the OAuth login actually authenticated against -- a sandbox
     * (dev) token sent to the production host always 401s, which is
     * exactly what surfaced testing against a real HMRC sandbox test user
     * (VRN 931392528). `DeveloperSandboxHmrc::getApiBaseUrl1()` already
     * resolves the correct host, but `setEnvironment()` is only ever
     * called from AuthController/SignupController
     * (`Oauth2::initializeOauth2IdentityProviderDualUrls()`) -- on a
     * later, separate request (like this one) that never happened, so
     * this resolves it fresh from the same `SettingRepository::getEnv()`
     * check that method uses, rather than assuming an earlier request
     * left the injected instance in the right state.
     *
     * Deliberately not used by createTestUserIndividual() or the two
     * fraud-prevention-headers helpers below -- HMRC's Create Test User
     * and Test Fraud Prevention Headers APIs are sandbox-only tooling
     * that doesn't exist in production at all, so those stay hardcoded to
     * test-api.service.hmrc.gov.uk regardless of environment.
     */
    private function resolveHmrcApiBaseUrl(): string
    {
        $environment = $this->sR->getEnv() === 'dev' ? 'dev' : 'prod';
        $this->developerSandboxHmrc->setEnvironment($environment);
        return $this->developerSandboxHmrc->getApiBaseUrl1();
    }

    private function createRequest(string $method, string $uri): Request
    {
        return $this->requestFactory->createRequest($method, $uri);
    }

    private function sendRequest(Request $request): Response
    {
        return $this->httpClient->sendRequest($request);
    }

    /**
     * Note: The connection method determines what headers are included.
     *       16 headers are required for the WEB_APP_VIA_SERVER method.
     * Related logic: https://developer.service.hmrc.gov.uk/guides/fraud-prevention/connection-method/web-app-via-server/
     */
    private function webAppViaServerBuildArrayFromStrings(// NOSONAR php:S107 — HMRC fraud-prevention spec mandates exactly 16 header strings
        string $govClientConnectionMethod,
        string $govClientBrowserJsUserAgent,
        string $govClientDeviceID,
        string $govClientMultiFactor,
        string $govClientPublicIp,
        string $govClientPublicIpTimestamp,
        int $govClientPublicPort,
        string $govClientScreens,
        string $govClientTimezone,
        string $govClientUserIds,
        string $govClientWindowSize,
        string $govVendorForwarded,
        string $govVendorLicenseIDs,
        string $govVendorProductName,
        string $govVendorPublicIP,
        string $govVendorVersion,
    ): array {
        return [
            'Gov-Client-Connection-Method' => $govClientConnectionMethod,
            'Gov-Client-Browser-JS-User-Agent' => $govClientBrowserJsUserAgent,
            'Gov-Client-Device-ID' => $govClientDeviceID,
            'Gov-Client-Multi-Factor' => $govClientMultiFactor,
            'Gov-Client-Public-Ip' => $govClientPublicIp,
            'Gov-Client-Public-IP-Timestamp' => $govClientPublicIpTimestamp,
            'Gov-Client-Public-Port' => $govClientPublicPort,
            'Gov-Client-Screens' => $govClientScreens,
            'Gov-Client-Timezone' => $govClientTimezone,
            'Gov-Client-User-IDs' => $govClientUserIds,
            'Gov-Client-Window-Size' => $govClientWindowSize,
            'Gov-Vendor-Forwarded' => $govVendorForwarded,
            'Gov-Vendor-License-IDs' => $govVendorLicenseIDs,
            'Gov-Vendor-Product-Name' => $govVendorProductName,
            'Gov-Vendor-Public-IP' => $govVendorPublicIP,
            'Gov-Vendor-Version' => $govVendorVersion,
        ];
    }

    /** @psalm-return HandlerStack */
    private function buildHandlerStackWithLogging(string $logFile): HandlerStack
    {
        $stack = HandlerStack::create();
        $stack->push($this->getRequestLoggingMiddleware($logFile));
        return $stack;
    }

    /** @return callable(callable): callable */
    private function getRequestLoggingMiddleware(string $logFile): callable
    {
        return fn (callable $handler): callable => function (Request $request, array $options) use ($handler, $logFile): PromiseInterface {
            // Redact the bearer token before it ever reaches the log file --
            // the real $request (unmodified) is still what's actually sent
            // to HMRC below; this only affects what gets written to disk.
            $headersForLog = $request->getHeaders();
            foreach (array_keys($headersForLog) as $name) {
                if (strtolower((string) $name) === 'authorization') {
                    $headersForLog[$name] = ['[REDACTED]'];
                }
            }
            $headersJson = json_encode(
                $headersForLog,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );

            if ($headersJson === false) {
                $headersJson = 'Error encoding headers';
            }

            $logEntry = sprintf(
                "[%s] %s %s\nHeaders: %s\n\n",
                date('c'),
                $request->getMethod(),
                (string) $request->getUri(),
                $headersJson,
            );
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

            /** @psalm-suppress MixedReturnStatement */
            return $handler($request, $options);
        };
    }

    /** @psalm-return HttpClient */
    private function createLoggedGuzzleClient(string $logFile): HttpClient
    {
        $stack = $this->buildHandlerStackWithLogging($logFile);
        return new HttpClient(['handler' => $stack]);
    }
}
