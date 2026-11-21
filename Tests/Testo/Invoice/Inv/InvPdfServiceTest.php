<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAllowanceCharge\InvAllowanceCharge;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Infrastructure\Persistence\InvCustom\InvCustom;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Invoice\Inv\InvPdfCoreDeps;
use App\Invoice\Inv\InvPdfDocDeps;
use App\Invoice\Inv\InvPdfItemDeps;
use App\Invoice\Inv\InvPdfService;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserInv\UserInvRepository;
use Mockery as m;
use ReflectionMethod;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * The invoice PDF and HTML renderer: which template an invoice uses, what is handed to it, the allowance and
 * charge and delivery location blocks, custom values, and the draft numbering done on the way to a PDF. The
 * repositories and the view renderer are mocks. The actual mPDF output is not produced here: the tests that
 * go through generate() stop before it, because the invoice is deliberately not found at that point.
 */
#[Test]
final class InvPdfServiceTest
{
    private const int INV_ID = 15;

    /** @var list<array{0: string, 1: array<string, mixed>}> */
    private array $rendered = [];

    private function translator(): TranslatorInterface
    {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);
        return $translator;
    }

    /**
     * @param array<string, string> $settings
     * @return SettingRepository&m\MockInterface
     */
    private function settings(array $settings = []): SettingRepository
    {
        $settings += ['date_format' => 'Y-m-d'];
        $s = TypedMock::of(SettingRepository::class);
        $s->shouldReceive('getSetting')->andReturnUsing(static fn (string $key): string => $settings[$key] ?? '');
        $s->shouldReceive('loadSettings');
        $s->shouldReceive('getPrivateCompanyDetails')->andReturn(['name' => 'Private Co']);
        $s->shouldReceive('getConfigCompanyDetails')->andReturn(['name' => 'Config Co']);
        $s->shouldReceive('localeLanguageArray')->andReturn(['en' => 'English', 'de' => 'German']);
        return $s;
    }

    private function renderer(): WebViewRenderer
    {
        $this->rendered = [];
        $renderer = TypedMock::of(WebViewRenderer::class);
        $renderer->shouldReceive('renderPartialAsString')->andReturnUsing(
            function (string $view, array $parameters = []): string {
                /** @var array<string, mixed> $parameters */
                $this->rendered[] = [$view, $parameters];
                return 'R:' . $view;
            },
        );
        return $renderer;
    }

    private function session(string $language = 'en'): SessionInterface
    {
        $session = TypedMock::of(SessionInterface::class);
        $session->shouldReceive('get')->with('_language')->andReturn($language);
        $session->shouldReceive('set');
        return $session;
    }

    /**
     * @param array<string, string> $settings
     * @param array<string, object> $core
     * @param array<string, object> $doc
     * @param array<string, object> $item
     */
    private function service(array $settings = [], array $core = [], array $doc = [], array $item = [], ?SettingRepository $s = null, string $language = 'en'): InvPdfService
    {
        return new InvPdfService(
            $s ?? $this->settings($settings),
            $this->session($language),
            $this->translator(),
            $this->renderer(),
            TypedMock::build(InvPdfCoreDeps::class, $core),
            TypedMock::build(InvPdfDocDeps::class, $doc),
            TypedMock::build(InvPdfItemDeps::class, $item),
        );
    }

    /** @param list<object> $items */
    private function reader(array $items): EntityReader
    {
        $reader = TypedMock::of(EntityReader::class);
        $reader->shouldReceive('getIterator')->andReturnUsing(static function () use ($items): \Generator {
            yield from $items;
        });
        return $reader;
    }

    private function invoice(int $statusId = 2, string $number = 'INV-100', int $soId = 0, int $deliveryLocationId = 0, ?Client $client = null): Inv
    {
        $inv = new Inv();
        $inv->setId(self::INV_ID);
        $inv->setUserId(3);
        $inv->setGroupId(9);
        $inv->setStatusId($statusId);
        $inv->setNumber($number);
        if ($soId > 0) {
            $inv->setSoId($soId);
        }
        if ($deliveryLocationId > 0) {
            $inv->setDeliveryLocationId($deliveryLocationId);
        }
        $inv->setClient($client);
        return $inv;
    }

    /** @return array<string, object> repositories that serve one complete invoice */
    private function invoiceRepos(Inv $inv, int $count = 1): array
    {
        $amount = TypedMock::of(InvAmount::class);
        $iaR = TypedMock::of(\App\Invoice\InvAmount\InvAmountRepository::class);
        $iaR->shouldReceive('repoInvAmountCount')->with(self::INV_ID)->andReturn(1);
        $iaR->shouldReceive('repoInvquery')->with(self::INV_ID)->andReturn($amount);
        $iR = TypedMock::of(InvRepository::class);
        $iR->shouldReceive('repoInvUnloadedquery')->with(self::INV_ID)->andReturn($inv);
        $iR->shouldReceive('repoInvLoadedquery')->with(self::INV_ID)->andReturn($inv);
        $iR->shouldReceive('repoCount')->with(self::INV_ID)->andReturn($count);
        return ['iR' => $iR, 'iaR' => $iaR];
    }

    /** @return array<string, object> */
    private function documentRepos(): array
    {
        $cfR = TypedMock::of(\App\Invoice\CustomField\CustomFieldRepository::class);
        $cfR->shouldReceive('repoTablequery')->andReturn($this->reader([]));
        $cvR = TypedMock::of(\App\Invoice\CustomValue\CustomValueRepository::class);
        $cvR->shouldReceive('fixCfValueToCf')->andReturn([]);
        $cR = TypedMock::of(\App\Invoice\Client\ClientRepository::class);
        $cR->shouldReceive('repoClientqueryOrig')->andReturn(null);
        $icR = TypedMock::of(\App\Invoice\InvCustom\InvCustomRepository::class);
        $icR->shouldReceive('repoInvCount')->andReturn(0);
        return ['cfR' => $cfR, 'cvR' => $cvR, 'cR' => $cR, 'icR' => $icR];
    }

    /**
     * @param list<object> $items
     * @return array<string, object>
     */
    private function itemRepos(int $itemCount = 0, array $items = []): array
    {
        $iiR = TypedMock::of(\App\Invoice\InvItem\InvItemRepository::class);
        $iiR->shouldReceive('repoCount')->andReturn($itemCount);
        $iiR->shouldReceive('repoInvItemIdquery')->andReturn($this->reader($items));
        $itrR = TypedMock::of(\App\Invoice\InvTaxRate\InvTaxRateRepository::class);
        $itrR->shouldReceive('repoCount')->andReturn(0);
        $aciR = TypedMock::of(InvAllowanceChargeRepository::class);
        $aciR->shouldReceive('repoACIquery')->andReturn($this->reader([]));
        return ['iiR' => $iiR, 'itrR' => $itrR, 'aciR' => $aciR];
    }

    /** @param list<mixed> $args */
    private function privately(InvPdfService $service, string $method, array $args): mixed
    {
        return (new ReflectionMethod($service, $method))->invoke($service, ...$args);
    }

    /** @return array<string, mixed> the data passed to the invoice template */
    private function templateData(): array
    {
        $last = end($this->rendered);
        return $last === false ? [] : $last[1];
    }

    private function templateView(): string
    {
        $last = end($this->rendered);
        return $last === false ? '' : $last[0];
    }

    // ── trivial accessors ────────────────────────────────────────────────────

    public function theServiceExposesItsRepositoriesAndFindsInvoices(): void
    {
        $inv = $this->invoice();
        $iR = TypedMock::of(InvRepository::class);
        $iR->shouldReceive('repoUrlKeyGuestLoaded')->with('abc')->andReturn($inv);
        $iR->shouldReceive('repoInvUnloadedquery')->with(self::INV_ID)->andReturn($inv);
        $uiR = TypedMock::of(UserInvRepository::class);
        $ucR = TypedMock::of(\App\Invoice\UserClient\UserClientRepository::class);
        $service = $this->service(core: ['iR' => $iR, 'uiR' => $uiR, 'ucR' => $ucR]);

        Assert::same($service->loadGuestInv('abc'), $inv);
        Assert::same($service->findInv(self::INV_ID), $inv);
        Assert::same($service->uiR(), $uiR);
        Assert::same($service->ucR(), $ucR);
    }

    // ── generateHtml ─────────────────────────────────────────────────────────

    public function noHtmlIsRenderedWhenTheInvoiceOrItsAmountIsMissing(): void
    {
        $iaR = TypedMock::of(\App\Invoice\InvAmount\InvAmountRepository::class);
        $iaR->shouldReceive('repoInvAmountCount')->andReturn(0);
        $iR = TypedMock::of(InvRepository::class);
        $iR->shouldReceive('repoInvUnloadedquery')->andReturn($this->invoice());
        $iR->shouldReceive('repoCount')->andReturn(1);
        $iR->shouldReceive('repoInvLoadedquery')->andReturn($this->invoice());

        $service = $this->service(core: ['iR' => $iR, 'iaR' => $iaR]);

        Assert::same($service->generateHtml(self::INV_ID, false), '');
    }

    public function noHtmlIsRenderedWhenTheInvoiceCannotBeLoaded(): void
    {
        $service = $this->service(core: $this->invoiceRepos($this->invoice(), count: 0));

        Assert::same($service->generateHtml(self::INV_ID, false), '');
    }

    public function theInvoiceTemplateReceivesTheInvoiceItsAmountAndTheRenderedBlocks(): void
    {
        $inv = $this->invoice();
        $service = $this->service(
            core: $this->invoiceRepos($inv) + ['uiR' => $this->userInvRepo()],
            doc: $this->documentRepos(),
            item: $this->itemRepos(),
        );

        $html = $service->generateHtml(self::INV_ID, true);
        $data = $this->templateData();

        Assert::same($html, 'R://invoice/template/invoice/pdf/invoice');
        Assert::same($data['inv'], $inv);
        Assert::same($data['output_type'], 'pdf');
        Assert::same($data['show_custom_fields'], true);
        Assert::same($data['show_item_discounts'], false);
        Assert::same($data['items'], null);
        Assert::same($data['inv_tax_rates'], []);
        Assert::same($data['delivery_location'], '');
        Assert::same($data['inv_allowance_charges'], '');
        Assert::same($data['top_custom_fields'], 'R://invoice/template/invoice/pdf/top_custom_fields');
        Assert::same($data['company_logo_and_address'], 'R://invoice/setting/company_logo_and_address.php');
    }

    private function userInvRepo(bool $found = false): UserInvRepository
    {
        $uiR = TypedMock::of(UserInvRepository::class);
        $uiR->shouldReceive('repoUserInvcount')->andReturn($found ? 1 : 0);
        $uiR->shouldReceive('repoUserInvquery')->andReturn(null);
        return $uiR;
    }

    public function theCompanyBlockUsesThePrivateCompanyDetailsAndTheInvoiceDetails(): void
    {
        $inv = $this->invoice(number: 'INV-100');
        $service = $this->service(
            core: $this->invoiceRepos($inv) + ['uiR' => $this->userInvRepo()],
            doc: $this->documentRepos(),
            item: $this->itemRepos(),
        );

        $service->generateHtml(self::INV_ID, false);
        $company = null;
        foreach ($this->rendered as [$view, $params]) {
            if ($view === '//invoice/setting/company_logo_and_address.php') {
                $company = $params;
            }
        }

        Assert::same($company['company'] ?? null, ['name' => 'Private Co']);
        Assert::same($company['document_number'] ?? null, 'INV-100');
        Assert::same($company['_language'] ?? null, 'en');
        Assert::same($company['inv_id'] ?? null, self::INV_ID);
        Assert::same($company['isInvoice'] ?? null, true);
        Assert::same($company['isQuote'] ?? null, false);
    }

    public function anItemWithADiscountTurnsOnTheItemDiscountColumn(): void
    {
        $discounted = TypedMock::of(InvItem::class);
        $discounted->shouldReceive('getDiscountAmount')->andReturn(5.0);
        $plain = TypedMock::of(InvItem::class);
        $plain->shouldReceive('getDiscountAmount')->andReturn(0.0);
        $service = $this->service(
            core: $this->invoiceRepos($this->invoice()) + ['uiR' => $this->userInvRepo()],
            doc: $this->documentRepos(),
            item: $this->itemRepos(2, [$plain, $discounted]),
        );

        $service->generateHtml(self::INV_ID, false);

        Assert::same($this->templateData()['show_item_discounts'], true);
    }

    public function itemsWithNoDiscountLeaveTheDiscountColumnOff(): void
    {
        $plain = TypedMock::of(InvItem::class);
        $plain->shouldReceive('getDiscountAmount')->andReturn(0.0);
        $service = $this->service(
            core: $this->invoiceRepos($this->invoice()) + ['uiR' => $this->userInvRepo()],
            doc: $this->documentRepos(),
            item: $this->itemRepos(1, [$plain]),
        );

        $service->generateHtml(self::INV_ID, false);

        Assert::same($this->templateData()['show_item_discounts'], false);
    }

    public function aSalesOrderPurchaseOrderNumberIsPassedToTheCompanyBlock(): void
    {
        $inv = $this->invoice(soId: 8);
        $so = TypedMock::of(SalesOrder::class);
        $so->shouldReceive('getClientPoNumber')->andReturn('PO-77');
        $soR = TypedMock::of(\App\Invoice\SalesOrder\SalesOrderRepository::class);
        $soR->shouldReceive('repoSalesOrderUnloadedquery')->with(8)->andReturn($so);
        $service = $this->service(
            core: $this->invoiceRepos($inv) + ['uiR' => $this->userInvRepo(), 'soR' => $soR],
            doc: $this->documentRepos(),
            item: $this->itemRepos(),
        );

        $service->generateHtml(self::INV_ID, false);
        $company = [];
        foreach ($this->rendered as [$view, $params]) {
            if ($view === '//invoice/setting/company_logo_and_address.php') {
                $company = $params;
            }
        }

        Assert::same($company['client_purchase_order_number'] ?? null, 'PO-77');
    }

    public function theClientsOwnLanguageDecidesTheCldrLanguageElseTheSessionLanguage(): void
    {
        $german = TypedMock::of(Client::class);
        $german->shouldReceive('getClientLanguage')->andReturn('German');
        $german->shouldReceive('reqId')->andReturn(7);
        $noLanguage = TypedMock::of(Client::class);
        $noLanguage->shouldReceive('getClientLanguage')->andReturn(null);
        $noLanguage->shouldReceive('reqId')->andReturn(7);

        $withClientLanguage = $this->service(
            core: $this->invoiceRepos($this->invoice(client: $german)) + ['uiR' => $this->userInvRepo()],
            doc: $this->documentRepos(),
            item: $this->itemRepos(),
        );
        $withClientLanguage->generateHtml(self::INV_ID, false);
        $clientCldr = $this->templateData()['cldr'];

        $sessionLanguage = $this->service(
            core: $this->invoiceRepos($this->invoice(client: $noLanguage)) + ['uiR' => $this->userInvRepo()],
            doc: $this->documentRepos(),
            item: $this->itemRepos(),
        );
        $sessionLanguage->generateHtml(self::INV_ID, false);
        $sessionCldr = $this->templateData()['cldr'];

        Assert::same($clientCldr, 'de');
        Assert::same($sessionCldr, 'en');
    }

    public function theUsersInvoiceSettingsAndTheInvoiceTaxRatesAreIncludedWhenTheyExist(): void
    {
        $userInv = TypedMock::of(\App\Infrastructure\Persistence\UserInv\UserInv::class);
        $uiR = TypedMock::of(UserInvRepository::class);
        $uiR->shouldReceive('repoUserInvcount')->with(3)->andReturn(1);
        $uiR->shouldReceive('repoUserInvquery')->with(3)->andReturn($userInv);
        $taxRate = TypedMock::of(\App\Infrastructure\Persistence\InvTaxRate\InvTaxRate::class);
        $itemRepos = $this->itemRepos();
        $itrR = TypedMock::of(\App\Invoice\InvTaxRate\InvTaxRateRepository::class);
        $itrR->shouldReceive('repoCount')->with(self::INV_ID)->andReturn(1);
        $itrR->shouldReceive('repoInvquery')->with(self::INV_ID)->andReturn($this->reader([$taxRate]));
        $service = $this->service(
            core: $this->invoiceRepos($this->invoice()) + ['uiR' => $uiR],
            doc: $this->documentRepos(),
            item: ['itrR' => $itrR] + $itemRepos,
        );

        $service->generateHtml(self::INV_ID, false);

        Assert::same($this->templateData()['userinv'], $userInv);
        Assert::true($this->templateData()['inv_tax_rates'] instanceof EntityReader);
    }

    public function theMarkSentStepDoesNothingForAnInvalidIdOrAMissingInvoice(): void
    {
        $iR = TypedMock::of(InvRepository::class);
        $iR->shouldReceive('repoInvUnloadedquery')->andReturn(null);
        $iR->shouldNotReceive('save');
        $service = $this->service(core: ['iR' => $iR]);

        $this->privately($service, 'markSentIfApplicable', [0]);
        $this->privately($service, 'markSentIfApplicable', [self::INV_ID]);

        Assert::true(true);
    }

    // ── template choice ──────────────────────────────────────────────────────

    /** @param array<string, string> $settings */
    private function templateFor(int $statusId, array $settings = []): string
    {
        $service = $this->service($settings);
        return (string) $this->privately($service, 'resolveTemplate', [$statusId]);
    }

    public function aPaidInvoiceUsesTheConfiguredPaidTemplateOtherwiseThePaidOne(): void
    {
        Assert::same($this->templateFor(4, ['pdf_invoice_template_paid' => 'my-paid']), 'my-paid');
        Assert::same($this->templateFor(4), 'paid');
    }

    public function anOverdueInvoiceUsesTheConfiguredOverdueTemplateOtherwiseTheOverdueOne(): void
    {
        Assert::same($this->templateFor(5, ['pdf_invoice_template_overdue' => 'my-overdue']), 'my-overdue');
        Assert::same($this->templateFor(5), 'overdue');
    }

    public function anyOtherStatusUsesTheDefaultTemplateOrInvoice(): void
    {
        Assert::same($this->templateFor(2, ['pdf_invoice_template' => 'custom']), 'custom');
        Assert::same($this->templateFor(2), 'invoice');
        Assert::same($this->templateFor(1), 'invoice');
    }

    public function aPaidInvoiceRendersWithThePaidTemplate(): void
    {
        $service = $this->service(
            core: $this->invoiceRepos($this->invoice(statusId: 4)) + ['uiR' => $this->userInvRepo()],
            doc: $this->documentRepos(),
            item: $this->itemRepos(),
        );

        $service->generateHtml(self::INV_ID, false);

        Assert::same($this->templateView(), '//invoice/template/invoice/pdf/paid');
    }

    // ── allowances and charges ───────────────────────────────────────────────

    private function allowance(int $identifier, float $amount, string $vatOrTax): InvAllowanceCharge
    {
        $allowanceCharge = TypedMock::of(AllowanceCharge::class);
        $allowanceCharge->shouldReceive('getIdentifier')->andReturn($identifier);
        $charge = TypedMock::of(InvAllowanceCharge::class);
        $charge->shouldReceive('getAllowanceCharge')->andReturn($allowanceCharge);
        $charge->shouldReceive('getAmount')->andReturn($amount);
        $charge->shouldReceive('getVatOrTax')->andReturn($vatOrTax);
        return $charge;
    }

    /** @param list<InvAllowanceCharge> $charges */
    private function allowanceBlock(array $charges, string $vat): string
    {
        $aciR = TypedMock::of(\App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository::class);
        $aciR->shouldReceive('repoACIquery')->andReturn($this->reader($charges));
        $service = $this->service(item: ['aciR' => $aciR]);
        $result = (string) $this->privately($service, 'renderAllowanceCharges', [self::INV_ID, $vat]);
        return $result === '' ? '' : (string) ($this->rendered[0][1]['inv_allowance_charges'] ?? '');
    }

    public function noAllowanceBlockIsRenderedWithoutAnInvoiceIdOrAnyCharges(): void
    {
        $service = $this->service();

        Assert::same($this->privately($service, 'renderAllowanceCharges', [0, '']), '');
        Assert::same($this->allowanceBlock([], ''), '');
    }

    public function anAllowanceAndAChargeAreListedWithTheirVatOrTaxHeading(): void
    {
        $block = $this->allowanceBlock([$this->allowance(1, 5.0, '1'), $this->allowance(0, 3.0, '2')], '1');

        Assert::same(
            $block,
            'allowance.or.charge.allowance: allowance.or.charge.amount 5, allowance.or.charge.allowance.vat: 1<br>'
            . 'allowance.or.charge.charge: allowance.or.charge.amount 3, allowance.or.charge.charge.vat: 2<br>',
        );
    }

    public function withoutVatTheHeadingsAreTheTaxOnes(): void
    {
        $block = $this->allowanceBlock([$this->allowance(1, 5.0, '1'), $this->allowance(0, 3.0, '2')], '');

        Assert::true(str_contains($block, 'allowance.or.charge.tax: 1'));
        Assert::true(str_contains($block, 'allowance.or.charge.charge.tax: 2'));
    }

    // ── delivery location ────────────────────────────────────────────────────

    public function noDeliveryLocationBlockIsRenderedWithoutAFoundLocation(): void
    {
        $dlR = TypedMock::of(\App\Invoice\DeliveryLocation\DeliveryLocationRepository::class);
        $dlR->shouldReceive('repoDeliveryLocationquery')->andReturn(null);
        $service = $this->service(doc: ['dlR' => $dlR]);

        Assert::same($this->privately($service, 'renderDeliveryLocation', ['en', 0]), '');
        Assert::same($this->privately($service, 'renderDeliveryLocation', ['en', -3]), '');
        Assert::same($this->privately($service, 'renderDeliveryLocation', ['en', 6]), '');
    }

    public function aDeliveryLocationIsRenderedWithItsAddress(): void
    {
        $location = new DeliveryLocation();
        $location->setBuildingNumber('1');
        $location->setAddress1('1 Virtual Street');
        $location->setAddress2('Flat 2');
        $location->setCity('Virtualton');
        $location->setZip('ZZ1 1AA');
        $location->setCountry('GB');
        $location->setGlobalLocationNumber('5060000000001');
        $dlR = TypedMock::of(\App\Invoice\DeliveryLocation\DeliveryLocationRepository::class);
        $dlR->shouldReceive('repoDeliveryLocationquery')->with(6)->andReturn($location);
        $service = $this->service(doc: ['dlR' => $dlR]);

        $html = $this->privately($service, 'renderDeliveryLocation', ['en', 6]);
        $params = $this->rendered[0][1];

        Assert::same($html, 'R://invoice/inv/partial_inv_delivery_location');
        Assert::same($params['actionName'], 'del/view');
        Assert::same($params['actionArguments'], ['_language' => 'en', 'id' => 6]);
        Assert::same($params['title'], 'delivery.location');
        Assert::same($params['address_1'], '1 Virtual Street');
        Assert::same($params['city'], 'Virtualton');
        Assert::same($params['state'], 'ZZ1 1AA');
        Assert::same($params['global_location_number'], '5060000000001');
    }

    // ── custom values ────────────────────────────────────────────────────────

    public function customValuesAreCollectedOnlyForAnInvoiceThatHasSome(): void
    {
        $value = TypedMock::of(InvCustom::class);
        $none = TypedMock::of(\App\Invoice\InvCustom\InvCustomRepository::class);
        $none->shouldReceive('repoInvCount')->andReturn(0);
        $some = TypedMock::of(\App\Invoice\InvCustom\InvCustomRepository::class);
        $some->shouldReceive('repoInvCount')->with(self::INV_ID)->andReturn(1);
        $some->shouldReceive('repoFields')->with(self::INV_ID)->andReturn($this->reader([$value]));

        Assert::same($this->privately($this->service(doc: ['icR' => $none]), 'customValues', [self::INV_ID]), []);
        Assert::same($this->privately($this->service(doc: ['icR' => $none]), 'customValues', [0]), []);
        Assert::same($this->privately($this->service(doc: ['icR' => $some]), 'customValues', [self::INV_ID]), [$value]);
    }

    // ── generate: early exits and draft numbering ────────────────────────────

    public function generateGivesNothingWhenThereIsNoAmountOrNoInvoice(): void
    {
        $iaR = TypedMock::of(\App\Invoice\InvAmount\InvAmountRepository::class);
        $iaR->shouldReceive('repoInvAmountCount')->andReturn(0);
        $noAmount = $this->service(core: ['iaR' => $iaR]);

        $repos = $this->invoiceRepos($this->invoice());
        $iR = TypedMock::of(InvRepository::class);
        $iR->shouldReceive('repoInvUnloadedquery')->andReturn(null);
        $noInvoice = $this->service(core: ['iR' => $iR, 'iaR' => $repos['iaR']], doc: $this->documentRepos());

        Assert::same($noAmount->generate(self::INV_ID, false, false), '');
        Assert::same($noInvoice->generate(self::INV_ID, false, false), '');
    }

    public function generateLooksUpTheSalesOrderOfAnInvoiceThatHasOne(): void
    {
        $inv = $this->invoice(soId: 8);
        $repos = $this->invoiceRepos($inv, count: 0);
        $soR = TypedMock::of(\App\Invoice\SalesOrder\SalesOrderRepository::class);
        $soR->shouldReceive('repoSalesOrderUnloadedquery')->once()->with(8)->andReturn(null);
        $service = $this->service(core: $repos + ['soR' => $soR], doc: $this->documentRepos());

        Assert::same($service->generate(self::INV_ID, false, false), '');
    }

    /** @param array<string, string> $settings */
    private function generateWithMarkSent(Inv $inv, array $settings, ?object $save = null): InvRepository
    {
        $s = $this->settings($settings + ['mark_invoices_sent_pdf' => '1']);
        $s->shouldReceive('invoiceMarkSent')->once();
        $iaR = TypedMock::of(\App\Invoice\InvAmount\InvAmountRepository::class);
        $iaR->shouldReceive('repoInvAmountCount')->andReturn(1);
        $iaR->shouldReceive('repoInvquery')->andReturn(TypedMock::of(InvAmount::class));
        $iR = TypedMock::of(InvRepository::class);
        $iR->shouldReceive('repoInvUnloadedquery')->andReturn($inv);
        // markSentIfApplicable() sees the invoice; createPdf() then finds it gone, so no PDF is produced.
        $iR->shouldReceive('repoCount')->andReturn(1, 0);
        $iR->shouldReceive('getInvNumber')->andReturn('INV-0001');
        $service = $this->service(core: ['iR' => $iR, 'iaR' => $iaR], doc: $this->documentRepos(), s: $s);
        if ($save !== null) {
            $iR->shouldReceive('save')->once()->with($inv);
        } else {
            $iR->shouldNotReceive('save');
        }
        Assert::same($service->generate(self::INV_ID, false, false), '');
        return $iR;
    }

    public function aDraftWithoutANumberGetsTheNextNumberWhenThePdfIsGenerated(): void
    {
        $inv = $this->invoice(statusId: 1, number: '');

        $this->generateWithMarkSent($inv, ['generate_invoice_number_for_draft' => '0'], save: $inv);

        Assert::same($inv->getNumber(), 'INV-0001');
    }

    public function aDraftIsNotNumberedWhenDraftNumbersAreSwitchedOn(): void
    {
        $inv = $this->invoice(statusId: 1, number: '');

        $this->generateWithMarkSent($inv, ['generate_invoice_number_for_draft' => '1'], save: $inv);

        Assert::same($inv->getNumber(), '');
    }

    public function anInvoiceThatIsNotADraftOrAlreadyHasANumberIsNotRenumbered(): void
    {
        $sent = $this->invoice(statusId: 2, number: '');
        $numbered = $this->invoice(statusId: 1, number: 'INV-050');

        $this->generateWithMarkSent($sent, ['generate_invoice_number_for_draft' => '0']);
        $this->generateWithMarkSent($numbered, ['generate_invoice_number_for_draft' => '0']);

        Assert::same($sent->getNumber(), '');
        Assert::same($numbered->getNumber(), 'INV-050');
    }
}
