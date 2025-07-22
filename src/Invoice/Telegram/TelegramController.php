<?php

declare(strict_types=1);

namespace App\Invoice\Telegram;

use App\Invoice\BaseController;
use App\Invoice\Helpers\Telegram\TelegramHelper;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use App\Widget\Button;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Vjik\TelegramBot\Api\FailResult;
use Vjik\TelegramBot\Api\ParseResult\TelegramParseResultException;
use Vjik\TelegramBot\Api\TelegramBotApi;
use Vjik\TelegramBot\Api\Type\Update\Update;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Json\Json;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class TelegramController extends BaseController
{
    /**
     * Note: Yiisoft\Di\NotFoundException can occur if $factory is placed after $telegramBotApi i.e. in the wrong order
     * Related logic: see https://github.com/rossaddison/invoice/issues/41
     */
    public function __construct(
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        private DataResponseFactoryInterface $factory,
        private Logger $logger,
        private ?Update $update,
        private ?TelegramBotApi $telegramBotApi,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->factory = $factory;
        $this->logger = $logger;
        $this->update = $update;
        $this->telegramBotApi = $telegramBotApi;
    }

    public function index(Request $request, UrlGenerator $urlGenerator): \Yiisoft\DataResponse\DataResponse|Response
    {
        $settingRepositoryTelegramToken = $this->sR->getSetting('telegram_token');
        $chatId = $this->sR->getSetting('telegram_chat_id');
        $ipAddress = null;
        $maxConnections = null;
        $allowUpdates = null;
        $dropPendingUpdates = null;
        $messageThreadId = null;
        $parseMode = null;
        $entities = null;
        $linkPreviewOptions = null;
        $disableNotification = null;
        $protectContent = null;
        $messageEffectId = null;
        $replyParameters = null;
        $replyMarkup = null;
        $allowPaidBroadcast = null;
        $secretToken = $this->sR->getSetting('telegram_webhook_secret_token') ?: null;
        try {
            $telegramEnabled = $this->sR->getSetting('enable_telegram');
            if ($telegramEnabled == '1') {
                if (strlen($settingRepositoryTelegramToken) > 1) {
                    $telegramHelper = new TelegramHelper(
                        $settingRepositoryTelegramToken,
                        $this->logger,
                    );
                    $this->telegramBotApi = $telegramHelper->getBotApi();
                    // FailResult|true
                    $failResult = $telegramHelper->setWebhook(
                        $urlGenerator,
                        $ipAddress,
                        $maxConnections,
                        $allowUpdates,
                        $dropPendingUpdates,
                        $secretToken,
                    );
                    if (strlen($chatId) > 1) {
                        if (!$failResult instanceof FailResult) {
                            $user = $this->telegramBotApi->getMe();
                            if (($user instanceof \Vjik\TelegramBot\Api\Type\User) &&
                                ($this->sR->getSetting('telegram_test_message_use') == '1')) {
                                $text = $this->translator->translate('telegram.bot.api.hello.world.test.message');
                                $sendMessageResult = $this->telegramBotApi->sendMessage(
                                    $chatId,
                                    $text,
                                    $businessConnectionId = null,
                                    $messageThreadId = null,
                                    $parseMode = null,
                                    $entities = null,
                                    $linkPreviewOptions = null,
                                    $disableNotification = null,
                                    $protectContent = null,
                                    $messageEffectId = null,
                                    $replyParameters = null,
                                    $replyMarkup = null,
                                    $allowPaidBroadcast = null,
                                );
                                if (!$sendMessageResult instanceof FailResult) {
                                    $this->flashMessage('success', $this->translator->translate('telegram.bot.api.hello.world.test.message.sent'));
                                } else {
                                    $this->flashMessage('danger', $this->translator->translate('telegram.bot.api.hello.world.test.message.sent.not'));
                                    if (null !== $sendMessageResult->description) {
                                        $this->flashMessage('primary', 'Fail Result: ' . $sendMessageResult->description);
                                    }
                                    if (null !== $sendMessageResult->errorCode) {
                                        $match = match ($sendMessageResult->errorCode) {
                                            403 => 'Solution: 1. Send a Message to Your Bot: Open Telegram and search for your bot by its username.' .
                                                   'Start a chat with your bot and send any message to it. 2. Open your browser and enter the following URL,' .
                                                   ' replacing YOUR_BOT_TOKEN with your bot token: https://api.telegram.org/botYOUR_BOT_TOKEN/getUpdates' . Button::deleteWebhook($urlGenerator, $this->translator),
                                            409 => Button::deleteWebhook($urlGenerator, $this->translator),
                                            default => $sendMessageResult->description ?? '',
                                        };
                                        $this->flashMessage('primary', 'Fail Result: ' . (string) $sendMessageResult->errorCode . ' ' . $match);
                                    }
                                    $this->webService->getRedirectResponse('setting/tab_index');
                                }
                            }
                        } else {
                            if (null !== $failResult->description) {
                                $this->flashMessage('primary', 'Fail Result: ' . $failResult->description);
                            }
                            if (null !== $failResult->errorCode) {
                                $this->flashMessage('primary', 'Fail Result: ' . (string) $failResult->errorCode);
                            }
                        }
                    } else {
                        $this->flashMessage('danger', $this->translator->translate('telegram.bot.api.chat.id.not.set'));
                    }
                } else {
                    $this->flashMessage('danger', $this->translator->translate('telegram.bot.api.token.not.set'));
                    $this->webService->getRedirectResponse('setting/tab_index');
                }
            } else {
                $this->flashMessage('danger', $this->translator->translate('telegram.bot.api.enabled.not'));
                $this->webService->getRedirectResponse('setting/tab_index');
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->viewRenderer->render('index', $parameters = [
            'alert' => $this->alert(),
        ]);
    }

    /**
     * Note: This function has not been tested and is still under development
     * @param Request $request
     * @param string $secret_token
     * @param string $jsonString
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function webhook(
        Request $request,
        #[RouteArgument('secret_token')]
        string $secret_token,
        #[RouteArgument('jsonString')]
        string $jsonString,
    ): \Yiisoft\DataResponse\DataResponse {
        $settingRepositoryTelegramToken = $this->sR->getSetting('telegram_token');
        $settingRepositoryTelegramSecretToken = $this->sR->getSetting('telegram_secret_token');
        try {
            if (strlen($settingRepositoryTelegramToken) > 1 && (strlen($settingRepositoryTelegramSecretToken) > 1)) {
                if ($settingRepositoryTelegramSecretToken === $secret_token) {
                    $telegramHelper = new TelegramHelper(
                        $settingRepositoryTelegramToken,
                        $this->logger,
                    );
                    /** @throws TelegramParseResultException */
                    $update = $telegramHelper::decodeJsonEncodedUpdatePushedToWebhookFromTelegramApi($jsonString, $this->logger);
                    return $this->factory->createResponse(Json::encode($update));
                }
            } else {
                $this->logger->warning($this->translator->translate('telegram.bot.api.token.not.set'));
                return $this->factory->createResponse(Json::encode(['fail' => $this->translator->translate('telegram.bot.api.token.not.set')]));
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
        }
        return $this->factory->createResponse(Json::encode(['fali' => true]));
    }

    /**
     * Note: Tested and functional
     * @param Request $request
     * @param UrlGenerator $urlGenerator
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function get_webhookinfo(Request $request, UrlGenerator $urlGenerator): \Yiisoft\DataResponse\DataResponse|Response
    {
        $settingRepositoryTelegramToken = $this->sR->getSetting('telegram_token');
        $failResultWebhookInfo = '';
        try {
            $telegramEnabled = $this->sR->getSetting('enable_telegram');
            if ($telegramEnabled == '1') {
                if (strlen($settingRepositoryTelegramToken) > 1) {
                    $telegramHelper = new TelegramHelper(
                        $settingRepositoryTelegramToken,
                        $this->logger,
                    );
                    $failResultWebhookInfo = $telegramHelper->getWebhookInfo();
                } else {
                    $this->flashMessage('danger', $this->translator->translate('telegram.bot.api.token.not.set'));
                    $this->webService->getRedirectResponse('setting/tab_index');
                }
            } else {
                $this->flashMessage('danger', $this->translator->translate('telegram.bot.api.enabled.not'));
                $this->webService->getRedirectResponse('setting/tab_index');
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->viewRenderer->render('getwebhookinfo', $parameters = [
            'alert' => $this->alert(),
            'webhookinfo' => $failResultWebhookInfo,
        ]);
    }

    /**
     * Note: Tested and functional
     * @param Request $request
     * @param UrlGenerator $urlGenerator
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function set_webhook(Request $request, UrlGenerator $urlGenerator): \Yiisoft\DataResponse\DataResponse|Response
    {
        $settingRepositoryTelegramToken = $this->sR->getSetting('telegram_token');
        $failResultWebhookInfo = '';
        try {
            $telegramEnabled = $this->sR->getSetting('enable_telegram');
            if ($telegramEnabled == '1') {
                if (strlen($settingRepositoryTelegramToken) > 1) {
                    $telegramHelper = new TelegramHelper(
                        $settingRepositoryTelegramToken,
                        $this->logger,
                    );
                    $failResultSetWebhook = $telegramHelper->setWebhook(
                        $urlGenerator,
                        $ipAddress = null,
                        $maxConnections = null,
                        $allowUpdates = null,
                        $dropPendingUpdates = false,
                        $secretToken = null,
                    );
                    $failResultWebhookInfo = $telegramHelper->getWebhookInfo();
                    if (!$failResultSetWebhook instanceof FailResult) {
                        $this->flashMessage('success', $this->translator->translate('telegram.bot.api.webhook.setup'));
                    } else {
                        $this->flashMessage('success', $this->translator->translate('telegram.bot.api.webhook.setup.already'));
                        if (null !== $failResultSetWebhook->description) {
                            $this->flashMessage('primary', 'Fail Result: ' . $failResultSetWebhook->description);
                        }
                        if (null !== $failResultSetWebhook->errorCode) {
                            $this->flashMessage('primary', 'Fail Result: ' . (string) $failResultSetWebhook->errorCode);
                        }
                    }
                } else {
                    $this->flashMessage('danger', $this->translator->translate('telegram.bot.api.token.not.set'));
                    $this->webService->getRedirectResponse('setting/tab_index');
                }
            } else {
                $this->flashMessage('danger', $this->translator->translate('telegram.bot.api.enabled.not'));
                $this->webService->getRedirectResponse('setting/tab_index');
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->viewRenderer->render('setwebhook', $parameters = [
            'alert' => $this->alert(),
            'webhookinfo' => $failResultWebhookInfo,
        ]);
    }

    /**
     * Note: Tested and functional
     * @param Request $request
     * @param UrlGenerator $urlGenerator
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function delete_webhook(Request $request, UrlGenerator $urlGenerator): \Yiisoft\DataResponse\DataResponse|Response
    {
        $settingRepositoryTelegramToken = $this->sR->getSetting('telegram_token');
        try {
            $telegramEnabled = $this->sR->getSetting('enable_telegram');
            if ($telegramEnabled == '1') {
                if (strlen($settingRepositoryTelegramToken) > 1) {
                    $telegramHelper = new TelegramHelper(
                        $settingRepositoryTelegramToken,
                        $this->logger,
                    );
                    $this->telegramBotApi = $telegramHelper->getBotApi();
                    // ensure any existing Webhook is deleted so that can receive updates e.g.
                    // messages sent from telegram users so that we can build up a database of chat_id's that we can send a test message to
                    $failResult = $telegramHelper->deleteWebhook();
                    if (!$failResult instanceof FailResult) {
                        $this->flashMessage('success', $this->translator->translate('telegram.bot.api.webhook.deleted'));
                    } else {
                        if (null !== $failResult->description) {
                            $this->flashMessage('primary', 'Fail Result: ' . $failResult->description);
                        }
                        if (null !== $failResult->errorCode) {
                            $this->flashMessage('primary', 'Fail Result: ' . (string) $failResult->errorCode);
                        }
                    }
                } else {
                    $this->flashMessage('danger', $this->translator->translate('telegram.bot.api.token.not.set'));
                    $this->webService->getRedirectResponse('setting/tab_index');
                }
            } else {
                $this->flashMessage('danger', $this->translator->translate('telegram.bot.api.enabled.not'));
                $this->webService->getRedirectResponse('setting/tab_index');
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->viewRenderer->render('index', $parameters = [
            'alert' => $this->alert(),
        ]);
    }

    /**
     * Note: Tested and functional
     * @param Request $request
     * @param UrlGenerator $urlGenerator
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function get_updates(Request $request, UrlGenerator $urlGenerator): \Yiisoft\DataResponse\DataResponse|Response
    {
        $settingRepositoryTelegramToken = $this->sR->getSetting('telegram_token');
        $offset = null;
        $limit = null;
        $timeout = null;
        $allowedUpdates = null;
        $failResultUpdates = [];
        try {
            $telegramEnabled = $this->sR->getSetting('enable_telegram');
            if ($telegramEnabled == '1') {
                if (strlen($settingRepositoryTelegramToken) > 1) {
                    $telegramHelper = new TelegramHelper(
                        $settingRepositoryTelegramToken,
                        $this->logger,
                    );
                    $this->telegramBotApi = $telegramHelper->getBotApi();
                    $failResult = $telegramHelper->deleteWebhook();
                    if (!$failResult instanceof FailResult) {
                        $this->flashMessage('success', $this->translator->translate('telegram.bot.api.webhook.deleted'));
                    } else {
                        if (null !== $failResult->description) {
                            $this->flashMessage('primary', 'Fail Result: ' . $failResult->description);
                        }
                        if (null !== $failResult->errorCode) {
                            $this->flashMessage('primary', 'Fail Result: ' . (string) $failResult->errorCode);
                        }
                    }
                    $failResultUpdates = $this->telegramBotApi->getUpdates($offset, $limit, $timeout, $allowedUpdates);
                    if (!$failResultUpdates instanceof FailResult) {
                        $this->flashMessage('success', $this->translator->translate('telegram.bot.api.get.updates.success'));
                    } else {
                        $this->flashMessage('danger', $this->translator->translate('telegram.bot.api.get.updates.danger'));
                        if (null !== $failResultUpdates->description) {
                            $this->flashMessage('primary', 'Fail Result: ' . $failResultUpdates->description);
                        }
                        if (null !== $failResultUpdates->errorCode) {
                            $this->flashMessage('primary', 'Fail Result: ' . (string) $failResultUpdates->errorCode);
                        }
                    }
                } else {
                    $this->flashMessage('danger', $this->translator->translate('telegram.bot.api.token.not.set'));
                    $this->webService->getRedirectResponse('setting/tab_index');
                }
            } else {
                $this->flashMessage('danger', $this->translator->translate('telegram.bot.api.enabled.not'));
                $this->webService->getRedirectResponse('setting/tab_index');
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->viewRenderer->render('updates', $parameters = [
            'alert' => $this->alert(),
            'updates' => $failResultUpdates,
        ]);
    }
}
