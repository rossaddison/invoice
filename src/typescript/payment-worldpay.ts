/**
 * Worldpay Access Checkout SDK + 3DS Payment Module
 *
 * Reads server-supplied values from a hidden #worldpay-payment-config
 * <div> rendered by payment_information_worldpay_pci.php (data-*
 * attributes), initialises the Access Checkout SDK's hosted card
 * fields, and orchestrates the multi-step exchange with this app's own
 * backend (create payment -> maybe 3DS device data -> maybe 3DS
 * challenge) — genuinely new territory for this codebase (see
 * WorldpayPaymentController's own docblock): every other gateway here
 * either hands off to the provider's own hosted page/Drop-in entirely,
 * or does a single client-side tokenize-then-submit step. Worldpay
 * needs real AJAX round-trips with this app's own backend mid-payment.
 *
 * Ground-truthed 2026-08-23 against Worldpay's own official reference
 * implementation (github.com/Worldpay/Worldpay-Ecommerce, the Magento
 * EmbeddedCheckout plugin v1.1.5, including its bundled
 * `worldpay/access-worldpay-php` SDK) — read directly for research
 * purposes only, not installed as a dependency (different platform,
 * PHP/Magento not Yii3), same as every other secondhand-SDK reference
 * this app's gateways have used. This resolved every previously
 * unconfirmed piece:
 *
 * - The session call is `checkout.generateSessionState()`, not
 *   `generateSessions()` — a single combined `paymentSession` string
 *   (not separate card/cvv sessions), which maps directly onto
 *   `instruction.paymentInstrument.sessionHref` server-side with no
 *   transformation (confirmed from the SDK's own
 *   PaymentsApiPayloadBuilder).
 * - The DDC form needs a `Bin` field (capital B) alongside `JWT` —
 *   confirmed from the SDK's own `Forms\DeviceDataCollection::render()`.
 * - The DDC `postMessage` payload is a JSON *string* (needs
 *   `JSON.parse`), shaped `{MessageType: "profile.completed", Status:
 *   true, SessionId: "..."}` — confirmed from the reference's own
 *   `handleMessage()`. `SessionId` is the `collectionReference` value.
 * - The challenge form needs an `MD` field alongside `JWT` (Worldpay's
 *   SDK defaults it to `''` when there's no merchant-specific
 *   correlation data to carry — used here the same way).
 * - `event.origin` should be validated against the DDC url's own
 *   origin before trusting a message — the reference validates against
 *   a configured `thirdPartyAppUrl` for the same reason.
 */

import { postJson } from './utils.js';

// ---------------------------------------------------------------------------
// Ambient declarations for the Access Checkout Web SDK
// ---------------------------------------------------------------------------
declare global {
    interface WorldpayCheckoutNamespace {
        checkout: {
            init(
                config: {
                    id: string;
                    form: string;
                    fields: { pan: string; expiry: string; cvv: string };
                    environment?: string;
                },
                callback?: (error: unknown) => void,
            ): void;
            generateSessionState(
                callback: (error: unknown, paymentSession: string | undefined) => void,
            ): void;
        };
    }
    // eslint-disable-next-line no-var
    var Worldpay: WorldpayCheckoutNamespace;
}

interface WorldpayDeviceDataCollection {
    bin: string;
    jwt: string;
    url: string;
}

interface WorldpayChallenge {
    reference?: string;
    url?: string;
    jwt?: string;
    payload?: string;
}

interface WorldpayCreatePaymentResponse {
    outcome: string;
    deviceDataCollection: WorldpayDeviceDataCollection | null;
    message?: string;
}

interface WorldpaySupply3dsResponse {
    outcome: string;
    challenge: WorldpayChallenge | null;
    message?: string;
}

