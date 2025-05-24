<?php

declare(strict_types=1);

namespace App\Invoice\Setting;

use App\Invoice\BaseController;
// App
use App\Invoice\Entity\Setting;
use App\Invoice\EmailTemplate\EmailTemplateRepository as ER;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Libraries\Crypt;
use App\Invoice\PaymentMethod\PaymentMethodRepository as PM;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Helpers\CurrencyHelper;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Helpers\Peppol\PeppolArrays;
use App\Invoice\Helpers\StoreCove\StoreCoveArrays;
use App\Invoice\Libraries\Sumex;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\TaxRate\TaxRateRepository as TR;
//use App\Invoice\Libraries\Sumex;
use App\Service\WebControllerService;
use App\User\UserService;
use Ramsey\Uuid\Uuid;
// Yii
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;
use Yiisoft\Db\Mysql\Dsn;
use Yiisoft\Files\FileHelper;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Security\Random;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
// Psr
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// Miscellaneous
use DateTimeImmutable;
use DateTimeZone;

final class SettingController extends BaseController
{
    protected string $controllerName = 'invoice/setting';

    public function __construct(
        private SettingService $settingService,
        private DataResponseFactoryInterface $factory,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR);
        $this->settingService = $settingService;
        $this->factory = $factory;
    }

    /**
     * The debug index is simply a list of the settings that are useful to change when debugging and appears in red
     *
     * @param CurrentRoute $currentRoute
     */
    public function debug_index(CurrentRoute $currentRoute): \Yiisoft\DataResponse\DataResponse
    {
        $pageNum = (int)$currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $pageNum > 0 ? $pageNum : 1;
        $paginator = (new OffsetPaginator($this->settings()))
        ->withPageSize($this->sR->positiveListLimit())
        ->withCurrentPage($currentPageNeverZero);
        $canEdit = $this->rbac();
        $parameters = [
            'paginator' => $paginator,
            'alert' => $this->alert(),
            'canEdit' => $canEdit,
            'settings' => $this->settings(),
            'session' => $this->session,
        ];
        return $this->viewRenderer->render('debug_index', $parameters);
    }

    // The tab_index is the index of settings showing in non debug mode

    /**
     * @param Request $request
     * @param ViewRenderer $head
     * @param ER $eR
     * @param GR $gR
     * @param PM $pm
     * @param TR $tR
     * @return Response
     */
    public function tab_index(
        Request $request,
        ViewRenderer $head,
        ER $eR,
        GR $gR,
        PM $pm,
        TR $tR,
        #[Query('active')] string $active = null,
    ): Response {
        $aliases = new Aliases(['@invoice' => dirname(__DIR__),
            '@language' => '@invoice/Language',
            '@icon' => '@invoice/Uploads/Temp']);
        $datehelper = new DateHelper($this->sR);
        $numberhelper = new NumberHelper($this->sR);
        $countries = new CountryHelper();
        $crypt = new Crypt();
        $peppol_arrays = new PeppolArrays();
        $languages = $this->sR->locale_language_array();
        $body = $request->getParsedBody();
        $parameters = [
            'actionName' => 'setting/tab_index',
            'actionArguments' => [],
            /**
             * Make the 'general' tab active by default unless through an outside urlGenerator query Parameter e.g.
             * http://invoice.myhost/invoice/setting/tab_index?active=mpdf
             * @see config/common/routes/routes.php Route::methods([Method::GET, Method::POST], '/setting/tab_index[/{active:\d+}]')
             */
            'active' => $active ?? 'front-page',
            'alert' => $this->alert(),
            'head' => $head,
            'body' => $body,
            'frontPage' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_front_page'),
            'general' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_general', [
                /**
                 * @psalm-suppress PossiblyInvalidArgument
                 */
                'languages' => $languages,
                'first_days_of_weeks' => ['0' => $this->sR->lang('i.sunday'), '1' => $this->sR->lang('i.monday')],
                'date_formats' => $datehelper->date_formats(),
                // Used in ClientForm
                'time_zones' => DateTimeZone::listIdentifiers(),
                'countries' => $countries->get_country_list((string)$this->session->get('_language')),
                'gateway_currency_codes' => CurrencyHelper::all(),
                'number_formats' => $this->sR->number_formats(),
                'current_date' => new \DateTime(),
                'icon' => $aliases->get('@icon'),
            ]),
            'invoices' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_invoices', [
                'invoice_groups' => $gR->findAllPreloaded(),
                'payment_methods' => $pm->findAllPreloaded(),
                'public_invoice_templates' => $this->sR->get_invoice_templates('public'),
                'pdf_invoice_templates' => $this->sR->get_invoice_templates('pdf'),
                'email_templates_invoice' => $eR->repoEmailTemplateType('invoice'),
                'roles' => Sumex::ROLES,
                'places' => Sumex::PLACES,
                'cantons' => Sumex::CANTONS,
            ]),
            'quotes' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_quotes', [
                'invoice_groups' => $gR->findAllPreloaded(),
                'public_quote_templates' => $this->sR->get_quote_templates('public'),
                'pdf_quote_templates' => $this->sR->get_quote_templates('pdf'),
                'email_templates_quote' => $eR->repoEmailTemplateType('quote'),
            ]),
            'salesorders' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_client_purchase_orders', [
                'gR' => $gR,
            ]),
            'oauth2' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_oauth2'),
            'taxes' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_taxes', [
                'tax_rates' => $tR->findAllPreloaded(),
            ]),
            'email' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_email'),
            'google_translate' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_google_translate', [
                'locales' => $this->sR->locales(),
            ]),
            'online_payment' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_online_payment', [
                'gateway_drivers' => $this->sR->active_payment_gateways(),
                'gateway_currency_codes' => CurrencyHelper::all(),
                'gateway_regions' => $this->sR->amazon_regions(),
                'payment_methods' => $pm->findAllPreloaded(),
                'crypt' => $crypt,
            ]),
            'mpdf' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_mpdf'),
            'mtd' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_making_tax_digital'),
            'projects_tasks' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_projects_tasks'),
            'vat_registered' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_vat_registered'),
            'peppol_electronic_invoicing' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_peppol', [
                'config_tax_currency' => $this->sR->get_config_peppol()['TaxCurrencyCode'] ?: $this->sR->get_config_company_details()['tax_currency'],
                'gateway_currency_codes' => CurrencyHelper::all(),
                // if delivery/invoice periods are used, a tax point date cannot be determined
                // because goods have not been delivered ie. no date supplied, and no invoice has been issued ie. no date issued/created after the goods have been delivered
                // A stand-in-code or description code 'stands in' or substitutes for how the tax point will be determined/calculated
                // If a stand-in-code exists, it is because a tax point cannot be determined
                // Therefore they are mutually exclusive.
                // They cannot both exist at the same time.
                'stand_in_codes' => $peppol_arrays->getUncl2005subset(),
                // use crypt to decode the store cove api key
                'crypt' => $crypt,
            ]),
            'storecove' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_storecove', [
                'countries' => $countries->get_country_list((string)$this->session->get('_language')),
                'sender_identifier_array' => StoreCoveArrays::store_cove_sender_identifier_array(),
            ]),
            'invoiceplane' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_invoiceplane', [
                'actionTestConnectionName' => 'import/testconnection',
                'actionTestConnectionArguments' => ['_language' => 'en'],
                'actionImportName' => 'import/invoiceplane',
                'actionImportArguments' => ['_language' => 'en'],
            ]),
            'qrcode' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_qr_code', [
            ]),
            'telegram' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_telegram', [
            ]),
            'bootstrap5' => $this->viewRenderer->renderPartialAsString('//invoice/setting/views/partial_settings_bootstrap5', [
                'alertMessageFontSize' => '10',
                'alertCloseButtonFontSize' => '10',
            ]),
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if (is_array($body)) {
                $settings = (array)$body['settings'];
                /**
                 * @var string $key
                 * @var string $value
                 */
                foreach ($settings as $key => $value) {
                    $key === 'tax_rate_decimal_places' && (int)$value !== 2 ? $this->tab_index_change_decimal_column((int)$value) : '';
                    // Deal with existing keys after first installation
                    if ($this->sR->repoCount($key) > 0) {
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
                            $this->tab_index_settings_save($key, (string)$crypt->encode(trim($value)));
                        } elseif (isset($settings[$key . '_field_is_amount'])) {
                            // Format amount inputs
                            $this->tab_index_settings_save($key, (string)$numberhelper->standardize_amount($value));
                        } else {
                            $this->tab_index_settings_save($key, $value);
                        }

                        if ($key == 'number_format') {
                            // Set thousands_separator and decimal_point according to number_format
                            // Derive the 'decimal_point' and 'thousands_separator' setting from the chosen ..number format eg. 1000,000.00 if it has a value
                            $this->tab_index_number_format($value);
                        }
                    } else {
                        // The key does not exist because the repoCount is not greater than zero => add
                        // Note:
                        // The settings 'decimal_point' and 'thousands_separator' which are derived from number_format array
                        // and were installed on the first run in InvoiceController
                        // will be derived automatically => their repoCount will be greater than zero and will not cause this to run
                        $this->tab_index_debug_mode_ensure_all_settings_included(true, $key, $value);
                    }
                }
                $this->flashMessage('info', $this->translator->translate('i.settings_successfully_saved'));
                return $this->webService->getRedirectResponse('setting/tab_index');
            }
        }
        return $this->viewRenderer->render('tab_index', $parameters);
    }

    /**
     * @see src\Invoice\Asset\rebuild\js\setting.js
     * @see resources\views\invoice\setting\views\partial_settings_making_tax_digital.php btn_fph_generate
     * @param Request $request
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function fphgenerate(Request $request): \Yiisoft\DataResponse\DataResponse
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
            'timestamp' => (new DateTimeImmutable())->getTimestamp(),
            'windowSize' => (string)$query_params['windowInnerWidth'] . 'x' . (string)$query_params['windowInnerHeight'],
            'userUuid' => $userUuid,
        ]));
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function tab_index_settings_save(string $key, string $value): void
    {
        $setting = $this->sR->withKey($key);
        if ($setting) {
            $setting->setSetting_value($value);
            $this->sR->save($setting);
        }
    }

    /**
     * @param int $value
     */
    public function tab_index_change_decimal_column(int $value): void
    {
        // Change the decimal column dynamically using cycle and the Fragment command. SyncTable has been commented out from config/params.php
        // TODO
    }

    /**
     * @param string $value
     */
    public function tab_index_number_format(string $value): void
    {
        // Set thousands_separator and decimal_point according to number_format
        $number_formats = $this->sR->number_formats();
        if ($this->sR->repoCount('decimal_point') > 0) {
            $this->tab_index_settings_save(
                'decimal_point',
                $number_formats[$value]['decimal_point'] ?? '.'
            );
        }
        if ($this->sR->repoCount('thousands_separator') > 0) {
            $this->tab_index_settings_save(
                'thousands_separator',
                $number_formats[$value]['thousands_separator'] ?? ','
            );
        }
    }

    // This procedure is used in the above procedure to ensure that all settings are being captured.
    /**
     * @param bool $bool
     * @param string $key
     * @param string $value
     */
    public function tab_index_debug_mode_ensure_all_settings_included(bool $bool, string $key, string $value): void
    {
        // The setting does not exist because repoCount is not greater than 0;
        if ($bool) {
            // Make sure the setting is available to be set in the database if there is no such like setting in the database
            $setting = new Setting();
            $setting->setSetting_key($key);
            $setting->setSetting_value($value);
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
            'title' => $this->translator->translate('i.add'),
            'actionName' => 'setting/add',
            'actionArguments' => [],
            'alert' => $this->alert(),
            'errors' => [],
            'form' => $form,
        ];
        if ($request->getMethod() === Method::POST) {
            /**
             * @psalm-suppress PossiblyInvalidArgument
             */
            if ($formHydrator->populateAndValidate($form, $request->getParsedBody())) {
                $this->settingService->saveSetting($setting, $request->getParsedBody());
                $this->flashMessage('info', $this->translator->translate('i.record_successfully_updated'));
                return $this->webService->getRedirectResponse('setting/debug_index');
            }
            $parameters['form'] = $form;
            $parameters['error'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        }
        return $this->viewRenderer->render('__form', $parameters);
    }

    /**
     * Use: Toggle between draft invoice has 1. invoice number generated or 2. no Invoice number generated
     * Route name: setting/draft route action setting/inv_draft_has_number_switch
     * @see /config/common/routes.php
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function inv_draft_has_number_switch(CurrentRoute $currentRoute): Response
    {
        $setting = $this->setting($currentRoute);
        if ($setting) {
            if ($setting->getSetting_value() == '0') {
                $setting->setSetting_value('1');
                $this->sR->save($setting);
                return $this->webService->getRedirectResponse('inv/index');
            }
            if ($setting->getSetting_value() == '1') {
                $setting->setSetting_value('0');
                $this->sR->save($setting);
                return $this->webService->getRedirectResponse('inv/index');
            }
        }
        return $this->webService->getRedirectResponse('inv/index');
    }

    /**
     * Purpose: A warning is given if this setting is ON during production
     * Use: Toggle between 1. On (Development) or 2. Off (Production) on the flash message under invoice/index
     * Location: Settings ... View ... Invoices ... Other Settings ... Mark invoices as sent when copying an invoice
     * Route name: setting/mark_sent route action setting/mark_sent
     * @see /config/common/routes.php
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function mark_sent(CurrentRoute $currentRoute): Response
    {
        $setting = $this->setting($currentRoute);
        if ($setting) {
            if ($setting->getSetting_value() == '0') {
                $setting->setSetting_value('1');
                $this->sR->save($setting);
                return $this->webService->getRedirectResponse('inv/index');
            }
            if ($setting->getSetting_value() == '1') {
                $setting->setSetting_value('0');
                $this->sR->save($setting);
                return $this->webService->getRedirectResponse('inv/index');
            }
        }
        return $this->webService->getRedirectResponse('inv/index');
    }

    /**
     * Purpose: Save the users toggle button choice on flash message: 'Assign a client to a user automatically after signup'
     * @see App\Widget\Button static function setOrUnsetAssignClientToUserAutomatically
     * @see src\Invoice\UserInv\UserInvController function signup
     * @return Response
     */
    public function auto_client(): Response
    {
        $setting = $this->sR->withKey('signup_automatically_assign_client');
        if ($setting) {
            if ($setting->getSetting_value() == '0') {
                $setting->setSetting_value('1');
                $this->sR->save($setting);
                return $this->webService->getRedirectResponse('site/index');
            }
            if ($setting->getSetting_value() == '1') {
                $setting->setSetting_value('0');
                $this->sR->save($setting);
                return $this->webService->getRedirectResponse('site/index');
            }
        }
        return $this->webService->getRedirectResponse('site/index');
    }

    public function visible(): Response
    {
        $setting = $this->sR->withKey('columns_all_visible');
        if ($setting) {
            if ($setting->getSetting_value() == '0') {
                $setting->setSetting_value('1');
                $this->sR->save($setting);
                return $this->webService->getRedirectResponse('inv/index');
            }
            if ($setting->getSetting_value() == '1') {
                $setting->setSetting_value('0');
                $this->sR->save($setting);
                return $this->webService->getRedirectResponse('inv/index');
            }
            $setting->setSetting_value('0');
            $this->sR->save($setting);
            return $this->webService->getRedirectResponse('inv/index');
        }
        $new_setting = new Setting();
        $new_setting->setSetting_key('columns_all_visible');
        $this->sR->save($new_setting);

        return $this->webService->getRedirectResponse('inv/index');
    }

    /**
     * Used in: inv/index. User clicks on button to unhide column with invsentlogs table
     * @return Response
     */
    public function unhideOrHideToggleInvSentLogColumn(): Response
    {
        $setting = $this->sR->withKey('column_inv_sent_log_visible');
        if ($setting) {
            if ($setting->getSetting_value() == '0') {
                $setting->setSetting_value('1');
                $this->sR->save($setting);
                return $this->webService->getRedirectResponse('inv/index');
            }
            if ($setting->getSetting_value() == '1') {
                $setting->setSetting_value('0');
                $this->sR->save($setting);
                return $this->webService->getRedirectResponse('inv/index');
            }
            $setting->setSetting_value('0');
            $this->sR->save($setting);
            return $this->webService->getRedirectResponse('inv/index');
        }
        $new_setting = new Setting();
        $new_setting->setSetting_key('column_inv_sent_log_visible');
        $this->sR->save($new_setting);

        return $this->webService->getRedirectResponse('inv/index');
    }

    public function listlimit(CurrentRoute $currentRoute): Response
    {
        $setting = $this->setting($currentRoute);
        $origin = $currentRoute->getArgument('origin') ?? 'inv';
        $limit = $currentRoute->getArgument('limit');
        if ($setting) {
            $setting->setSetting_value((string)$limit);
            $this->sR->save($setting);
        }
        return $this->webService->getRedirectResponse($origin . '/index');
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
        FormHydrator $formHydrator
    ): Response {
        $setting = $this->setting($currentRoute);
        if ($setting) {
            $form = new SettingForm($setting);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'setting/edit',
                'actionArguments' => ['setting_id' => $setting->getSetting_id()],
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
                    $this->flashMessage('info', $this->translator->translate('i.record_successfully_updated'));
                    return $this->webService->getRedirectResponse('setting/debug_index');
                }
                $parameters['form'] = $form;
                $parameters['error'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            }
            return $this->viewRenderer->render('__form', $parameters);
        }
        return $this->webService->getRedirectResponse('setting/debug_index');
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
            $this->flashMessage('info', $this->translator->translate('i.record_successfully_deleted'));
            $this->settingService->deleteSetting($setting);
        }
        return $this->webService->getRedirectResponse('setting/debug_index');
    }

    /**
     * @param CurrentRoute $currentRoute
     */
    public function view(CurrentRoute $currentRoute): \Yiisoft\DataResponse\DataResponse|Response
    {
        $setting = $this->setting($currentRoute);
        if ($setting) {
            $form = new SettingForm($setting);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'setting/view',
                'actionArguments' => ['setting_id' => $setting->getSetting_id()],
                'setting' => $setting,
                'form' => $form,
            ];
            return $this->viewRenderer->render('__view', $parameters);
        }
        return $this->webService->getRedirectResponse('setting/index');
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('setting/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @return Setting|null
     */
    private function setting(
        CurrentRoute $currentRoute
    ): Setting|null {
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
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function clear(): \Yiisoft\DataResponse\DataResponse
    {
        $directory = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets';
        try {
            $filehelper = new FileHelper();
            $filehelper->clearDirectory($directory);
            $this->flashMessage('info', $this->translator->translate('invoice.setting.assets.cleared.at') . $directory);
            return $this->factory->createResponse($this->viewRenderer->renderPartialAsString(
                '//invoice/setting/successful',
                ['heading' => $this->translator->translate('invoice.successful'),'message' => $this->translator->translate('invoice.setting.you.have.cleared.the.cache')]
            ));
        } catch (\Exception $e) {
            $this->flashMessage('warning', $this->translator->translate('invoice.setting.assets.were.not.cleared.at') . $directory . $this->translator->translate('invoice.setting.as.a.result.of') . $e->getMessage());
            return $this->factory->createResponse($this->viewRenderer->renderPartialAsString(
                '//invoice/setting/unsuccessful',
                ['heading' => $this->translator->translate('invoice.unsuccessful'),'message' => $this->translator->translate('invoice.setting.you.have.not.cleared.the.cache.due.to.a') . $e->getMessage() . $this->translator->translate('invoice.setting.error.on.the.public.assets.folder')]
            ));
        }
    }

    /**
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function get_cron_key(): \Yiisoft\DataResponse\DataResponse
    {
        $parameters = [
            'success' => 1,
            'cronkey' => Random::string(32),
        ];
        return $this->factory->createResponse(Json::encode($parameters));
    }

    private function invoiceplaneConnected(): bool
    {
        $settingInvoiceplaneName = $this->sR->getSetting('invoiceplane_database_name');
        $settingInvoiceplaneUsername = $this->sR->getSetting('invoiceplane_database_username');
        $settingInvoiceplanePassword = $this->sR->getSetting('invoiceplane_database_password') ?: '';
        if (strlen($settingInvoiceplaneName) > 0 && strlen($settingInvoiceplaneUsername) > 0) {
            $dsn = (new Dsn(
                'mysql',
                '127.0.0.1',
                $settingInvoiceplaneName,
                '3306',
                [
                    'charset' => 'utf8mb4',
                ]
            )
            )->asString();
            $arrayCache = new ArrayCache();
            $schemaCache = new SchemaCache($arrayCache);
            $pdoDriver = new Driver($dsn, $settingInvoiceplaneUsername, $settingInvoiceplanePassword);
            new Connection($pdoDriver, $schemaCache);
            return true;
        }
        return false;
    }
}
