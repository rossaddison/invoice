<?php

declare(strict_types=1);

namespace App\Invoice\Import;

use App\Invoice\BaseController;
use App\Service\WebControllerService;
use App\User\UserService;
// Exceptions
use App\Invoice\Helpers\InvoicePlane\Exception\NoConnectionException;
//Entities
use App\Invoice\Entity\TaxRate;
// Repositories
use App\Invoice\Client\ClientRepository;
use App\Invoice\Family\FamilyRepository;
use App\Invoice\Product\ProductRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\TaxRate\TaxRateRepository;
use App\Invoice\Unit\UnitRepository;
// Psr
use Psr\Http\Message\ResponseInterface as Response;
/**
 * @link https://github.com/yiisoft/db-mysql
 */
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;
use Yiisoft\Db\Mysql\Dsn;
use Yiisoft\Db\Query\Query;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class ImportController extends BaseController
{
    protected string $controllerName = 'invoice/import';

    public function __construct(
        private ClientRepository $cR,
        private UnitRepository $uR,
        private FamilyRepository $fR,
        private ProductRepository $pR,
        private TaxRateRepository $trR,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->cR = $cR;
        $this->uR = $uR;
        $this->fR = $fR;
        $this->pR = $pR;
        $this->trR = $trR;
    }

    private function invoiceplaneConnected(): Connection|null
    {
        $settingInvoiceplaneName = $this->sR->getSetting('invoiceplane_database_name');
        $settingInvoiceplaneUsername = $this->sR->getSetting('invoiceplane_database_username');
        $settingInvoiceplanePassword = $this->sR->getSetting('invoiceplane_database_password') ?: '';
        if (strlen($settingInvoiceplaneName) > 0 && strlen($settingInvoiceplaneUsername) > 0) {
            $dsn = (string) (new Dsn(
                'mysql',
                '127.0.0.1',
                $settingInvoiceplaneName,
                '3306',
                [
                    'charset' => 'utf8mb4',
                ],
            ));
            $arrayCache = new ArrayCache();
            $schemaCache = new SchemaCache($arrayCache);
            $pdoDriver = new Driver($dsn, $settingInvoiceplaneUsername, $settingInvoiceplanePassword);
            return new Connection($pdoDriver, $schemaCache);
        }
        $this->flashMessage('warning', $this->translator->translate('invoiceplane.no.username.or.password'));

        return null;
    }

    public function index(): Response
    {
        $parameters = [
            'action' => ['import/index'],
            'alert' => $this->alert(),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    public function invoiceplane(): Response
    {
        if (strlen($this->sR->getSetting('invoiceplane_database_name')) > 0
            && strlen($this->sR->getSetting('invoiceplane_database_username')) > 0) {
            $db = $this->invoiceplaneConnected();
            if (count($this->uR->findAllPreloaded()) == 0
             && count($this->fR->findAllPreloaded()) == 0
             && count($this->pR->findAllPreloaded()) == 0
             && count($this->cR->findAllPreloaded()) == 0
             && count($this->trR->findAllPreloaded()) == 0) {
                if (null !== $db) {
                    $units = $this->inputUnit($db);
                    $this->InsertUnits($units);
                    $families = $this->inputFamily($db);
                    $this->InsertFamilies($families);
                    $taxRates = $this->inputTaxRate($db);
                    $this->InsertTaxRates($taxRates);
                    $products = $this->inputProduct($db);
                    $this->InsertProducts($products);
                    $clients = $this->inputClient($db);
                    $this->InsertClients($clients);
                    $db->close();
                    $this->flashMessage(
                        'success',
                        $this->translator->translate('invoiceplane.import.complete.connection.closed'),
                    );
                } else {
                    $this->flashMessage('info', $this->translator->translate('invoiceplane.no.connection'));
                }
            } else {
                $this->flashMessage('warning', $this->translator->translate('invoiceplane.tables.not.empty'));
            }
        } else {
            $this->flashMessage('warning', $this->translator->translate('invoiceplane.no.username.or.password'));
        }
        return $this->webService->getRedirectResponse('import/index');
    }

    public function testConnection(): Response
    {
        $db = $this->invoiceplaneConnected();
        if (null !== $db) {
            // Test to the Query Level on any Table to ensure a username and password validated connection
            $this->inputProduct($db);
            $this->flashMessage('info', $this->translator->translate('invoiceplane.yes.connection'));
        } else {
            $this->flashMessage('info', $this->translator->translate('invoiceplane.no.connection'));
        }
        return $this->webService->getRedirectResponse('setting/tab_index');
    }

    private function inputUnit(Connection $db): array
    {
        try {
            return (new Query($db))
            ->select(['unit_name', 'unit_name_plrl'])
            ->from('{{%ip_units}}')
            ->all();
        } catch (\Yiisoft\Db\Exception\Exception $e) {
            throw new NoConnectionException($this->translator, $e);
        }
    }

    private function inputFamily(Connection $db): array
    {
        try {
            return (new Query($db))
            ->select(['family_name'])
            ->from('{{%ip_families}}')
            ->all();
        } catch (\Yiisoft\Db\Exception\Exception $e) {
            throw new NoConnectionException($this->translator, $e);
        }
    }

    private function ensureMandatoryTaxRatesInstalled(): void
    {
        /**
         * @var TaxRate $taxRateService
         */
        $taxRateService = new TaxRate();
        $taxRateService->setTaxRateName('Zero');
        $taxRateService->setTaxRatePercent(0);
        $taxRateService->setTaxRateDefault(false);
        $this->trR->save($taxRateService);
        /**
         * @var TaxRate $taxRateStandard
         */
        $taxRateStandard = new TaxRate();
        $taxRateStandard->setTaxRateName('Standard');
        $taxRateStandard->setTaxRatePercent(20);
        $taxRateStandard->setTaxRateDefault(true);
        $this->trR->save($taxRateStandard);
    }

    private function inputTaxRate(Connection $db): array
    {
        $this->ensureMandatoryTaxRatesInstalled();
        try {
            return (new Query($db))
            ->select(['tax_rate_name', 'tax_rate_percent'])
            ->from('{{%ip_tax_rates}}')
            ->all();
        } catch (\Yiisoft\Db\Exception\Exception $e) {
            throw new NoConnectionException($this->translator, $e);
        }
    }

    private function inputClient(Connection $db): array
    {
        try {
            return (new Query($db))
            ->select([
                'client_date_created',
                'client_date_modified',
                'client_name',
                'client_surname',
                'client_address_1',
                'client_address_2',
                'client_city',
                'client_state',
                'client_zip',
                'client_country',
                'client_phone',
                'client_fax',
                'client_mobile',
                'client_email',
                'client_web',
                'client_vat_id',
                'client_tax_code',
                'client_language',
                'client_active',
                'client_avs',
                'client_insurednumber',
                'client_veka',
                'client_birthdate',
                'client_gender',
            ])
            ->from('{{%ip_clients}}')
            ->all();
        } catch (\Yiisoft\Db\Exception\Exception $e) {
            throw new NoConnectionException($this->translator, $e);
        }
    }

    private function inputProduct(Connection $db): array
    {
        try {
            return (new Query($db))
            ->select([
                'family_id',
                'product_sku',
                'product_name',
                'product_description',
                'product_price',
                'purchase_price',
                'provider_name',
                'tax_rate_id',
                'unit_id',
                'product_tariff'])
            ->from('{{%ip_products}}')
            ->all();
        } catch (\Yiisoft\Db\Exception\Exception $e) {
            throw new NoConnectionException($this->translator, $e);
        }
    }

    private function InsertUnits(array $units): void
    {
        /**
         * @var array $unit
         */
        foreach ($units as $unit) {
            $newUnit = new \App\Invoice\Entity\Unit();
            $newUnit->setUnit_name((string) $unit['unit_name']);
            $newUnit->setUnit_name_plrl((string) $unit['unit_name_plrl']);
            $this->uR->save($newUnit);
        }
        $this->flashMessage('info', $this->translator->translate('invoiceplane.units'));
    }

    private function InsertFamilies(array $families): void
    {
        /**
         * @var array $family
         */
        foreach ($families as $family) {
            $newFamily = new \App\Invoice\Entity\Family();
            $newFamily->setFamily_name((string) $family['family_name']);
            $this->fR->save($newFamily);
        }
        $this->flashMessage('info', $this->translator->translate('invoiceplane.families'));
    }

    private function InsertTaxRates(array $taxRates): void
    {
        /**
         * @var array $taxRate
         */
        foreach ($taxRates as $taxRate) {
            $newTaxRate = new TaxRate();
            $newTaxRate->setTaxRateName((string) $taxRate['tax_rate_name']);
            $newTaxRate->setTaxRateDefault(false);
            $this->trR->save($newTaxRate);
        }
        $this->flashMessage('info', $this->translator->translate('invoiceplane.taxrates'));
    }

    private function InsertClients(array $clients): void
    {
        /**
         * @var array $client
         */
        foreach ($clients as $client) {
            $newClient = new \App\Invoice\Entity\Client();
            $newClient->setClient_date_created((string) $client['client_date_created']);
            $newClient->setClient_date_modified((string) $client['client_date_modified']);
            $newClient->setClient_name((string) $client['client_name']);
            $newClient->setClient_surname((string) $client['client_surname']);
            $newClient->setClient_address_1((string) $client['client_address_1']);
            $newClient->setClient_address_2((string) $client['client_address_2']);
            $newClient->setClient_city((string) $client['client_city']);
            $newClient->setClient_state((string) $client['client_state']);
            $newClient->setClient_zip((string) $client['client_zip']);
            $newClient->setClient_country((string) $client['client_country']);
            $newClient->setClient_phone((string) $client['client_phone']);
            $newClient->setClient_fax((string) $client['client_fax']);
            $newClient->setClient_mobile((string) $client['client_mobile']);
            $newClient->setClient_email((string) $client['client_email']);
            $newClient->setClient_web((string) $client['client_web']);
            $newClient->setClient_vat_id((string) $client['client_vat_id']);
            $newClient->setClient_tax_code((string) $client['client_tax_code']);
            $newClient->setClient_language((string) $client['client_language']);
            $newClient->setClient_active($client['client_active'] === '1' ? true : false);
            $newClient->setClient_avs((string) $client['client_avs']);
            $newClient->setClient_insurednumber((string) $client['client_insurednumber']);
            $newClient->setClient_veka((string) $client['client_veka']);
            $newClient->setClient_birthdate(new \DateTime((string) $client['client_birthdate']));
            $newClient->setClient_gender((int) $client['client_gender']);
            $this->cR->save($newClient);
        }
        $this->flashMessage('info', $this->translator->translate('invoiceplane.clients'));
    }

    private function InsertProducts(array $products): void
    {
        /**
         * @var array $product
         */
        foreach ($products as $product) {
            $newProduct = new \App\Invoice\Entity\Product();
            $newProduct->setFamily_id((int) $product['family_id']);
            $newProduct->setProduct_sku((string) $product['product_sku']);
            $newProduct->setProduct_name((string) $product['product_name']);
            $newProduct->setProduct_description((string) $product['product_description']);
            $newProduct->setProduct_price((float) $product['product_price']);
            $newProduct->setPurchase_price((float) $product['purchase_price']);
            $newProduct->setProvider_name((string) $product['provider_name']);
            $newProduct->setTax_rate_id((int) $product['tax_rate_id']);
            $newProduct->setUnit_id((int) $product['unit_id']);
            $newProduct->setProduct_tariff((float) $product['product_tariff']);
            $this->pR->save($newProduct);
        }
        $this->flashMessage('info', $this->translator->translate('invoiceplane.products'));
    }
}