// ---------------------------------------------------------------------------
// Exported initialiser — called once from index.ts on DOMContentLoaded
// ---------------------------------------------------------------------------
export function initWorldpayPayment(): void {
    const configEl = document.getElementById('worldpay-payment-config');
    if (!configEl) return; // not a Worldpay payment page

    const checkoutId = configEl.dataset.checkoutId ?? '';
    const environment = configEl.dataset.environment ?? 'test';
    const createPaymentUrl = configEl.dataset.createPaymentUrl ?? '';
    const supply3dsUrl = configEl.dataset.supply3dsDeviceDataUrl ?? '';
    const completeUrl = configEl.dataset.completeUrl ?? '';
    if (!checkoutId || !createPaymentUrl) return;

    const form = document.getElementById('worldpay-payment-form') as HTMLFormElement | null;
    if (!form) return;

    globalThis.Worldpay.checkout.init(
        {
            id: checkoutId,
            form: '#worldpay-payment-form',
            fields: { pan: '#worldpay-pan', expiry: '#worldpay-expiry', cvv: '#worldpay-cvv' },
            environment,
        },
        (error) => {
            if (error) {
                showError('Unable to load the payment form.');
                console.error('Worldpay checkout.init error:', error);
            }
        },
    );

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        handleSubmit(form, createPaymentUrl, supply3dsUrl, completeUrl);
    });
}

function csrfToken(): string {
    return (document.querySelector('input[name="_csrf"]') as HTMLInputElement | null)?.value ?? '';
}

function showError(message: string): void {
    const el = document.getElementById('worldpay-error');
    if (el) el.textContent = message;
}

function handleSubmit(
    form: HTMLFormElement,
    createPaymentUrl: string,
    supply3dsUrl: string,
    completeUrl: string,
): void {
    showError('');
    globalThis.Worldpay.checkout.generateSessionState((error, paymentSession) => {
        if (error || !paymentSession) {
            showError('Unable to validate card details.');
            console.error('Worldpay generateSessionState error:', error);
            return;
        }

        const cardHolderName = (form.elements.namedItem('cardHolderName') as HTMLInputElement | null)?.value ?? '';

        void postJson<WorldpayCreatePaymentResponse>(
            createPaymentUrl,
            { sessionHref: paymentSession, cardHolderName },
            csrfToken(),
        )
            .then((response) => handleCreatePaymentResponse(response, supply3dsUrl, completeUrl))
            .catch((err: unknown) => {
                showError('Unable to process payment. Please try again.');
                console.error('Worldpay createPayment failed:', err);
            });
    });
}

function handleCreatePaymentResponse(
    response: WorldpayCreatePaymentResponse,
    supply3dsUrl: string,
    completeUrl: string,
): void {
    if (response.outcome === '3dsDeviceDataRequired' && response.deviceDataCollection) {
        void runDeviceDataCollection(response.deviceDataCollection, supply3dsUrl, completeUrl);
        return;
    }
    if (response.outcome === 'error') {
        showError(response.message ?? 'Payment could not be processed.');
        return;
    }
    // authorized / sentForSettlement / refused / fraudHighRisk — nothing
    // further for the browser to do; the webhook is authoritative for
    // whether the invoice is actually marked paid (see
    // WorldpayWebhookHandler). Always land on the same read-only status
    // page regardless of outcome, matching every other gateway here.
    globalThis.location.href = completeUrl;
}

/**
 * Device Data Collection: a hidden form posts `Bin`+`JWT` to
 * `deviceDataCollection.url` inside a hidden iframe (Cardinal
 * Commerce's own DDC endpoint), which — once fingerprinting completes
 * — posts a message directly to `window.parent`. Confirmed shape and
 * mechanics from Worldpay's own reference implementation; see this
 * file's own header docblock.
 */
