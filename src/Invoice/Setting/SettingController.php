<?php

declare(strict_types=1);

namespace App\Invoice\Setting;

use App\Auth\Permissions;
use App\Invoice\BaseController;
// App
use App\Invoice\Entity\Setting;
use App\Invoice\EmailTemplate\EmailTemplateRepository as ER;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\PaymentMethod\PaymentMethodRepository as PM;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Helpers\CurrencyHelper;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Helpers\Peppol\PeppolArrays;
use App\Invoice\Helpers\StoreCove\StoreCoveArrays;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Setting\Trait\OpenBankingProviders;
use App\Invoice\Setting\Trait\SettingsTabBootstrap5;
use App\Invoice\TaxRate\TaxRateRepository as TR;
use App\Service\WebControllerService;
use App\User\UserService;
use Ramsey\Uuid\Uuid;
// Yii
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;
use Yiisoft\Db\Mysql\Dsn;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator as FastRouteGenerator;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Security\Random;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;
// Psr
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// Miscellaneous
use DateTimeImmutable;
use DateTimeZone;

final class SettingController extends BaseController
{
    use OpenBankingProviders;
    use SettingsTabBootstrap5;

    protected string $controllerName = 'invoice/setting';

    public function __construct(
        private SettingService $settingService,
        private DataResponseFactoryInterface $factory,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
        $this->settingService = $settingService;
        $this->factory = $factory;
    }

    /**
     * The debug index is simply a list of the settings that are useful to change when debugging and appears in red
     *
     * @param CurrentRoute $currentRoute
     */
    public function debugIndex(
        FastRouteGenerator $urlFastRouteGenerator,
        CurrentRoute $currentRoute,
        Request $request,
        sR $sR,
    ): \Psr\Http\Message\ResponseInterface {

        $pageNum = (int) $currentRoute->getArgument('page', '1');
        $query_params = $request->getQueryParams();
        /**
         * @var string $query_params['page']
         */
        $currentPage = $query_params['page'] ?? $pageNum;
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = (int) $currentPage > 0 ? (int) $currentPage : 1;
        $settings = $this->settings();
        if (isset($query_params['setting_key']) && !empty($query_params['setting_key'])) {
            $settings = $sR->filterSettingKey((string) $query_params['setting_key']);
        }
        if (isset($query_params['setting_value']) && !empty($query_params['setting_value'])) {
            $settings = $sR->filterSettingValue((string) $query_params['setting_value']);
        }
        $parameters = [
            'alert' => $this->alert(),
            'defaultPageSizeOffsetPaginator' => (int) $this->sR->getSetting('default_list_limit'),
            'optionsDataSettingsKeyDropDownFilter' => $this->optionsDataSettingsKey($sR),
            'optionsDataSettingsValueDropDownFilter' => $this->optionsDataSettingsValue($sR),
            'page' => $currentPageNeverZero,
            'settings' => $settings,
            /** @var string $query_params['sort'] */
            'sortString' => $query_params['sort'] ?? '-id, setting_key, setting_value',
            'urlFastRouteGenerator' => $urlFastRouteGenerator,
        ];
        return $this->webViewRenderer->render('debug_index', $parameters);
    }

    // The tab_index is the index of settings showing in non debug mode

