<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\Setting\SettingRepository as SR;
use App\Service\WebControllerService;
use App\User\UserService;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class QuoteControllerBaseDeps
{
    public function __construct(
        public readonly WebControllerService $webService,
        public readonly UserService $userService,
        public readonly TranslatorInterface $translator,
        public readonly WebViewRenderer $webViewRenderer,
        public readonly SessionInterface $session,
        public readonly SR $sR,
        public readonly Flash $flash,
    ) {
    }
}