function runDeviceDataCollection(
    ddc: WorldpayDeviceDataCollection,
    supply3dsUrl: string,
    completeUrl: string,
): Promise<void> {
    return new Promise((resolve) => {
        const expectedOrigin = safeOrigin(ddc.url);
        const iframeName = 'worldpay-ddc-iframe';
        const iframe = document.createElement('iframe');
        iframe.name = iframeName;
        iframe.style.display = 'none';
        document.body.appendChild(iframe);

        const messageHandler = (event: MessageEvent): void => {
            if (expectedOrigin && event.origin !== expectedOrigin) return;

            const collectionReference = extractCollectionReference(event.data);
            if (!collectionReference) return;

            globalThis.removeEventListener('message', messageHandler);
            iframe.remove();

            void postJson<WorldpaySupply3dsResponse>(
                supply3dsUrl,
                { collectionReference },
                csrfToken(),
            )
                .then((response) => handleSupply3dsResponse(response, completeUrl))
                .catch((err: unknown) => {
                    showError('Unable to process payment. Please try again.');
                    console.error('Worldpay supply3dsDeviceData failed:', err);
                })
                .finally(() => resolve());
        };
        globalThis.addEventListener('message', messageHandler);

        const ddcForm = document.createElement('form');
        ddcForm.method = 'POST';
        ddcForm.action = ddc.url;
        ddcForm.target = iframeName;
        appendHiddenField(ddcForm, 'Bin', ddc.bin);
        appendHiddenField(ddcForm, 'JWT', ddc.jwt);
        document.body.appendChild(ddcForm);
        ddcForm.submit();
        ddcForm.remove();

        // Device data collection failing/timing out is not fatal — per
        // Worldpay's own docs, a payment can still be attempted without
        // it, just with a higher chance of a challenge/authentication
        // failure. 10s matches the reference implementation's own timeout.
        setTimeout(() => {
            globalThis.removeEventListener('message', messageHandler);
            iframe.remove();
            void postJson<WorldpaySupply3dsResponse>(supply3dsUrl, { collectionReference: '' }, csrfToken())
                .then((response) => handleSupply3dsResponse(response, completeUrl))
                .catch(() => showError('Unable to process payment. Please try again.'))
                .finally(() => resolve());
        }, 10000);
    });
}

function safeOrigin(url: string): string {
    try {
        return new URL(url).origin;
    } catch {
        return '';
    }
}

function appendHiddenField(form: HTMLFormElement, name: string, value: string): void {
    const field = document.createElement('input');
    field.type = 'hidden';
    field.name = name;
    field.value = value;
    form.appendChild(field);
}

/**
 * Confirmed shape from Worldpay's own reference implementation:
 * `event.data` is a JSON *string* (not an already-parsed object),
 * `{MessageType: "profile.completed", Status: true, SessionId: "..."}`.
 * Any other MessageType/Status, or malformed JSON, is not a result
 * worth acting on yet.
 */
function extractCollectionReference(data: unknown): string {
    if (typeof data !== 'string' || data.length === 0) return '';

    let parsed: unknown;
    try {
        parsed = JSON.parse(data);
    } catch {
        return '';
    }
    if (!parsed || typeof parsed !== 'object') return '';

    const message = parsed as { MessageType?: unknown; Status?: unknown; SessionId?: unknown };
    if (message.MessageType !== 'profile.completed' || message.Status !== true) return '';
    return typeof message.SessionId === 'string' ? message.SessionId : '';
}

function handleSupply3dsResponse(response: WorldpaySupply3dsResponse, completeUrl: string): void {
    if (response.outcome === '3dsChallenged' && response.challenge?.url && response.challenge.jwt) {
        runChallenge(response.challenge, completeUrl);
        return;
    }
    if (response.outcome === 'error') {
        showError(response.message ?? 'Payment could not be processed.');
        return;
    }
    globalThis.location.href = completeUrl;
}

