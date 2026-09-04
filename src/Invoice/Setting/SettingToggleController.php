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

    private const string INDEX_SUFFIX = '/index';

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
            return $this->webService->getRedirectResponse($origin . self::INDEX_SUFFIX);
        }
        $new_setting = new Setting();
        $new_setting->setSettingKey('columns_all_visible');
        $this->sR->save($new_setting);
        return $this->webService->getRedirectResponse($origin . self::INDEX_SUFFIX);
    }

    /**
     * One shared setting ('grid_sticky_header'), not one per grid: toggled
     * here from the navbar's gear dropdown (next to the page-size picker,
     * same immediate-save-no-page-reload UX -- see that dropdown's own
     * hx-get + hx-swap="none" pattern in resources/views/layout/
     * invoice.php) rather than added to Settings > Invoices as its own
     * form field, applies uniformly to every grid that supports it
     * (Invoice/Quote/SalesOrder/Product all read the same key -- see
     * InvsListWidget::withGridDisplayOptions()'s docblock for the
     * pattern each *ListWidget follows).
     */
    public function gridStickyHeader(#[RouteArgument('origin')] string $origin): Response
    {
        return $this->toggleBooleanSettingCreatingAtOne('grid_sticky_header', $origin);
    }

    /**
     * 'bootstrap5_layout_invoice_navbar_sticky' -- pulled out of
     * Settings > Bootstrap5 (resources/views/invoice/setting/views/
     * bootstrap5/partial_navbar_invoice.php no longer has this field)
     * and placed adjacent to gridStickyHeader()'s own toggle in the
     * navbar's gear dropdown instead, same immediate-save UX. The
     * *read* side is unchanged -- LayoutViewInjection::
     * resolveBootstrapSettings() still feeds this key straight into
     * resources/views/layout/invoice.php's $bootstrap5LayoutInvoiceNavbarSticky,
     * which is what actually applies the sticky-top class; only the
     * form field this setting used to live behind moved.
     */
    public function navbarSticky(#[RouteArgument('origin')] string $origin): Response
    {
        return $this->toggleBooleanSettingCreatingAtOne('bootstrap5_layout_invoice_navbar_sticky', $origin);
    }

    /**
     * Shared by gridStickyHeader()/navbarSticky() -- CI's real SonarCloud
     * gate flagged the two as duplicated code before this extraction
     * (new_duplicated_lines_density over its 3% threshold), identical
     * apart from the setting key. Deliberately NOT also used by the
     * pre-existing visible() above, despite the very similar shape: that
     * method's own create-branch leaves a fresh Setting's value at
     * whatever the entity's own default is rather than explicitly
     * setting '1' -- a real behavioural difference, not just a smaller
     * duplicate, and not this PR's code to change.
     */
    private function toggleBooleanSettingCreatingAtOne(string $key, string $origin): Response
    {
        $setting = $this->sR->withKey($key);
        if ($setting) {
            $setting->setSettingValue($setting->getSettingValue() === '0' ? '1' : '0');
            $this->sR->save($setting);
            return $this->webService->getRedirectResponse($origin . self::INDEX_SUFFIX);
        }
        $new_setting = new Setting();
        $new_setting->setSettingKey($key);
        $new_setting->setSettingValue('1');
        $this->sR->save($new_setting);
        return $this->webService->getRedirectResponse($origin . self::INDEX_SUFFIX);
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
        return $this->webService->getRedirectResponse($origin !== 'setting' ? $origin . self::INDEX_SUFFIX : 'setting/debugIndex');
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
