<?php

declare(strict_types=1);

namespace App\Invoice\Telegram;

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
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class TelegramController
{
    public function __construct(
        private ViewRenderer $viewRenderer,
        private WebControllerService $webService,
        private UserService $userService,
        private Session $session,
        private Flash $flash,
        private TranslatorInterface $translator,
        private Logger $logger,
        private sR $sR,
        private ?Update $update = null,
        private ?TelegramBotApi $telegramBotApi = null,
        private DataResponseFactoryInterface $factory    
    ) {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/telegram')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->translator = $translator;
        $this->logger = $logger;
        $this->sR = $sR;
        $this->update = $update;
        $this->telegramBotApi = $telegramBotApi;
        $this->factory = $factory;
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
        $secretToken = $this->sR->getSetting('telegram_webhook_secret_token') ?: null;
        try {
            $telegramEnabled = $this->sR->getSetting('enable_telegram');
            if ($telegramEnabled == '1') {
                if (strlen($settingRepositoryTelegramToken) > 1) {
                    $telegramHelper = new TelegramHelper(
                        $settingRepositoryTelegramToken,
                        $this->logger
                    );
                    $this->telegramBotApi = $telegramHelper->getBotApi();
                    // FailResult|true
                    $failResult = $telegramHelper->setWebhook(
                        $urlGenerator,
                        $ipAddress,
                        $maxConnections,
                        $allowUpdates,
                        $dropPendingUpdates,
                        $secretToken
                    );
                    if (strlen($chatId) > 1) {
                        if (!$failResult instanceof \Vjik\TelegramBot\Api\FailResult) {
                            $user = $this->telegramBotApi->getMe();
                            if (($user instanceof \Vjik\TelegramBot\Api\Type\User) &&
                                ($this->sR->getSetting('telegram_test_message_use') == '1')) {
                                $text = $this->translator->translate('invoice.invoice.telegram.bot.api.hello.world.test.message');
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
                                );
                                if (!$sendMessageResult instanceof FailResult) {
                                    $this->flashMessage('success', $this->translator->translate('invoice.invoice.telegram.bot.api.hello.world.test.message.sent'));
                                } else {
                                    $this->flashMessage('danger', $this->translator->translate('invoice.invoice.telegram.bot.api.hello.world.test.message.sent.not'));
                                    if (null !== $sendMessageResult->description) {
                                        $this->flashMessage('primary', 'Fail Result: ' . $sendMessageResult->description);
                                    }
                                    if (null !== $sendMessageResult->errorCode) {
                                        $match = match ($sendMessageResult->errorCode) {
                                            403 => 'Solution: 1. Send a Message to Your Bot: Open Telegram and search for your bot by its username.' .
                                                   'Start a chat with your bot and send any message to it. 2. Open your browser and enter the following URL,'.
                                                   ' replacing YOUR_BOT_TOKEN with your bot token: https://api.telegram.org/botYOUR_BOT_TOKEN/getUpdates'. Button::deleteWebhook($urlGenerator, $this->translator),
                                            409 => Button::deleteWebhook($urlGenerator, $this->translator),
                                            default => null !== $sendMessageResult->description ? $sendMessageResult->description : '',
                                        };
                                        $this->flashMessage('primary', 'Fail Result: ' . (string)$sendMessageResult->errorCode.' '.$match);
                                    }
                                    $this->webService->getRedirectResponse('setting/tab_index');
                                }
                            }
                        } else {
                            if (null !== $failResult->description) {
                                $this->flashMessage('primary', 'Fail Result: ' . $failResult->description);
                            }
                            if (null !== $failResult->errorCode) {
                                $this->flashMessage('primary', 'Fail Result: ' . (string)$failResult->errorCode);
                            }
                        }
                    } else {
                        $this->flashMessage('danger', $this->translator->translate('invoice.invoice.telegram.bot.api.chat.id.not.set'));
                    }
                } else {
                    $this->flashMessage('danger', $this->translator->translate('invoice.invoice.telegram.bot.api.token.not.set'));
                    $this->webService->getRedirectResponse('setting/tab_index');
                }
            } else {
                $this->flashMessage('danger', $this->translator->translate('invoice.invoice.telegram.bot.api.enabled.not'));
                $this->webService->getRedirectResponse('setting/tab_index');
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->viewRenderer->render('index', $parameters = [
            'alert' => $this->alert()
        ]);
    }
        
    public function webhook(Request $request, 
            #[RouteArgument('secret_token')] string $secret_token, 
            #[RouteArgument('jsonString')] string $jsonString) : \Yiisoft\DataResponse\DataResponse
    {
        $settingRepositoryTelegramToken = $this->sR->getSetting('telegram_token');
        $settingRepositoryTelegramSecretToken = $this->sR->getSetting('telegram_secret_token');
        try {
            if (strlen($settingRepositoryTelegramToken) > 1 && (strlen($settingRepositoryTelegramSecretToken) > 1)) {
                if ($settingRepositoryTelegramSecretToken === $secret_token) {
                    $telegramHelper = new TelegramHelper(
                        $settingRepositoryTelegramToken,
                        $this->logger
                    );
                    /** @throws TelegramParseResultException */
                    $update = $telegramHelper::decodeJsonEncodedUpdatePushedToWebhookFromTelegramApi($jsonString, $this->logger);
                    return $this->factory->createResponse(Json::encode($update));
                }    
            } else {
                $this->logger->warning($this->translator->translate('invoice.invoice.telegram.bot.api.token.not.set'));
                return $this->factory->createResponse(Json::encode(['fail' => $this->translator->translate('invoice.invoice.telegram.bot.api.token.not.set')]));
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
        }
        return $this->factory->createResponse(Json::encode(['fali' => true]));
    }

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
                        $this->logger
                    );
                    $failResultWebhookInfo = $telegramHelper->getWebhookInfo();
                } else {
                    $this->flashMessage('danger', $this->translator->translate('invoice.invoice.telegram.bot.api.token.not.set'));
                    $this->webService->getRedirectResponse('setting/tab_index');
                }
            } else {
                $this->flashMessage('danger', $this->translator->translate('invoice.invoice.telegram.bot.api.enabled.not'));
                $this->webService->getRedirectResponse('setting/tab_index');
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->viewRenderer->render('getwebhookinfo', $parameters = [
            'alert' => $this->alert(),
            'webhookinfo' => $failResultWebhookInfo
        ]);
    }
    
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
                        $this->logger
                    );
                    $failResultSetWebhook = $telegramHelper->setWebhook(
                        $urlGenerator,
                        $ipAddress = null,
                        $maxConnections = null,
                        $allowUpdates = null,
                        $dropPendingUpdates = false,
                        $secretToken = null
                    );
                    $failResultWebhookInfo = $telegramHelper->getWebhookInfo();
                    if (!$failResultSetWebhook instanceof \Vjik\TelegramBot\Api\FailResult) {
                        $this->flashMessage('success', $this->translator->translate('invoice.invoice.telegram.bot.api.webhook.setup'));
                    } else {
                        $this->flashMessage('success', $this->translator->translate('invoice.invoice.telegram.bot.api.webhook.setup.already'));
                        if (null !== $failResultSetWebhook->description) {
                            $this->flashMessage('primary', 'Fail Result: ' . $failResultSetWebhook->description);
                        }
                        if (null !== $failResultSetWebhook->errorCode) {
                            $this->flashMessage('primary', 'Fail Result: ' . (string)$failResultSetWebhook->errorCode);
                        }
                    }
                } else {
                    $this->flashMessage('danger', $this->translator->translate('invoice.invoice.telegram.bot.api.token.not.set'));
                    $this->webService->getRedirectResponse('setting/tab_index');
                }
            } else {
                $this->flashMessage('danger', $this->translator->translate('invoice.invoice.telegram.bot.api.enabled.not'));
                $this->webService->getRedirectResponse('setting/tab_index');
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->viewRenderer->render('setwebhook', $parameters = [
            'alert' => $this->alert(),
            'webhookinfo' => $failResultWebhookInfo
        ]);
    }

    public function delete_webhook(Request $request, UrlGenerator $urlGenerator): \Yiisoft\DataResponse\DataResponse|Response
    {
        $settingRepositoryTelegramToken = $this->sR->getSetting('telegram_token');
        try {
            $telegramEnabled = $this->sR->getSetting('enable_telegram');
            if ($telegramEnabled == '1') {
                if (strlen($settingRepositoryTelegramToken) > 1) {
                    $telegramHelper = new TelegramHelper(
                        $settingRepositoryTelegramToken,
                        $this->logger
                    );
                    $this->telegramBotApi = $telegramHelper->getBotApi();
                    // ensure any existing Webhook is deleted so that can receive updates e.g.
                    // messages sent from telegram users so that we can build up a database of chat_id's that we can send a test message to
                    $failResult = $telegramHelper->deleteWebhook();
                    if (!$failResult instanceof \Vjik\TelegramBot\Api\FailResult) {
                        $this->flashMessage('success', $this->translator->translate('invoice.invoice.telegram.bot.api.webhook.deleted'));
                    } else {
                        if (null !== $failResult->description) {
                            $this->flashMessage('primary', 'Fail Result: ' . $failResult->description);
                        }
                        if (null !== $failResult->errorCode) {
                            $this->flashMessage('primary', 'Fail Result: ' . (string)$failResult->errorCode);
                        }
                    }
                } else {
                    $this->flashMessage('danger', $this->translator->translate('invoice.invoice.telegram.bot.api.token.not.set'));
                    $this->webService->getRedirectResponse('setting/tab_index');
                }
            } else {
                $this->flashMessage('danger', $this->translator->translate('invoice.invoice.telegram.bot.api.enabled.not'));
                $this->webService->getRedirectResponse('setting/tab_index');
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->viewRenderer->render('index', $parameters = [
            'alert' => $this->alert()
        ]);
    }

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
                        $this->logger
                    );
                    $this->telegramBotApi = $telegramHelper->getBotApi();
                    $failResult = $telegramHelper->deleteWebhook();
                    if (!$failResult instanceof \Vjik\TelegramBot\Api\FailResult) {
                        $this->flashMessage('success', $this->translator->translate('invoice.invoice.telegram.bot.api.webhook.deleted'));
                    } else {
                        if (null !== $failResult->description) {
                            $this->flashMessage('primary', 'Fail Result: ' . $failResult->description);
                        }
                        if (null !== $failResult->errorCode) {
                            $this->flashMessage('primary', 'Fail Result: ' . (string)$failResult->errorCode);
                        }
                    }
                    $failResultUpdates = $this->telegramBotApi->getUpdates($offset, $limit, $timeout, $allowedUpdates);
                    if (!$failResultUpdates instanceof \Vjik\TelegramBot\Api\FailResult) {
                        $this->flashMessage('success', $this->translator->translate('invoice.invoice.telegram.bot.api.get.updates.success'));
                    } else {
                        $this->flashMessage('danger', $this->translator->translate('invoice.invoice.telegram.bot.api.get.updates.danger'));
                        if (null !== $failResultUpdates->description) {
                            $this->flashMessage('primary', 'Fail Result: ' . $failResultUpdates->description);
                        }
                        if (null !== $failResultUpdates->errorCode) {
                            $this->flashMessage('primary', 'Fail Result: ' . (string)$failResultUpdates->errorCode);
                        }
                    }
                } else {
                    $this->flashMessage('danger', $this->translator->translate('invoice.invoice.telegram.bot.api.token.not.set'));
                    $this->webService->getRedirectResponse('setting/tab_index');
                }
            } else {
                $this->flashMessage('danger', $this->translator->translate('invoice.invoice.telegram.bot.api.enabled.not'));
                $this->webService->getRedirectResponse('setting/tab_index');
            }
        } catch (TelegramParseResultException $e) {
            $this->logger->warning($e->getMessage());
            $this->flashMessage('secondary', $e->getMessage());
        }
        return $this->viewRenderer->render('updates', $parameters = [
            'alert' => $this->alert(),
            'updates' => $failResultUpdates
        ]);
    }

    /**
     * @return string
     */
    private function alert(): string
    {
        return $this->viewRenderer->renderPartialAsString(
            '//invoice/layout/alert',
            [
        'flash' => $this->flash,
      ]
        );
    }

    /**
     * @param string $level
     * @param string $message
     * @return Flash|null
     */
    private function flashMessage(string $level, string $message): Flash|null
    {
        if (strlen($message) > 0) {
            $this->flash->add($level, $message, true);
            return $this->flash;
        }
        return null;
    }
}