/**
 * Renders the issuer's real challenge UI in a visible iframe — same
 * hidden-form-POST mechanic as Device Data Collection, confirmed field
 * names (`JWT` + `MD`) from Worldpay's own reference implementation's
 * `Forms\Challenge::render()`. `MD` is left empty — this app has no
 * merchant-specific correlation data of its own to carry through the
 * ACS round-trip beyond what's already in the JWT.
 *
 * After the customer completes it, the issuer's ACS is expected to
 * navigate/POST back to this app's own `threeDS.challenge.returnUrl`
 * (worldpayComplete) — a genuine page navigation the backend handles
 * server-side (see WorldpayPaymentController::worldpayComplete()), not
 * a postMessage this script listens for. If that navigation happens to
 * land inside the iframe rather than breaking out to the top-level
 * page (Worldpay's own reference relays this via a custom DOM event
 * from a server-rendered bridge page — a Magento-specific mechanism
 * this app doesn't replicate), worldpayComplete's own page includes a
 * small top-level redirect for exactly that case — see
 * initWorldpayCompleteEscape() below.
 */
function runChallenge(challenge: WorldpayChallenge, completeUrl: string): void {
    const container = document.getElementById('worldpay-3ds-container');
    if (!container || !challenge.url || !challenge.jwt) {
        globalThis.location.href = completeUrl;
        return;
    }

    container.classList.remove('d-none');
    container.innerHTML = '';

    const size = challengeWindowSize(challenge.payload);
    const iframeName = 'worldpay-challenge-iframe';
    const iframe = document.createElement('iframe');
    iframe.name = iframeName;
    iframe.style.width = size.width;
    iframe.style.height = size.height;
    iframe.style.border = '0';
    container.appendChild(iframe);

    const challengeForm = document.createElement('form');
    challengeForm.method = 'POST';
    challengeForm.action = challenge.url;
    challengeForm.target = iframeName;
    appendHiddenField(challengeForm, 'JWT', challenge.jwt);
    appendHiddenField(challengeForm, 'MD', '');
    document.body.appendChild(challengeForm);
    challengeForm.submit();
    challengeForm.remove();
}

/**
 * `challenge.payload` is a base64-encoded JSON CReq carrying (among
 * other things) the issuer's own recommended `challengeWindowSize`
 * code ('01'-'05') — confirmed mapping straight from Worldpay's own
 * reference implementation's `ChallengeWindowSize::$challengeWindowSizeMapping`,
 * the same 5 sizes the request-side `windowSize` enum already offers.
 * Falls back to Worldpay's own documented default (390x400) if the
 * payload is missing, malformed, or carries an unrecognised code.
 */
function challengeWindowSize(payload: string | undefined): { width: string; height: string } {
    const sizes: Record<string, { width: string; height: string }> = {
        '01': { width: '250px', height: '400px' },
        '02': { width: '390px', height: '400px' },
        '03': { width: '500px', height: '600px' },
        '04': { width: '600px', height: '400px' },
        '05': { width: '100%', height: '100%' },
    };
    const fallback = sizes['02'];
    if (!payload) return fallback;

    try {
        const decoded = JSON.parse(atob(payload)) as { challengeWindowSize?: unknown };
        const code = typeof decoded.challengeWindowSize === 'string' ? decoded.challengeWindowSize : '';
        return sizes[code] ?? fallback;
    } catch {
        return fallback;
    }
}

/**
 * Escapes an iframe onto the top-level page if worldpayComplete's own
 * page happens to have loaded inside the challenge iframe (see
 * runChallenge()'s own docblock — not confirmed either way against a
 * real challenge). No-op when this page is already top-level, which is
 * the expected case for every non-Worldpay gateway and for
 * worldpayComplete itself outside of an active challenge.
 */
export function initWorldpayCompleteEscape(): void {
    if (!document.getElementById('worldpay-payment-config') && globalThis.self !== globalThis.top) {
        try {
            if (globalThis.top) {
                globalThis.top.location.href = globalThis.self.location.href;
            }
        } catch {
            // Cross-origin frame — nothing this script can do; the customer
            // still sees a completed challenge, just inside the iframe.
        }
    }
}
