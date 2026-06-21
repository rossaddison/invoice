<?php

declare(strict_types=1);

namespace App\Invoice\Setting;

use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\BaseController;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Handles boolean-toggle and listlimit setting actions.
 * Extracted from SettingController to satisfy S1448 (≤20 methods per class).
 */
final class SettingToggleController extends BaseController
{
    protected string $controllerName = 'invoice/setting';

    public function __construct(
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
    }

    public function invDraftHasNumberSwitch(CurrentRoute $currentRoute): Response
    {
        return $this->toggleSettingToInvIndex($currentRoute);
    }

    public function markSent(CurrentRoute $currentRoute): Response
    {
        return $this->toggleSettingToInvIndex($currentRoute);
    }

    public function autoClient(): Response
    {
        $setting = $this->sR->withKey('signup_automatically_assign_client');
        if ($setting) {
            if ($setting->getSettingValue() == '0') {
                $setting->setSettingValue('1');
                $this->sR->save($setting);
                return $this->webService->getRedirectResponse('site/index');
            }
            if ($setting->getSettingValue() == '1') {
                $setting->setSettingValue('0');
                $this->sR->save($setting);
                return $this->webService->getRedirectResponse('site/index');
            }
        }
        return $this->webService->getRedirectResponse('site/index');
    }

    public function visible(#[RouteArgument('origin')] string $origin): Response
    {
        $setting = $this->sR->withKey('columns_all_visible');
        if ($setting) {
            $setting->setSettingValue($setting->getSettingValue() === '0' ? '1' : '0');
            $this->sR->save($setting);
            return $this->webService->getRedirectResponse($origin . '/index');
        }
        $new_setting = new Setting();
        $new_setting->setSettingKey('columns_all_visible');
        $this->sR->save($new_setting);
        return $this->webService->getRedirectResponse($origin . '/index');
    }

    public function unhideOrHideToggleInvSentLogColumn(): Response
    {
        $setting = $this->sR->withKey('column_inv_sent_log_visible');
        if ($setting) {
            $setting->setSettingValue($setting->getSettingValue() === '0' ? '1' : '0');
            $this->sR->save($setting);
            return $this->webService->getRedirectResponse('inv/index');
        }
        $new_setting = new Setting();
        $new_setting->setSettingKey('column_inv_sent_log_visible');
        $this->sR->save($new_setting);
        return $this->webService->getRedirectResponse('inv/index');
    }

    public function listlimit(CurrentRoute $currentRoute): Response
    {
        $setting = $this->sR->repoSettingquery((int) $currentRoute->getArgument('setting_id'));
        $origin = $currentRoute->getArgument('origin') ?? 'inv';
        $limit = $currentRoute->getArgument('limit');
        if ($setting) {
            $setting->setSettingValue((string) $limit);
            $this->sR->save($setting);
        }
        return $this->webService->getRedirectResponse($origin !== 'setting' ? $origin . '/index' : 'setting/debugIndex');
    }

    private function toggleSettingToInvIndex(CurrentRoute $currentRoute): Response
    {
        $setting = $this->sR->repoSettingquery((int) $currentRoute->getArgument('setting_id'));
        if ($setting) {
            if ($setting->getSettingValue() == '0') {
                $setting->setSettingValue('1');
                $this->sR->save($setting);
                return $this->webService->getRedirectResponse('inv/index');
            }
            if ($setting->getSettingValue() == '1') {
                $setting->setSettingValue('0');
                $this->sR->save($setting);
                return $this->webService->getRedirectResponse('inv/index');
            }
        }
        return $this->webService->getRedirectResponse('inv/index');
    }
}