    /**
     * @param Request $request
     * @param WebViewRenderer $head
     * @param ER $eR
     * @param GR $gR
     * @param PM $pm
     * @param TR $tR
     * @return Response
     */
    public function tabIndex(
        Request $request,
        WebViewRenderer $head,
        ER $eR,
        GR $gR,
        PM $pm,
        TR $tR,
        #[Query('active')]
        ?string $active = null,
    ): Response {
        $aliases = new Aliases(['@invoice' => dirname(__DIR__),
            '@language' => '@invoice/Language',
            '@icon' => '@invoice/Uploads/Temp']);
        $datehelper = new DateHelper($this->sR);
        $numberhelper = new NumberHelper($this->sR);
        $countries = new CountryHelper();
        $peppol_arrays = new PeppolArrays();
        $languages = $this->sR->localeLanguageArray();
        $body = $request->getParsedBody();
        $p = '//invoice/setting/views/partial_settings_';
        $parameters = [
            'actionName' => 'setting/tabIndex',
            'actionArguments' => [],
            /**
             * Make the 'general' tab active by default unless through an outside urlGenerator query Parameter e.g.
             * http://invoice.myhost/invoice/setting/tab_index?active=mpdf
             * Related logic: see config/common/routes/routes.php Route::methods([Method::GET, Method::POST], '/setting/tab_index[/{active:\d+}]')
             */
            'active' => $active ?? 'front-page',
            'alert' => $this->alert(),
            'head' => $head,
            'body' => $body,
            'font' => $this->sR->getSetting('bootstrap5_layout_invoice_navbar_font')
                ?: 'Arial',
            'fontSize' => (int) $this->sR->getSetting('bootstrap5_layout_invoice_navbar_font_size')
                ?: 16,
            'frontPage' => $this->webViewRenderer->renderPartialAsString($p . 'front_page'),
            'general' => $this->webViewRenderer->renderPartialAsString($p . 'general', [
                /**
                 * @psalm-suppress PossiblyInvalidArgument
                 */
                'languages' => $languages,
                'first_days_of_weeks' => ['0' => $this->sR->lang('sunday'),
                    '1' => $this->sR->lang('monday')],
                'date_formats' => $datehelper->dateFormats(),
                // Used in ClientForm
                'time_zones' => DateTimeZone::listIdentifiers(),
                'countries' => $countries->getCountryList((string) $this->session->get('_language')),
                'gateway_currency_codes' => CurrencyHelper::all(),
                'number_formats' => $this->sR->numberFormats(),
                'current_date' => new \DateTime(),
                'icon' => $aliases->get('@icon'),
            ]),
            'invoices' => $this->webViewRenderer->renderPartialAsString($p . 'invoices', [
                'invoice_groups' => $gR->findAllPreloaded(),
                'payment_methods' => $pm->findAllPreloaded(),
                'public_invoice_templates' => $this->sR->getInvoiceTemplates('public'),
                'pdf_invoice_templates' => $this->sR->getInvoiceTemplates('pdf'),
                'email_templates_invoice' => $eR->repoEmailTemplateType('invoice'),
            ]),
            'quotes' => $this->webViewRenderer->renderPartialAsString($p . 'quotes', [
                'invoice_groups' => $gR->findAllPreloaded(),
                'public_quote_templates' => $this->sR->getQuoteTemplates('public'),
                'pdf_quote_templates' => $this->sR->getQuoteTemplates('pdf'),
                'email_templates_quote' => $eR->repoEmailTemplateType('quote'),
            ]),
            'salesorders' => $this->webViewRenderer->renderPartialAsString($p . 'client_purchase_orders', [
                'gR' => $gR,
            ]),
            'oauth2' => $this->webViewRenderer->renderPartialAsString($p . 'oauth2', [
                'openBankingProviders' => $this->getOpenBankingProvidersWithAuthUrl(),
            ]),
            'taxes' => $this->webViewRenderer->renderPartialAsString($p . 'taxes', [
                'tax_rates' => $tR->findAllPreloaded(),
            ]),
            'email' => $this->webViewRenderer->renderPartialAsString($p . 'email'),
            'google_translate' => $this->webViewRenderer->renderPartialAsString($p . 'google_translate', [
                'locales' => $this->sR->locales(),
            ]),
            'online_payment' => $this->webViewRenderer->renderPartialAsString($p . 'online_payment', [
                'gateway_drivers' => $this->sR->activePaymentGateways(),
                'gateway_currency_codes' => CurrencyHelper::all(),
                'gateway_regions' => $this->sR->amazonRegions(),
                'openBankingProviders' => $this->getOpenBankingProviderNames(),
                'payment_methods' => $pm->findAllPreloaded(),
            ]),
            'mpdf' => $this->webViewRenderer->renderPartialAsString($p . 'mpdf'),
            'mtd' => $this->webViewRenderer->renderPartialAsString($p . 'making_tax_digital'),
            'projects_tasks' => $this->webViewRenderer->renderPartialAsString($p . 'projects_tasks'),
            'vat_registered' => $this->webViewRenderer->renderPartialAsString($p . 'vat_registered'),
            'peppol_electronic_invoicing' => $this->webViewRenderer->renderPartialAsString($p . 'peppol', [
                'config_tax_currency' => $this->sR->getConfigPeppol()['TaxCurrencyCode'] ?: $this->sR->getConfigCompanyDetails()['tax_currency'],
                'gateway_currency_codes' => CurrencyHelper::all(),
                // if delivery/invoice periods are used, a tax point date cannot be determined
                // because goods have not been delivered ie. no date supplied, and no invoice has been issued ie. no date issued/created after the goods have been delivered
                // A stand-in-code or description code 'stands in' or substitutes for how the tax point will be determined/calculated
                // If a stand-in-code exists, it is because a tax point cannot be determined
                // Therefore they are mutually exclusive.
                // They cannot both exist at the same time.
                'stand_in_codes' => $peppol_arrays->getUncl2005subset(),
            ]),
            'storecove' => $this->webViewRenderer->renderPartialAsString($p . 'storecove', [
                'countries' => $countries->getCountryList((string) $this->session->get('_language')),
                'sender_identifier_array' => StoreCoveArrays::storeCoveSenderIdentifierArray(),
            ]),
            'invoiceplane' => $this->webViewRenderer->renderPartialAsString($p . 'invoiceplane', [
                'actionTestConnectionName' => 'import/testconnection',
                'actionTestConnectionArguments' => ['_language' => 'en'],
                'actionImportName' => 'import/invoiceplane',
                'actionImportArguments' => ['_language' => 'en'],
            ]),
            'qrcode' => $this->webViewRenderer->renderPartialAsString($p . 'qr_code', [
            ]),
            'telegram' => $this->webViewRenderer->renderPartialAsString($p . 'telegram', [
            ]),
            // two-factor-authentication
            'tfa' => $this->webViewRenderer->renderPartialAsString($p . 'two_factor_authentication'),
            'bootstrap5' => $this->bootstrap5Partial(),
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if (is_array($body)) {
                $settings = (array) $body['settings'];
                /**
                 * @var string $key
                 * @var string $value
                 */
                foreach ($settings as $key => $value) {
                    $key === 'tax_rate_decimal_places' && (int) $value !== 2 ?
                           $this->tabIndexChangeDecimalColumn((int) $value) : '';
                    // Deal with existing keys after first installation
                    if ($this->sR->repoCount($key) > 0) {
                        // Warn if duplicates
                        if ($this->sR->repoCount($key) > 1) {
                            $this->flashMessage('danger',
                                $this->translator->translate('setting.duplicate.key') . $key);
                            return $this->webService->getRedirectResponse('setting/tabIndex');
                        }
                        if (str_contains($key, 'field_is_password') || str_contains($key, 'field_is_amount')) {
                            // Skip all meta fields
                            continue;
                        }
                        if (isset($settings[$key . '_field_is_password']) && empty($value)) {
                            // Password field, but empty value, let's skip it
                            continue;
                        }
                        if (isset($settings[$key . '_field_is_password']) && $value !== '') {
                            // Encrypt passwords but don't save empty passwords
                            $this->tabIndexSettingsSave($key, (string) $this->sR->encode(trim($value)));
                        } elseif (isset($settings[$key . '_field_is_amount'])) {
                            // Format amount inputs
                            $this->tabIndexSettingsSave($key, (string) $numberhelper->standardizeAmount($value));
                        } else {
                            $this->tabIndexSettingsSave($key, $value);
                        }

                        if (($key == 'number_format') && in_array($value, $this->sR->numberFormats())) {
                            $this->tabIndexNumberFormat($value);
                        }
                    } else {
                            $this->tabIndexDebugModeEnsureAllSettingsIncluded(true, $key, $value);
                        }
                    }
                $this->flashMessage('info', $this->translator->translate('settings.successfully.saved'));
                return $this->webService->getRedirectResponse('setting/tabIndex');
            }
        }
        return $this->webViewRenderer->render('//invoice/setting/tab_index', $parameters);
    }

