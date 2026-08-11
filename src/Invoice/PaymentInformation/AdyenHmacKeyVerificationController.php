<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Invoice\BaseController;
use App\Invoice\PaymentInformation\Service\AdyenPaymentService;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * A single settings-page action: computes the currently-configured Adyen
 * webhook HMAC key's own KCV (Key Check Value) and flashes it back to the
 * user, so it can be compared by eye against the KCV Adyen's own Customer
 * Area displays for that webhook — the exact manual check that diagnosed
 * a real live incident where a correctly-formatted but wrong key (copied
 * but never saved on Adyen's side) silently broke every webhook. See
 * AdyenPaymentService::hmacKeyKcv() and
 * docs/ADYEN_WEBHOOK_HMAC_KEY_NOT_SAVED_AUGUST_2026.md.
 *
 * Deliberately its own tiny controller, mirroring
 * ImportController::testConnection()'s exact shape (compute → flash →
 * redirect back to the settings tab), rather than growing
 * SettingController further (php:S1448, ≤20 methods/class).
 */
final class AdyenHmacKeyVerificationController extends BaseController
{
    protected string $controllerName = 'invoice/paymentinformation';

    public function __construct(
        private readonly AdyenPaymentService $adyenPaymentService,
        WebControllerService $webService,
        UserService $userService,
        TranslatorInterface $translator,
        WebViewRenderer $webViewRenderer,
        SessionInterface $session,
        sR $sR,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
    }

    public function verifyHmacKey(): Response
    {
        $kcv = $this->adyenPaymentService->hmacKeyKcv();

        if (null === $kcv) {
            $this->flashMessage(
                'warning',
                $this->translator->translate('online.payment.adyen.hmac.kcv.not.configured'),
            );
        } else {
            $this->flashMessage(
                'info',
                sprintf($this->translator->translate('online.payment.adyen.hmac.kcv.result'), $kcv),
            );
        }

        return $this->webService->getRedirectResponse('setting/tabIndex', [], ['active' => 'online-payment']);
    }
}
