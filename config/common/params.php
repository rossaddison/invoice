<?php

declare(strict_types=1);

use App\ViewInjection\CommonViewInjection;
use App\ViewInjection\LayoutViewInjection;
use App\ViewInjection\LinkTagsViewInjection;
use App\ViewInjection\MetaTagsViewInjection;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Definitions\Reference;
use Yiisoft\Form\Field\SubmitButton;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\Cycle\Schema\Conveyor\AttributedSchemaConveyor;
use Yiisoft\Yii\Cycle\Schema\Provider\FromConveyorSchemaProvider;
use Yiisoft\Yii\Cycle\Schema\Provider\PhpFileSchemaProvider;
use Yiisoft\Yii\View\CsrfViewInjection;
// yii3-i
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\Session\SessionInterface;

return [
  'mailer' => [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'sender@example.com',
  ],
  'yiisoft/aliases' => [
    'aliases' => [
      '@root' => dirname(__DIR__, 2),
      '@assets' => '@root/public/assets',
      '@assetsUrl' => '@baseUrl/assets',
      '@baseUrl' => '',
      '@messages' => '@resources/messages',
      '@npm' => '@root/node_modules',
      '@public' => '@root/public',
      '@resources' => '@root/resources',
      '@runtime' => '@root/runtime',
      '@src' => '@root/src',
      '@vendor' => '@root/vendor',
      '@layout' => '@views/layout',
      '@views' => '@resources/views',
    ],
  ],
  'yiisoft/form' => [
    'configs' => [
      'default' => [
        'containerClass' => 'form-floating mb-3',
        'inputClass' => 'form-control',
        'invalidClass' => 'is-invalid',
        'validClass' => 'is-valid',
        'template' => '{input}{label}{hint}{error}',
        'labelClass' => 'floatingInput',
        'errorClass' => 'fw-bold fst-italic',
        'hintClass' => 'form-text',
        'fieldConfigs' => [
          SubmitButton::class => [
            'buttonClass()' => ['btn btn-primary btn-lg mt-3'],
            'containerClass()' => ['d-grid gap-2 form-floating'],
          ],
        ],
      ],
    ],
  ],
  'yiisoft/rbac-rules-container' => [
    'rules' => require __DIR__ . '/rbac-rules.php',
  ],
  'yiisoft/router-fastroute' => [
    'enableCache' => false,
  ],
  'yiisoft/translator' => [
    'locale' => 'en',
    'fallbackLocale' => 'en',
    'defaultCategory' => 'app',
    'categorySources' => [
      Reference::to('translation.app'),
    ],
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
      'session' => Reference::to(SessionInterface::class),
      'datehelper' => Reference::to(DateHelper::class),
    ],
  ],
  'yiisoft/cookies' => [
    'secretKey' => '53136271c432a1af377c3806c3112ddf',
  ],
  // works in association with yiisoft/yii-debug-viewer which is not installed 
  // @see blog/common/config/di/router.php  which relies on Yiisoft\Yii\Debug\Viewer\Middleware\ToolbarMiddleware
  'yiisoft/yii-debug' => [
    'enabled' => false,  
  ],  
  'yiisoft/yii-view' => [
    'viewPath' => '@views',
    'layout' => '@views/layout/main',
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
        // 'sqlite' => new SQLiteDriverConfig(
        //     connection: new FileConnectionConfig(
        //        database: 'runtime/database.db'
        //    )
        //),
        // yii-invoice
        'mysql' => new \Cycle\Database\Config\MySQLDriverConfig(
          connection:
          new \Cycle\Database\Config\MySQL\DsnConnectionConfig('mysql:host=localhost;dbname=yii3-i',
            'root',
            ''),
          driver: \Cycle\Database\Driver\MySQL\MySQLDriver::class,
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
     * SchemaProvider list for {@see \Yiisoft\Yii\Cycle\Schema\Provider\Support\SchemaProviderPipeline}
     * Array of classname and {@see SchemaProviderInterface} object.
     * You can configure providers if you pass classname as key and parameters as array:
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
    'schema-providers' => [
      // Uncomment next line to enable a Schema caching in the common cache
      // \Yiisoft\Yii\Cycle\Schema\Provider\SimpleCacheSchemaProvider::class => ['key' => 'cycle-orm-cache-key'],
      // Store generated Schema in the file
      PhpFileSchemaProvider::class => [
        // >>>>>>>>>>  To update a table structure and related schema use MODE_WRITE_ONLY ...then revert back to MODE_READ_AND_WRITE
        //'mode' => PhpFileSchemaProvider::MODE_WRITE_ONLY,
        // yii-invoice
        // For faster performance use MODE_READ_AND_WRITE => 0
        'mode' => PhpFileSchemaProvider::MODE_READ_AND_WRITE,
        'file' => 'runtime/schema.php',
      ],
      FromConveyorSchemaProvider::class => [
        'generators' => [
          Cycle\Schema\Generator\SyncTables::class, // sync table changes to database
        ],
      ],
    ],
    /**
     * Config for {@see \Yiisoft\Yii\Cycle\Schema\Conveyor\AnnotatedSchemaConveyor}
     * Annotated entity directories list.
     * {@see \Yiisoft\Aliases\Aliases} are also supported.
     */
    'entity-paths' => [
      '@src',
    ],
    'conveyor' => AttributedSchemaConveyor::class,
  ],
  'yiisoft/yii-swagger' => [
    'annotation-paths' => [
      '@src/Controller',
      '@src/User/Controller',
    ],
  ],
  'yiisoft/yii-sentry' => [
    'handleConsoleErrors' => false, // Add to disable console errors.
    'options' => [
      // Set to `null` to disable error sending (note that in case of web application errors it only prevents
      // sending them via HTTP). To disable interactions with Sentry SDK completely, remove middleware and the
      // rest of the config.
      'dsn' => $_ENV['SENTRY_DSN'] ?? null,
      'environment' => $_ENV['YII_ENV'] ?? null, // Add to separate "production" / "staging" environment errors.
    ],
  ],
  'yiisoft/mailer' => [
    'messageBodyTemplate' => [
      'viewPath' => '@src/Contact/mail',
    ],
    'fileMailer' => [
      'fileMailerStorage' => '@runtime/mail',
    ],
    'useSendmail' => false,
    'writeToFiles' => false,
  ],
  'symfony/mailer' => [
    'esmtpTransport' => [
      'scheme' => 'smtp', // "smtps": using TLS, "smtp": without using TLS.
      'host' => 'mail.yourinternet.com',
      'port' => 25,
      'username' => 'your.name@yourinternet.com',
      'password' => 'yourpassword',
      'options' => [], // See: https://symfony.com/doc/current/mailer.html#tls-peer-verification
    ],
  ],
  // These parameters appear on ZugFerdXml produced invoice
  // and also Sumex1 semi-compatible invoice and is used in App/Invoice/Libraries/Sumex class
  // see settingRepository->get_config_company_details() function
  // see also src\Invoice\Helpers\PeppolHelper
  'company' => [
    'name' => 'MyCompanyName',
    'address_1' => '1 MyCompany Street',
    'address_2' => 'MyCompany Area',
    'city' => 'MyCompanyCity',
    'country' => 'MyCompanyCountry',
    'zip' => 'A11 1AA',
    'state' => 'My State',
    'vat_id' => 'GB123456789',
    'tax_code' => 'Tax Code',
    'tax_currency' => 'Tax Currency',
    'phone' => '02000000000',
    'fax' => '0200000000',
    'iso_3166_country_identification_code' => 'GB',
    'iso_3166_country_identification_list_id' => 'ISO3166-1:Alpha2'
  ],
  // In association with src/Invoice/Setting/SettingRepository/get_config_peppol()
  // If you add values here, be sure to add them to get_config_peppol()
  // and you will need to create a new function in src/Invoice/Helpers/PeppolHelper
  // The default data inserted here mirrors/replicates the data from:
  // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/tree/
  // Note: Invoices in the UK can be made out in a foreign currency eg. EUR => $documentCurrencyCode with a foreign language of choice;
  //       However it is mandatory/must according to the UK, and according to Peppol to provide
  //       an equivalent/equal VAT amount with the local currency code ie. GBP, namely @see TaxCurrencyCode on the invoice
  'peppol' => [
    'invoice' => [
      'CustomizationID' => 'urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0',
      'ProfileID' => 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
      'InvoiceTypeCode' => '380',
      'Note' => 'Please use our latest telephone number',
      /**
       * @see $settingRepository->get_setting('currency_code_to')
       */
      //'DocumentCurrencyCode' => 'EUR',
      /**
       * @see $settingRepository->get_setting('currency_code_from')
       */
      'TaxCurrencyCode' => 'GBP',
      'AccountingSupplierParty' => [
        'Party' => [
          'EndPointID' => [
            'value' => '7300010000001',
            'schemeID' => '0088'
          ],
          //https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-AccountingSupplierParty/cac-Party/cac-PartyIdentification/
          'PartyIdentification' => [
            'ID' => [
              'value' => '5060012349998',
              // optional
              'schemeID' => '0088'
            ]
          ],
          'PostalAddress' => [
            'StreetName' => 'Main Street 1',
            'AdditionalStreetName' => 'Po Box 351',
            'AddressLine' => [
              'Line' => 'Building 23'
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
               * Error message: [UBL-CR-660]-A UBL invoice should not include the Country Identification code listID
               */
              'ListId' => 'ISO3166-1:Alpha2'
            ],
          ],
          'Contact' => [
            'Name' => 'Joe Bloggs',
            'FirstName' => 'Joe',
            'LastName' => 'Bloggs',
            'Telephone' => '801 801 801',
            /**
             * Warning from Ecosio Validator: OpenPeppol UBL Invoice (3.15.0) (a.k.a BIS Billing 3.0.14) 
             * Location: invoice_a0oVdj0WINV107_peppol
             * Element/context: /:Invoice[1]
             * XPath test: not(cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:Telefax)
             * Error message: [UBL-CR-190]-A UBL invoice should not include the AccountingSupplierParty Party Contact Telefax
             */
            'Telefax' => '',
            'ElectronicMail' => 'test.name@foo.bar'
          ],
          'PartyTaxScheme' => [
            // EU: VAT Number
            'CompanyID' => 'GB999888777',            
            'TaxScheme' => [
              // https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-AccountingSupplierParty/cac-Party/cac-PartyTaxScheme/cac-TaxScheme/cbc-ID/
              // VAT / !VAT
              'ID' => 'VAT',
            ],
          ],
          'PartyLegalEntity' => [
            'RegistrationName' => 'Full Formal Seller Name LTD.',
            'CompanyID' => '987654321',
            /**
             * @see src/Invoice/Ubl/PartyLegalEntity
             * @see src/Invoice/Setting/SettingRepository function get_config_peppol
             * @see src/Invoice/Helpers/PeppolHelper function SupplierPartyLegalEntity()
             */
            'Attributes' => [
              'schemeID' => '0002'
            ],
            'CompanyLegalForm' => 'Share Capital'
          ],
        ],
      ],
      'PayeeParty' => [
        'PartyIdentification' => [
          'ID' => 'FR932874294',
          'schemeID' => 'SEPA'
        ],
        'PartyName' => [
          'Name' => ''
        ],
        'PartyLegalEntity' => [
          'CompanyID' => '',
          'schemeID' => ''
        ],
      ],
      'PaymentMeans' => [
        'PaymentMeansCode' => '30',
        'PaymentID' => '432948234234234',
        'CardAccount' => [
          'PrimaryAccountNumberID' => '1234',
          'NetworkID' => 'NA',
          'HolderName' => 'John Doe'
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
            'ID' => '12345676543'
          ],
        ],
      ],
    ],
  ],
];