    /**
     * Related logic: see src\Invoice\Asset\rebuild\js\setting.js
     * Related logic: see resources\views\invoice\setting\views\partial_settings_making_tax_digital.php btn_fph_generate
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function fphgenerate(Request $request): \Psr\Http\Message\ResponseInterface
    {
        $query_params = $request->getQueryParams();
        $randomDeviceIdVersion4 = Uuid::uuid4();
        $deviceId = $randomDeviceIdVersion4->toString();

        $randomUserIdVersion4 = Uuid::uuid4();
        $userUuid = $randomUserIdVersion4->toString();
        return $this->factory->createResponse(Json::encode([
            'success' => 1,
            'userAgent' => $query_params['userAgent'],
            'deviceId' => $deviceId,
            'width' => $query_params['width'],
            'height' => $query_params['height'],
            'scalingFactor' => $query_params['scalingFactor'],
            'colourDepth' => $query_params['colourDepth'],
            'timestamp' =>  new DateTimeImmutable()->getTimestamp(),
            'windowSize' => (string) $query_params['windowInnerWidth']
                . 'x' . (string) $query_params['windowInnerHeight'],
            'userUuid' => $userUuid,
        ]));
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function tabIndexSettingsSave(string $key, string $value): void
    {
        $setting = $this->sR->withKey($key);
        if ($setting) {
            $setting->setSettingValue($value);
            $this->sR->save($setting);
        }
    }

    /**
     * @param int $value
     */
    public function tabIndexChangeDecimalColumn(int $value): void
    {
        $this->tabIndexSettingsSave('tax_rate_decimal_places', (string) $value);
    }

