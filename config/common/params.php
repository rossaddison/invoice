<?php

declare(strict_types=1);

use App\ViewInjection\CommonViewInjection;
use App\ViewInjection\LayoutViewInjection;
use App\ViewInjection\LinkTagsViewInjection;
use App\ViewInjection\MetaTagsViewInjection;
use Psr\Log\LogLevel;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Definitions\Reference;
use Yiisoft\Form\Field\SubmitButton;
use Yiisoft\Form\Field\Checkbox;
use Yiisoft\Form\Field\ErrorSummary;
use Yiisoft\FormModel\ValidationRulesEnricher;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\Cycle\Schema\Conveyor\MetadataSchemaConveyor;
use Yiisoft\Yii\Cycle\Schema\Provider\FromConveyorSchemaProvider;
use Cycle\Schema\Provider\PhpFileSchemaProvider;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\Pagination\OffsetPagination;
use Yiisoft\Yii\View\Renderer\CsrfViewInjection;
// yii3-i
use App\Invoice\Helpers\ClientHelper;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Helpers\CustomValuesHelper;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Helpers\Peppol\Peppol_UNECERec20_11e;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\InvRecurring\InvRecurringRepository;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\QuoteAmount\QuoteAmountRepository;
use App\Invoice\Setting\SettingRepository;
use App\Widget\Button;
use App\Widget\GridComponents;
use App\Widget\PageSizeLimiter;
use App\Widget\SubMenu;

$env = $_ENV['APP_ENV'] ?? 'local';
$dbUser = $_ENV['DB_USERNAME'] ?: 'root';
$dbName = $_ENV['DB_NAME'] ?: 'yii3_i';
$dbPassword = $_ENV['DB_PASSWORD'] ?: null;

switch ($env) {
    case 'docker':
        $dbHost = $_ENV['DB_HOST_IP_ADDRESS'] ?? '192.168.0.24';
        break;
    // alpine will fall into this default
    default:
        $dbHost = $_ENV['DB_HOST_IP_ADDRESS'] ?? 'localhost';
}
$buttonClass = 'buttonClass()';
$containerClass = 'containerClass()';
$submitButtonConfigs = [
    'default' => [
        $buttonClass => ['btn btn-primary btn-sm mt-3'],
        $containerClass => ['d-grid gap-2 form-floating'],
    ],
    'bootstrap5-vertical' => [
        $buttonClass => ['btn btn-primary'],
    ],
    'bootstrap5-horizontal' => [
        $buttonClass => ['btn btn-primary'],
    ],
];

