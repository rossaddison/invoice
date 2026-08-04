<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\Service;

/**
 * Robokassa request signing / callback verification, built directly against
 * Robokassa's own protocol rather than a third-party SDK — see
 * docs/GATEWAY_STATUS_PAGE_AUGUST_2026.md for why.
 *
 * Every formula here is ground-truthed against Robokassa's own official
 * OpenAPI specification (https://docs.robokassa.ru/openapi/robokassa.yaml):
 *
 * - `signJwt()` — the `createInvoice` operation's `x-robokassa-jwt` block:
 *   secret `MerchantLogin:Пароль#1`, signingInput
 *   `BASE64URL_HEADER.BASE64URL_PAYLOAD`, signature `HMAC(signingInput,
 *   secret)`.
 * - `signOpState()` — the `getOperationState` operation's
 *   `x-robokassa-signature`: formula `MerchantLogin:InvoiceID:Пароль#2`.
 * - `verifyResultUrlSignature()` — the `paymentResult` webhook's
 *   `x-robokassa-signature`: formula `OutSum:InvId:Пароль#2:Shp_*`, with
 *   Shp_ custom parameters in alphabetical order.
 * - `signRefundJwt()` — the `createRefund` operation's `x-robokassa-jwt`
 *   block: secret `Пароль#3` (a separate password from Пароль#1/#2, only
 *   issued once Robokassa support has enabled the Refund API for the
 *   merchant account), signingInput `BASE64URL_HEADER.BASE64URL_PAYLOAD`,
 *   signature `HMAC(signingInput, secret)` using HS256 (the spec permits
 *   HS256/HS384/HS512; HS256 is used here as the conventional default).
 */
final class RobokassaSignatureService
{
    /**
     * Base64url without padding, matching Robokassa's own JWT encoding.
     */
    public function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param array<string, scalar> $header
     * @param array<string, scalar|array<array-key, mixed>> $payload
     * @return array{0: string, 1: string} [encodedHeader.encodedPayload, signature]
     */
    public function signJwt(array $header, array $payload, string $merchantLogin, string $password1): array
    {
        $encodedHeader = $this->base64Url(json_encode($header, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $encodedPayload = $this->base64Url(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $dataToSign = $encodedHeader . '.' . $encodedPayload;

        $rawSignature = hash_hmac('md5', $dataToSign, $merchantLogin . ':' . $password1, true);

        return [$dataToSign, $this->base64Url($rawSignature)];
    }

    /**
     * Signature for the WebService status-check API (OpStateExt) — see this
     * class's docblock.
     */
    public function signOpState(string $merchantLogin, string $invId, string $password2): string
    {
        return hash('md5', $merchantLogin . ':' . $invId . ':' . $password2);
    }

    /**
     * Verifies an inbound Result URL callback's SignatureValue against the
     * `paymentResult` webhook's documented formula — see this class's
     * docblock.
     *
     * @param array<string, string> $shpParams Shp_-prefixed custom parameters, if any, from the callback request.
     */
    public function verifyResultUrlSignature(
        string $outSum,
        string $invId,
        string $password2,
        string $signatureValue,
        array $shpParams = [],
    ): bool {
        $parts = [$outSum, $invId, $password2];
        $pairs = [];
        foreach ($shpParams as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }
        sort($pairs);
        foreach ($pairs as $pair) {
            $parts[] = $pair;
        }

        $expected = hash('md5', implode(':', $parts));

        return hash_equals(strtolower($expected), strtolower($signatureValue));
    }

    /**
     * Signs a Refund API request payload (RefundPayload: OpKey, optionally
     * RefundSum) — see this class's docblock.
     *
     * @param array<string, scalar> $payload
     * @return array{0: string, 1: string} [encodedHeader.encodedPayload, signature]
     */
    public function signRefundJwt(array $payload, string $password3): array
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $encodedHeader = $this->base64Url(json_encode($header, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $encodedPayload = $this->base64Url(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $dataToSign = $encodedHeader . '.' . $encodedPayload;

        $rawSignature = hash_hmac('sha256', $dataToSign, $password3, true);

        return [$dataToSign, $this->base64Url($rawSignature)];
    }
}