    /**
     * @param string $value
     */
    public function tabIndexNumberFormat(string $value): void
    {
        // Set thousands_separator and decimal_point according to number_format
        $number_formats = $this->sR->numberFormats();
        if (strlen($value) > 0) {
            if (is_array($number_formats[$value])) {
                if ($this->sR->repoCount('decimal_point') == 1) {
                    $this->tabIndexSettingsSave(
                        'decimal_point',
                        (string) $number_formats[$value]['decimal_point'],
                    );
                }
                if ($this->sR->repoCount('thousands_separator') == 1) {
                    $this->tabIndexSettingsSave(
                        'thousands_separator',
                        (string) $number_formats[$value]['thousands_separator'],
                    );
                }
            }
        }
    }

    // This procedure is used in the above procedure to ensure that all
    //  settings are being captured.
    /**
     * @param bool $bool
     * @param string $key
     * @param string $value
     */
    public function tabIndexDebugModeEnsureAllSettingsIncluded(bool $bool,
            string $key, string $value): void
    {
        if ($bool) {
            $setting = new Setting();
            $setting->setSettingKey($key);
            $setting->setSettingValue($value);
            $this->sR->save($setting);
        }
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(Request $request, FormHydrator $formHydrator): Response
    {
        $setting = new Setting();
        $form = new SettingForm($setting);
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'setting/add',
            'actionArguments' => [],
            'alert' => $this->alert(),
            'errors' => [],
            'form' => $form,
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            $key = (string) ($body['setting_key'] ?? '');
            if ($this->sR->repoCount($key) == 1) {
                $this->flashMessage('danger',
                    $this->translator->translate('setting.duplicate.key')
                        . $key);
                return $this->webService->getRedirectResponse('setting/debugIndex');
            }
            /**
             * @psalm-suppress PossiblyInvalidArgument
             */
            if ($formHydrator->populateAndValidate($form, $body)) {
                $this->settingService->saveSetting($setting, $body);
                $this->flashMessage('info', $this->translator->translate('record.successfully.updated'));
                return $this->webService->getRedirectResponse('setting/debugIndex');
            }
            $parameters['form'] = $form;
            $parameters['error'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        }
        return $this->webViewRenderer->render('__form', $parameters);
    }

    /**
     * Use: Toggle between draft invoice has 1. invoice number generated
     *  or 2. no Invoice number generated
     * Route name: setting/draft route action setting/inv_draft_has_number_switch
     * Related logic: see /config/common/routes.php
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function invDraftHasNumberSwitch(CurrentRoute $currentRoute): Response
    {
        $setting = $this->setting($currentRoute);
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

    /**
     * Purpose: A warning is given if this setting is ON during production
     * Use: Toggle between 1. On (Development) or
     *  2. Off (Production) on the flash message under invoice/index
     * Location: Settings ... View ... Invoices ... Other Settings
     *  ... Mark invoices as sent when copying an invoice
     * Route name: setting/mark_sent route action setting/mark_sent
     * Related logic: see /config/common/routes.php
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function markSent(CurrentRoute $currentRoute): Response
    {
        $setting = $this->setting($currentRoute);
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

    /**
     * Purpose: Save the users toggle button choice on flash message:
     *  'Assign a client to a user automatically after signup'
     * Related logic:
     *  see App\Widget\Button static function setOrUnsetAssignClientToUserAutomatically
     * Related logic: see src\Invoice\UserInv\UserInvController function signup
     * @return Response
     */
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
            if ($setting->getSettingValue() == '0') {
                $setting->setSettingValue('1');
                $this->sR->save($setting);
                return $this->webService->getRedirectResponse($origin . '/index');
            }
            if ($setting->getSettingValue() == '1') {
                $setting->setSettingValue('0');
                $this->sR->save($setting);
                return $this->webService->getRedirectResponse($origin . '/index');
            }
            $setting->setSettingValue('0');
            $this->sR->save($setting);
            return $this->webService->getRedirectResponse($origin . '/index');
        }
        $new_setting = new Setting();
        $new_setting->setSettingKey('columns_all_visible');
        $this->sR->save($new_setting);