return [
    'yiisoft/log-target-file' => [
        'fileTarget' => [
            'file' => '@runtime/logs/app.log',
            'levels' => [
                //LogLevel::EMERGENCY,
                //LogLevel::ERROR,
                //LogLevel::WARNING,
                LogLevel::INFO,
                //LogLevel::DEBUG,
            ],
            'dirMode' => 0o755,
            'fileMode' => null,
        ],
        'fileRotator' => [
            'maxFileSize' => 500,
            'maxFiles' => 100,
            'fileMode' => null,
            'compressRotatedFiles' => false,
        ],
    ],
    'env' => $_ENV['YII_ENV'] ?? '',
    'server' => [
        'remote_port' => $_SERVER['REMOTE_PORT'] ?? null,
        'http_x_forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
        'http_client_ip' => $_SERVER['HTTP_CLIENT_IP'] ?? null,
    ],
    'license' => [
        'id' => 'invoice_BSD-3-Clause_20250511',
    ],
    'product' => [
        'name' => 'RossAddison Invoice',
        'version' => 'pre-release',
    ],
    'mailer' => [
        'adminEmail' => 'admin@example.com',
    /**
     * Note: This setting is critical to the sending of emails since it is used
     * in SettingsRepository getConfigSenderEmail()
     * Used in critical function e.g src/Auth/Controller/SignUpController
     * function signup and src/Auth/Controller/ForgotController function forgot
     */
        'senderEmail' => 'sender@your.web.site.domain.com',
    ],

    /**
   * Related logic: src/Invoice/Setting/SettingRepository
   *  function getOauth2IdentityProviderConfigParamsClientsArray()
   * Related logic: App\Widgets\Button function facebook, github, google
   * Related logic: App\Auth\Controller\AuthController function login
   * Related logic: resources\views\auth\login.php
   */
    'yiisoft/yii-auth-client' => [
        'enabled' => true,
        'clients' => [
            'developersandboxhmrc' => [
                'class' =>
                    'Yiisoft\Yii\AuthClient\Client\DeveloperSandboxHmrc::class',
                'clientId' =>
                    $_ENV['DEVELOPER_GOV_SANDBOX_HMRC_API_CLIENT_ID'] ?? '',
                'clientSecret' =>
                    $_ENV['DEVELOPER_GOV_SANDBOX_HMRC_API_CLIENT_SECRET'] ?? '',
                'returnUrl' =>
                    $_ENV['DEVELOPER_GOV_SANDBOX_HMRC_API_CLIENT_RETURN_URL'] ?? '',
            ],
            'facebook' => [
                'class' => 'Yiisoft\Yii\AuthClient\Client\Facebook::class',
                'clientId' => $_ENV['FACEBOOK_API_CLIENT_ID'] ?? '',
                'clientSecret' => $_ENV['FACEBOOK_API_CLIENT_SECRET'] ?? '',
                'returnUrl' => $_ENV['FACEBOOK_API_CLIENT_RETURN_URL'] ?? '',
            ],
            'github' => [
                'class' => 'Yiisoft\Yii\AuthClient\Client\Github::class',
                'clientId' => $_ENV['GITHUB_API_CLIENT_ID'] ?? '',
                'clientSecret' => $_ENV['GITHUB_API_CLIENT_SECRET'] ?? '',
                'returnUrl' => $_ENV['GITHUB_API_CLIENT_RETURN_URL'] ?? '',
            ],
            'google' => [
                'class' => 'Yiisoft\Yii\AuthClient\Client\Google::class',
                'clientId' => $_ENV['GOOGLE_API_CLIENT_ID'] ?? '',
                'clientSecret' => $_ENV['GOOGLE_API_CLIENT_SECRET'] ?? '',
                'returnUrl' => $_ENV['GOOGLE_API_CLIENT_RETURN_URL'] ?? '',
            ],
            'govuk' => [
                'class' => 'Yiisoft\Yii\AuthClient\Client\GovUk::class',
                'clientId' => $_ENV['GOVUK_API_CLIENT_ID'] ?? '',
                'clientSecret' => $_ENV['GOVUK_API_CLIENT_SECRET'] ?? '',
                'returnUrl' => $_ENV['GOVUK_API_CLIENT_RETURN_URL'] ?? '',
            ],
            'linkedin' => [
                'class' => 'Yiisoft\Yii\AuthClient\Client\LinkedIn::class',
                'clientId' => $_ENV['LINKEDIN_API_CLIENT_ID'] ?? '',
                'clientSecret' => $_ENV['LINKEDIN_API_CLIENT_SECRET'] ?? '',
                'returnUrl' => $_ENV['LINKEDIN_API_CLIENT_RETURN_URL'] ?? '',
            ],
            'microsoftonline' => [
                'class' => 'Yiisoft\Yii\AuthClient\Client\MicrosoftOnline::class',
                'clientId' => $_ENV['MICROSOFTONLINE_API_CLIENT_ID'] ?? '',
                'clientSecret' => $_ENV['MICROSOFTONLINE_API_CLIENT_SECRET'] ?? '',
                'returnUrl' => $_ENV['MICROSOFTONLINE_API_CLIENT_RETURN_URL'] ?? '',
              /**
               * tenant can be one of 'common', 'organisations',
               * 'consumers', or a tenant ID
               * Related logic: https://learn.microsoft.com/en-us/entra/
               * identity-platform/
               * v2-oauth2-auth-code-flow#request-an-authorization-code
               */
                'tenant' => $_ENV['MICROSOFTONLINE_API_CLIENT_TENANT'] ?? 'common',
            ],
            'oidc' => [
                'class' => 'Yiisoft\Yii\AuthClient\Client\Google::class',
                //'issuerUrl' => 'dev-0yhorhwwkgkdmu1g.uk.auth0.com',
                'issuerUrl' =>
                'https://accounts.google.com/.well-known/openid-configuration',
                'clientId' => $_ENV['OIDC_API_CLIENT_ID'] ?? '',
                'clientSecret' => $_ENV['OIDC_API_CLIENT_SECRET'] ?? '',
                'returnUrl' => $_ENV['OIDC_API_CLIENT_RETURN_URL'] ?? '',
            ],
            'openbanking' => [
                'class' => 'Yiisoft\Yii\AuthClient\Client\OpenBanking::class',
                'clientId' => $_ENV['OPENBANKING_API_CLIENT_ID'] ?? '',
                'clientSecret' => $_ENV['OPENBANKING_API_CLIENT_SECRET'] ?? '',
                'returnUrl' => $_ENV['OPENBANKING_API_CLIENT_RETURN_URL'] ?? '',
            ],
            'vkontakte' => [
                'class' => 'Yiisoft\Yii\AuthClient\Client\VKontakte::class',
                'clientId' => $_ENV['VKONTAKTE_API_CLIENT_ID'] ?? '',
                'clientSecret' => $_ENV['VKONTAKTE_API_CLIENT_SECRET'] ?? '',
                'returnUrl' => $_ENV['VKONTAKTE_API_CLIENT_RETURN_URL'] ?? '',
            ],
            'x' => [
                'class' => 'Yiisoft\Yii\AuthClient\Client\X::class',
                'clientId' => $_ENV['X_API_CLIENT_ID'] ?? '',
                'clientSecret' => $_ENV['X_API_CLIENT_SECRET'] ?? '',
                'returnUrl' => $_ENV['X_API_CLIENT_RETURN_URL'] ?? '',
            ],
            'yandex' => [
                'class' => 'Yiisoft\Yii\AuthClient\Client\Yandex::class',
                'clientId' => $_ENV['YANDEX_API_CLIENT_ID'] ?? '',
                'clientSecret' => $_ENV['YANDEX_API_CLIENT_SECRET'] ?? '',
                'returnUrl' => $_ENV['YANDEX_API_CLIENT_RETURN_URL'] ?? '',
            ],
        ],
    ],
    'yiisoft/aliases' => [
        'aliases' => [
            '@root' => dirname(__DIR__, 2),
            '@views' => dirname(__DIR__, 2) . '/resources/views',
            '@assets' => '@root/public/assets',
            '@assetsUrl' => '@baseUrl/assets',
            '@baseUrl' => '',
            '@hmrc' => '@resources/backend/views/hmrc',
            '@messages' => '@resources/messages',
            '@English' => '@src/Invoice/Language/English',
            '@generated' => '@views/invoice/generator/output_overwrite',
            '@npm' => '@root/node_modules',
            '@public' => '@root/public',
            '@resources' => '@root/resources',
            '@runtime' => '@root/runtime',
            '@src' => '@root/src',
            '@validatorMessages' => '@vendor/yiisoft/validator/messages',
            '@vendor' => '@root/vendor',
            '@layout' => '@views/layout',
        ],
    ],
    'yiisoft/form' => [
        'themes' => [
            'defaultTheme' => 'bootstrap5-vertical',
            'validationRulesEnricher' => new ValidationRulesEnricher(),
            // currently being used
            'default' => [
                'containerClass' => 'form-floating mb-3',
                'inputClass' => 'form-control h3',
                'invalidClass' => 'is-invalid',
                'validClass' => 'is-valid',
                'template' => '{input}{label}{hint}{error}',
                'labelClass' => 'floatingInput h6',
                'errorClass' => 'fw-bold fst-italic badge bg-danger text-wrap',
               /**
                * Related logic: resources/views/invoice/
                * product/_form.php and adjust the h6 below to h1 and see the
                * effect
                */
                'hintClass' => 'text-danger h4',
                'fieldConfigs' => [
                    $submitButtonConfigs['default'],
                    // if this Checkbox class is not used then the checkbox ends
                    // up floating because of the default containerClass above;
                    // refer to client form with active client checkbox
                    Checkbox::class => [
                        $containerClass => ['form-group'],
                    ],
                    DataColumn::class => [
                        $containerClass => ['form-group'],
                    ],
                    OffsetPagination::class => [
                        'listTag()' => ['ul'],
                        'listAttributes()' => [['class' => 'pagination']],
                        'itemTag()' => ['li'],
                        'itemAttributes()' => [['class' => 'page-item']],
                        'linkAttributes()' => [['class' => 'page-link']],
                        'currentItemClass()' => ['active'],
                        'disabledItemClass()' => ['disabled'],
                    ],
                ],
            ],
            'bootstrap5-vertical' => [
                'template' => "{label}\n{input}\n{hint}\n{error}",
                'containerClass' => 'mb-3',
                'labelClass' => 'form-label',
                'inputClass' => 'form-control',
                'hintClass' => 'form-text',
                'errorClass' => 'invalid-feedback',
                'inputValidClass' => 'is-valid',
                'inputInvalidClass' => 'is-invalid',
                'fieldConfigs' => [
                    ErrorSummary::class => [
                        $containerClass => ['alert alert-danger'],
                        'listAttributes()' => [['class' => 'mb-0']],
                        'header()' => [''],
                    ],
                    SubmitButton::class =>
                                    $submitButtonConfigs['bootstrap5-vertical'],
                ],
                'enrichFromValidationRules' => true,
            ],
            'bootstrap5-horizontal' => [
                'template' =>
             "{label}\n<div class=\"col-sm-10\">{input}\n{hint}\n{error}</div>",
                'containerClass' => 'row mb-3',
                'labelClass' => 'col-sm-2 col-form-label',
                'inputClass' => 'form-control',
                'hintClass' => 'form-text',
                'errorClass' => 'invalid-feedback',
                'inputValidClass' => 'is-valid',
                'inputInvalidClass' => 'is-invalid',
                'fieldConfigs' => [
                    SubmitButton::class =>
                                    $submitButtonConfigs['bootstrap5-horizontal'],
                    ErrorSummary::class => [
                        $containerClass => ['alert alert-danger'],
                        'listClass()' => ['mb-0'],
                        'header()' => [''],
                    ],
                ],
                'enrichFromValidationRules' => true,
            ],
        ],
    ],
    'yiisoft/rbac-rules-container' => [
        'rules' => require_once __DIR__ . '/rbac-rules.php',
    ],
    'yiisoft/router-fastroute' => [
        'enableCache' => false,
        'encodeRaw' => true,
    ],
    'yiisoft/translator' => [
        'locale' => 'en',
        'fallbackLocale' => 'en',
        'defaultCategory' => 'app',
        'validatorCategory' => 'yii-validator',
    ],
    'yiisoft/view' => [
        'basePath' => '@views',
        'parameters' => [
            'assetManager' => Reference::to(AssetManager::class),
            'urlGenerator' => Reference::to(UrlGeneratorInterface::class),
            'currentRoute' => Reference::to(CurrentRoute::class),
            'translator' => Reference::to(TranslatorInterface::class),
// yii-invoice - Below parameters are specifically used in views/layout/invoice
            's' => Reference::to(SettingRepository::class),
            'button' => Reference::to(Button::class),
            'session' => Reference::to(SessionInterface::class),
            'clientHelper' => Reference::to(ClientHelper::class),
            'countryHelper' => Reference::to(CountryHelper::class),
            'cvH' => Reference::to(CustomValuesHelper::class),
            'datehelper' => Reference::to(DateHelper::class),
            'dateHelper' => Reference::to(DateHelper::class),
            'numberHelper' => Reference::to(NumberHelper::class),
            'pageSizeLimiter' => Reference::to(PageSizeLimiter::class),
            'peppolUNECERec2011e' => Reference::to(Peppol_UNECERec20_11e::class),
            'gridComponents' => Reference::to(GridComponents::class),
            'subMenu' => Reference::to(SubMenu::class),
// Appear in client/view.php and duplication taken out of ClientController
//  function view
            'cR' => Reference::to(ClientRepository::class),
            'iR' => Reference::to(InvRepository::class),
            'iaR' => Reference::to(InvAmountRepository::class),
            'irR' => Reference::to(InvRecurringRepository::class),
            'qR' => Reference::to(QuoteRepository::class),
            'qaR' => Reference::to(QuoteAmountRepository::class),
        ],
    ],
    'yiisoft/cookies' => [
        'secretKey' => '53136271c432a1af377c3806c3112ddf',
    ],
// works in association with yiisoft/yii-debug-viewer which is not installed
// Related logic: see blog/common/config/di/router.php  which relies on
//   Yiisoft\Yii\Debug\Viewer\Middleware\ToolbarMiddleware
    'yiisoft/yii-debug' => [
        'enabled' => false,
    ],
    'yiisoft/yii-debug-api' => [
        'enabled' => false,
    ],
    'yiisoft/yii-view-renderer' => [
        'viewPath' => '@views',
        //'layout' => '@views/layout/main.php',
        'layout' => '@views/layout/templates/soletrader/main.php',
        'injections' => [
            Reference::to(CommonViewInjection::class),
            Reference::to(CsrfViewInjection::class),
            Reference::to(LayoutViewInjection::class),
            Reference::to(LinkTagsViewInjection::class),
            Reference::to(MetaTagsViewInjection::class),
            Reference::to(SettingRepository::class),
        ],
    ],
    'yiisoft/yii-cycle' => [
        // DBAL config
        'dbal' => [
            // SQL query logger. Definition of Psr\Log\LoggerInterface
            // For example, \Yiisoft\Yii\Cycle\Logger\StdoutQueryLogger::class
            'query-logger' => null,
            // Default database
            'default' => 'default',
            'aliases' => [],
            'databases' => [
                //'default' => ['connection' => 'sqlite'],
                // yii-invoice
                'default' => ['connection' => 'mysql'],
            ],
            'connections' => [
                'mysql' => new Cycle\Database\Config\MySQLDriverConfig(
                    connection: new Cycle\Database\Config\MySQL\DsnConnectionConfig(
                        'mysql:host=' . $dbHost . ';dbname='. $dbName,
                        $dbUser,
                        $dbPassword,
                    ),
                    driver: Cycle\Database\Driver\MySQL\MySQLDriver::class,
                ),
            ],
        ],
        // Cycle migration config
        'migrations' => [
            'directory' => '@root/migrations',
            'namespace' => 'App\\Migration',
            'table' => 'migration',
            'safe' => false,
        ],
/**
 * SchemaProvider list for {Related logic: see \Yiisoft\Yii\Cycle\Schema\
 *                                      Provider\Support\SchemaProviderPipeline}
 * Array of classname and {Related logic: see SchemaProviderInterface} object.
 * You can configure providers if you pass classname as key and parameters as
 *  array:
 * [
 *     SimpleCacheSchemaProvider::class => [
 *         'key' => 'my-custom-cache-key'
 *     ],
 *     FromFilesSchemaProvider::class => [
 *         'files' => ['@runtime/cycle-schema.php']
 *     ],
 *     FromConveyorSchemaProvider::class => [
 *         'generators' => [
 *              Generator\SyncTables::class, // sync table changes to database
 *          ]
 *     ],
 * ]
 */

/**
 * To update a table structure and related schema use MODE_WRITE_ONLY
 *  ...then revert back to MODE_READ_AND_WRITE
 * For faster performance use MODE_READ_AND_WRITE
 * Note as at 15/06/2024: If you have adjusted any Entity file you will have
 *  to always make two adjustments to
 * ensure the database is updated with the new changes and relevent fields:
 * 1. Change the mode immediately below
 * 2. Change the BUILD_DATABASE=  in the .env file at the root to
 *  BUILD_DATABASE=true
 * 3. Once the changes have been reflected and you have checked them via
 *  e.g. phpMyAdmin revert back to the original settings
 * Mode: PhpFileSchemaProvider::MODE_WRITE_ONLY or 1 :
 *  PhpFileSchemaProvider::MODE_READ_AND_WRITE or 0 \
 */
        'schema-providers' => [
            PhpFileSchemaProvider::class => [
                /**
                 * @psalm-suppress RiskyTruthyFalsyComparison
                 */
                'mode' => $_ENV['BUILD_DATABASE'] ?? '' ?
                    PhpFileSchemaProvider::MODE_WRITE_ONLY :
                    PhpFileSchemaProvider::MODE_READ_AND_WRITE,
                'file' => 'runtime/schema.php',
            ],
            FromConveyorSchemaProvider::class => [
                'generators' => [
                    // sync table changes to database
                    Cycle\Schema\Generator\SyncTables::class, 
                ],
            ],
        ],

/**
 * Related logic: https://github.com/yiisoft/yii-cycle/pull/141/
 * files#diff-09651a8d339eb91e3f0a340f94f4b0caf4df642c48812a526f8c80f7b8ba7ad4
 *
 * Collection factories.
 *
 * @link https://cycle-orm.dev/docs/relation-collections/2.x
 */
        'collections' => [
            /** Default factory (class or name from the `factories`
                list below) or {@see null} */
            'default' => 'doctrine',
            /** List of class names that implement
                {@see \Cycle\ORM\Collection\CollectionFactoryInterface} */
            'factories' => [
                //'array' => \Cycle\ORM\Collection\ArrayCollectionFactory::class,
                'doctrine' => \Cycle\ORM\Collection\DoctrineCollectionFactory::class,
                //'illuminate' => \Cycle\ORM\Collection\IlluminateCollectionFactory::class,
            ],
        ],

/**
 * Config for {Related logic: see \Yiisoft\Yii\Cycle\Schema\Conveyor\
                                                        AnnotatedSchemaConveyor}
 * Annotated entity directories list.
 * {Related logic: see \Yiisoft\Aliases\Aliases} are also supported.
 */
        'entity-paths' => [
            '@src',
        ],
        'conveyor' => MetadataSchemaConveyor::class,
    ],
    'yiisoft/yii-swagger' => [
        'annotation-paths' => [
            '@src/Controller',
            '@src/User/Controller',
        ],
    ],
    'yiisoft/mailer' => [
        'fileMailer' => [
            'path' => '@runtime/mail',
        ],
    ],
    'yiisoft/mailer-symfony' => [
        'esmtpTransport' => [
/**
 * enabled => true is a setting independent of vendor/yiisoft/mailer-symfony/
 * config/params.php
 * Related logic: see SettingRepository function config_params()  */
            'enabled' => true,
            'useSendMail' => false,
            'scheme' => 'smtp', // "smtps": using TLS, "smtp": without using TLS.
            'host' => 'mail.yourinternet.com',
            'port' => 25,
            'username' => filter_input(INPUT_ENV, 'SYMFONY_MAILER_USERNAME') ?? '',
/**
 * Avoid the use of hard-coded credentials
 * Related logic: https://cwe.mitre.org/data/definitions/798.html
 * Related logic: The .env file in the root folder
 * Related logic:
 * https://stackoverflow.com/questions/97984/
 *                                      how-to-secure-database-passwords-in-php
 */
            'password' => filter_input(INPUT_ENV, 'SYMFONY_MAILER_PASSWORD') ?? '',
            'options' => [],
            // See: https://symfony.com/doc/current/mailer.html#tls-peer-verification
        ],
        'messageSettings' => [
            'charset' => 'utf-8',
            'from' => null,
            'addFrom' => null,
            'to' => null,
            'addTo' => null,
            'replyTo' => null,
            'addReplyTo' => null,
            'cc' => null,
            'addCc' => null,
            'bcc' => null,
            'addBcc' => null,
            'subject' => null,
            'date' => null,
            'priority' => null,
            'returnPath' => null,
            'sender' => null,
            'textBody' => null,
            'htmlBody' => null,
            'attachments' => null,
            'addAttachments' => null,
            'embeddings' => null,
            'addEmbeddings' => null,
            'headers' => [],
            'overwriteHeaders' => null,
            'convertHtmlToText' => true,
        ],
    ],
    'company' => [
        'logopublicsource' => 'site',
        'logofilenamewithsuffix' => 'logo.png',
        'name' => 'MyCompanyName',
        'address_1' => '1 MyCompany Street',
        'address_2' => 'MyCompany Area',
        'city' => 'MyCompanyCity',
        'country' => 'MyCompanyCountry',
        'zip' => 'A11 1AA',
        'state' => 'My State',
        'vat_id' => 'GB123456789',
        'tax_code' => 'GBP',
        'tax_currency' => 'GBP',
        'document_currency' => 'GBP',
        'phone' => '02000000000',
        'fax' => '0200000000',
        'iso_3166_country_identification_code' => 'GB',
        'iso_3166_country_identification_list_id' => 'ISO3166-1:Alpha2',
    ],
    // In association with src/Invoice/Setting/SettingRepository/get_config_peppol()
    // If you add values here, be sure to add them to get_config_peppol()
    // and you will need to create a new function in src/Invoice/Helpers/PeppolHelper
    // The default data inserted here mirrors/replicates the data from:
    // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/tree/
    // Note: Invoices in the UK can be made out in a foreign currency eg. EUR =>
    //  $documentCurrencyCode with a foreign language of choice;
    //       However it is mandatory/must according to the UK, and according to
    //        Peppol to provide
    //       an equivalent/equal VAT amount with the local currency code ie.
    //        GBP, namely Related logic: see TaxCurrencyCode on the invoice
    'peppol' => [
        'invoice' => [
            'CustomizationID' =>
   'urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0',
            'ProfileID' => 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
            'InvoiceTypeCode' => '380',
            'Note' => 'Please use our latest telephone number',
      /**
       * Related logic: see $settingRepository->getSetting('currency_code_to')
       */
            'DocumentCurrencyCode' => 'EUR',
      /**
       * Related logic: see $settingRepository->getSetting('currency_code_from')
       */
            'TaxCurrencyCode' => 'GBP',
            'AccountingSupplierParty' => [
                'Party' => [
                    'EndPointID' => [
                        'value' => '7300010000001',
                        'schemeID' => '0088',
                    ],
//https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
//                 cac-AccountingSupplierParty/cac-Party/cac-PartyIdentification/
                    'PartyIdentification' => [
                        'ID' => [
                            'value' => '5060012349998',
                            // optional
                            'schemeID' => '0088',
                        ],
                    ],
                    'PostalAddress' => [
                        'StreetName' => 'Main Street 1',
                        'AdditionalStreetName' => 'Po Box 351',
                        'AddressLine' => [
                            'Line' => 'Building 23',
                        ],
                        'CityName' => 'London',
                        'PostalZone' => 'W1G 8LZ',
                        'CountrySubentity' => 'Region A',
                        'Country' => [
                            'IdentificationCode' => 'GB',
//https://docs.peppol.eu/poacc/billing/3.0/codelist/ISO3166/
//Alpha 2 => 2 digit code eg. GB
//Alpha 3 => 3 digit code eg. GBP
/**
 * ListId should not be shown => see src/Invoice/Ubl/Country
 * Warning
 * Location: invoice_a-362E8wINV107_peppol
 * Element/context: /:Invoice[1]
 * XPath test: not(//cac:Country/cbc:IdentificationCode/@listID)
 * Error message: [UBL-CR-660]-A UBL invoice should not include the Country
 *  Identification code listID
 */
                            'ListId' => 'ISO3166-1:Alpha2',
                        ],
                    ],
                    'Contact' => [
                        'Name' => 'Joe Bloggs',
                        'FirstName' => 'Joe',
                        'LastName' => 'Bloggs',
                        'Telephone' => '801 801 801',
/**
 * Warning from Ecosio Validator: OpenPeppol UBL Invoice (3.15.0)
 *  (a.k.a BIS Billing 3.0.14)
 * Location: invoice_a0oVdj0WINV107_peppol
 * Element/context: /:Invoice[1]
 * XPath test: not(cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:Telefax)
 * Error message: [UBL-CR-190]-A UBL invoice should not include the
 *  AccountingSupplierParty Party Contact Telefax
 */
                        'Telefax' => '',
                        'ElectronicMail' => 'test.name@foo.bar',
                    ],
                    'PartyTaxScheme' => [
                        // EU: VAT Number
                        'CompanyID' => 'GB999888777',
                        'TaxScheme' => [
// https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
// cac-AccountingSupplierParty/cac-Party/cac-PartyTaxScheme/cac-TaxScheme/cbc-ID/
// VAT / !VAT
                            'ID' => 'VAT',
                        ],
                    ],
                    'PartyLegalEntity' => [
                        'RegistrationName' => 'Full Formal Seller Name LTD.',
                        'CompanyID' => '987654321',
/**
 * Related logic: src/Invoice/Ubl/PartyLegalEntity
 * Related logic: src/Invoice/Setting/SettingRepository function get_config_peppol
 * Related logic: src/Invoice/Helpers/PeppolHelper function SupplierPartyLegalEntity()
 */
                        'Attributes' => [
                            'schemeID' => '0002',
                        ],
                        'CompanyLegalForm' => 'Share Capital',
                    ],
                ],
            ],
            'PayeeParty' => [
                'PartyIdentification' => [
                    'ID' => 'FR932874294',
                    'schemeID' => 'SEPA',
                ],
                'PartyName' => [
                    'Name' => '',
                ],
                'PartyLegalEntity' => [
                    'CompanyID' => '',
                    'schemeID' => '',
                ],
            ],
            'PaymentMeans' => [
                'PaymentMeansCode' => '30',
                'PaymentID' => '432948234234234',
                'CardAccount' => [
                    'PrimaryAccountNumberID' => '1234',
                    'NetworkID' => 'NA',
                    'HolderName' => 'John Doe',
                ],
                // Supplier/Designated Payee in company
                'PayeeFinancialAccount' => [
                    // eg. IBAN number
                    'ID' => 'IBAN number',
                    // Name of account holder
                    'Name' => 'FF',
                    'FinancialInstitutionBranch' => [
                        //Payment service provider identifier
                        //An identifier for the payment service provider
                        //where a payment account is located. Such as a
                        //BIC or a national clearing code where required.
                        //No identification scheme Identifier to be used.
                        'ID' => '9999',
                    ],
                ],
                'PaymentMandate' => [
                    // Mandate reference identifier
                    // Unique identifier assigned by the
                    // Payee for referencing the direct
                    // debit mandate. Used in order to
                    // pre-notify the Buyer of a SEPA
                    // direct debit.
                    'ID' => '123456',
                    'PayerFinancialAccount' => [
                        // Debited account identifier
                        // The account to be debited by
                        // the direct debit.
                        'ID' => '12345676543',
                    ],
                ],
            ],
        ],
    ],
];
