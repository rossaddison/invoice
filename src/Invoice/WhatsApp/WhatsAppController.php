<?php

declare(strict_types=1);

namespace App\Invoice\WhatsApp;

use App\Invoice\BaseController;
use App\Invoice\Helpers\WhatsApp\WhatsAppService;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface as Logger;
use Yiisoft\DataResponse\ResponseFactory\PlainTextResponseFactory;
use Yiisoft\Http\Method;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class WhatsAppController extends BaseController
{
    protected string $controllerName = 'invoice/whatsapp';

    public function __construct(
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
        private readonly Logger $logger,
        private readonly WhatsAppService $whatsAppService,
        private readonly PlainTextResponseFactory $plainTextFactory,
    ) {
        parent::__construct($webService, $userService, $translator,
                $webViewRenderer, $session, $sR, $flash);
    }

    /**
     * Sends a test message to the number configured in
     * whatsapp_test_recipient_number, using the configured template — see
     * partial_settings_whatsapp.php step 6. That number must be manually
     * added and verified in the Meta dashboard first if using Meta's free
     * test number (WhatsApp restricts test-mode sending to pre-approved
     * recipients).
     */
    public function index(): Response
    {
        $configError = $this->whatsAppConfigError();
        if ($configError !== null) {
            $this->flashMessage('danger', $this->translator->translate($configError));
        } else {
            $this->sendTestMessage();
        }
        return $this->webViewRenderer->render('index', [
            'alert' => $this->alert(),
        ]);
    }

    /**
     * Meta requires a GET verification handshake (echoing hub_challenge
     * back) before a webhook subscription can even be saved in their
     * dashboard — implemented here since it's simple and a prerequisite for
     * receiving anything at all. Actually processing incoming POST events
     * (delivery statuses, replies) isn't built yet — see
     * partial_settings_whatsapp.php step 7; this just logs and
     * acknowledges for now.
     *
     * IMPORTANT: the route for this action must NOT have auth middleware so
     * Meta's servers can reach it. Secured by whatsapp_webhook_verify_token
     * only (GET) — there's no per-request signature to check on POST yet.
     */
    public function webhook(Request $request): Response
    {
        if ($request->getMethod() === Method::GET) {
            return $this->verifyWebhookSubscription($request);
        }

        $this->logger->info(
            'WhatsApp webhook received (not yet processed — logging only).',
            ['body' => $request->getBody()->getContents()],
        );
        return $this->plainTextFactory->createResponse('EVENT_RECEIVED');
    }

    private function verifyWebhookSubscription(Request $request): Response
    {
        $params = $request->getQueryParams();
        $mode = (string) ($params['hub_mode'] ?? '');
        $token = (string) ($params['hub_verify_token'] ?? '');
        $challenge = (string) ($params['hub_challenge'] ?? '');
        $configuredToken = $this->sR->getSetting('whatsapp_webhook_verify_token');

        if ($mode === 'subscribe' && $configuredToken !== '' && $token === $configuredToken) {
            return $this->plainTextFactory->createResponse($challenge);
        }

        $this->logger->warning('WhatsApp webhook: verification token mismatch.');
        return $this->plainTextFactory->createResponse('verification failed')->withStatus(403);
    }

    private function whatsAppConfigError(): ?string
    {
        if ($this->sR->getSetting('enable_whatsapp') !== '1') {
            return 'whatsapp.business.cloud.api.enabled.not';
        }
        if (!$this->whatsAppService->isConfigured()) {
            return 'whatsapp.business.cloud.api.not.configured';
        }
        return null;
    }

    private function sendTestMessage(): void
    {
        $testNumber = $this->sR->getSetting('whatsapp_test_recipient_number');
        if ($testNumber === '') {
            $this->flashMessage('warning',
                $this->translator->translate('whatsapp.business.cloud.api.test.recipient.not.set'));
            return;
        }

        $result = $this->whatsAppService->sendTemplateMessage($testNumber);
        if ($result->sent) {
            $this->flashMessage('success',
                $this->translator->translate('whatsapp.business.cloud.api.test.message.sent'));
            return;
        }

        $this->flashMessage('danger',
            $this->translator->translate('whatsapp.business.cloud.api.test.message.sent.not'));
        if ($result->error !== null) {
            $this->flashMessage('primary', $result->error);
        }
    }
}