        return $this->webService->getRedirectResponse($origin . '/index');
    }

    /**
     * Used in: inv/index. User clicks on button to unhide column with invsentlogs table
     * @return Response
     */
    public function unhideOrHideToggleInvSentLogColumn(): Response
    {
        $setting = $this->sR->withKey('column_inv_sent_log_visible');
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
            $setting->setSettingValue('0');
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
        $setting = $this->setting($currentRoute);
        $origin = $currentRoute->getArgument('origin') ?? 'inv';
        $limit = $currentRoute->getArgument('limit');
        if ($setting) {
            $setting->setSettingValue((string) $limit);
            $this->sR->save($setting);
        }
        return $this->webService->getRedirectResponse($origin !== 'setting' ? $origin . '/index' : 'setting/debugIndex');
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
    ): Response {
        $setting = $this->setting($currentRoute);
        if ($setting) {
            $form = new SettingForm($setting);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'setting/edit',
                'actionArguments' => ['setting_id' => $setting->getSettingId()],
                'alert' => $this->alert(),
                'form' => $form,
                'errors' => [],
            ];
            if ($request->getMethod() === Method::POST) {
                /**
                 * @psalm-suppress PossiblyInvalidArgument
                 */
                if ($formHydrator->populateAndValidate($form, $request->getParsedBody())) {
                    $body = $request->getParsedBody();
                    /**
                     * @psalm-suppress PossiblyInvalidArgument
                     */
                    $this->settingService->saveSetting($setting, $body);
                    $this->flashMessage('info', $this->translator->translate('record.successfully.updated'));
                    return $this->webService->getRedirectResponse('setting/debugIndex');
                }
                $parameters['form'] = $form;
                $parameters['error'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            }
            return $this->webViewRenderer->render('__form', $parameters);
        }
        return $this->webService->getRedirectResponse('setting/debugIndex');
    }

    /**
     * @return true
     */
    public function true(): bool
    {
        return true;
    }

    /**
     * @return false
     */
    public function false(): bool
    {
        return false;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute): Response
    {
        $setting = $this->setting($currentRoute);
        if ($setting) {
            $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));
            $this->settingService->deleteSetting($setting);
        }
        return $this->webService->getRedirectResponse('setting/debugIndex');
    }

    /**
     * @param CurrentRoute $currentRoute
     */
    public function view(CurrentRoute $currentRoute): \Psr\Http\Message\ResponseInterface
    {
        $setting = $this->setting($currentRoute);
        if ($setting) {
            $form = new SettingForm($setting);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'setting/view',
                'actionArguments' => ['setting_id' => $setting->getSettingId()],
                'setting' => $setting,
                'form' => $form,
            ];
            return $this->webViewRenderer->render('__view', $parameters);
        }
        return $this->webService->getRedirectResponse('setting/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @return Setting|null
     */
    private function setting(
        CurrentRoute $currentRoute,
    ): ?Setting {
        $setting_id = $currentRoute->getArgument('setting_id');
        if (null !== $setting_id) {
            return $this->sR->repoSettingquery($setting_id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function settings(): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $this->sR->findAllPreloaded();
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getCronKey(): \Psr\Http\Message\ResponseInterface
    {
        $parameters = [
            'success' => 1,
            'cronkey' => Random::string(32),
        ];
        return $this->factory->createResponse(Json::encode($parameters));
    }

    public function optionsDataSettingsKey(sR $sR): array
    {
        $optionsDataSettings = [];
        $settings = $sR->findAllPreloaded();
        /**
         * @var Setting $setting
         */
        foreach ($settings as $setting) {
            $settingKey = $setting->getSettingKey();
            // Remove repeats
            if (!in_array($setting->getSettingKey(), $optionsDataSettings)) {
                $optionsDataSettings[$settingKey] = $setting->getSettingKey();
            }
        }
        return $optionsDataSettings;
    }

    public function optionsDataSettingsValue(sR $sR): array
    {
        $optionsDataSettings = [];
        $settings = $sR->findAllPreloaded();
        /**
         * @var Setting $setting
         */
        foreach ($settings as $setting) {
            $settingValue = $setting->getSettingValue();
            // Remove repeats
            if (!in_array($setting->getSettingValue(), $optionsDataSettings)) {
                $optionsDataSettings[$settingValue] = $setting->getSettingValue();
            }
        }
        return $optionsDataSettings;
    }
}
