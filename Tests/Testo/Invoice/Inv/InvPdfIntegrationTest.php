<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Helpers\MpdfHelper;
use App\Invoice\Helpers\PdfCreateContext;
use App\Invoice\Helpers\ZugFerdHelper;
use App\Invoice\Inv\InvPdfCoreDeps;
use App\Invoice\Inv\InvPdfDocDeps;
use App\Invoice\Inv\InvPdfItemDeps;
use App\Invoice\Inv\InvPdfService;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository;
use App\Invoice\InvItemAmount\InvItemAmountRepository;
use App\Invoice\Libraries\ZugferdXml;
use App\Invoice\Setting\SettingRepository;
use DOMDocument;
use DOMXPath;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * The real thing, with only the database and the view templates stood in for: an invoice goes through
 * ZugferdXml, ZugFerdHelper and mPDF, and what comes out is read back from the bytes. The point is that the
 * PDF opens, carries the invoice and has the ZUGFeRD e-invoice XML embedded in it.
 *
 * Files are written under src/Invoice/Uploads and src/Invoice/Tmp, which are git-ignored, and each test deletes
 * the files it created. Invoice numbers are random so a test never touches a real archived PDF.
 */
#[Test]
final class InvPdfIntegrationTest
{
    private const int INV_ID = 15;

    /** @var list<string> files this test class created and must remove */
    private array $created = [];

    private function translator(): TranslatorInterface
    {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(
            static fn (string $id): string => $id === 'date_format' ? 'd-m-Y' : $id,
        );
        return $translator;
    }

    /**
     * A partial mock of SettingRepository: every method is the real one unless stubbed here, so the static path
     * helpers MpdfHelper and ZugFerdHelper call on it work. Only what would otherwise need the database or the
     * HTTP application config is stubbed.
     *
     * @param array<string, string> $settings
     * @return SettingRepository&m\MockInterface
     */
    private function settings(array $settings = []): SettingRepository
    {
        $s = TypedMock::of(SettingRepository::class);
        $s->makePartial();
        $s->settingsArray = $settings + ['pdf_archive_inv' => '1', 'currency_code' => 'GBP', 'date_format' => 'Y-m-d'];
        $s->shouldReceive('getConfigCompanyDetails')->andReturn([
            'name' => 'Virtual Supplier Ltd', 'address_1' => '1 Supplier Road', 'address_2' => 'Unit 1',
            'city' => 'Supplytown', 'country' => 'GB', 'zip' => 'ZZ1 1ZZ',
        ]);
        $s->shouldReceive('getPrivateCompanyDetails')->andReturn([]);
        $s->shouldReceive('localeLanguageArray')->andReturn(['en' => 'English']);
        return $s;
    }

    private function client(string $name = 'Smith & Sons <Ltd>'): Client
    {
        $client = TypedMock::of(Client::class);
        $client->shouldReceive('reqId')->andReturn(7);
        $client->shouldReceive('getClientName')->andReturn($name);
        $client->shouldReceive('getClientZip')->andReturn('ZZ2 2BB');
        $client->shouldReceive('getClientAddress1')->andReturn('2 Buyer Road');
        $client->shouldReceive('getClientAddress2')->andReturn('Suite 4');
        $client->shouldReceive('getClientCity')->andReturn('Buytown');
        $client->shouldReceive('getClientCountry')->andReturn('GB');
        $client->shouldReceive('getClientVatId')->andReturn('GB123456789');
        $client->shouldReceive('getClientTaxCode')->andReturn('TAX-1');
        $client->shouldReceive('getClientLanguage')->andReturn(null);
        return $client;
    }

    /**
     * @param string|null $password null means no password. A new Inv has an empty string instead, which
     *                              MpdfHelper treats as a password (see the findings in the pull request).
     */
    private function invoice(string $number, ?Client $client = null, ?string $password = null): Inv
    {
        $inv = new Inv();
        (new \ReflectionProperty(Inv::class, 'password'))->setValue($inv, $password);
        $inv->setId(self::INV_ID);
        $inv->setUserId(3);
        $inv->setGroupId(9);
        $inv->setStatusId(2);
        $inv->setNumber($number);
        $inv->setTerms('Pay within 30 days');
        $inv->setClient($client ?? $this->client());
        return $inv;
    }

    private function invAmount(): InvAmount
    {
        $amount = TypedMock::of(InvAmount::class);
        $amount->shouldReceive('getItemSubtotal')->andReturn(100.0);
        $amount->shouldReceive('getItemTaxTotal')->andReturn(20.0);
        $amount->shouldReceive('getTotal')->andReturn(120.0);
        $amount->shouldReceive('getPaid')->andReturn(0.0);
        $amount->shouldReceive('getBalance')->andReturn(120.0);
        return $amount;
    }

    /**
     * Digits only. A hexadecimal suffix made one run in about twelve fail: mPDF's font draws an adjacent "ff" as a
     * single ligature glyph, so the invoice name no longer appeared as text in the PDF
     * (thePdfNeverPrintsTheServersFolderLayoutInItsTitleOrHeader). Digits cannot form a ligature.
     */
    private function uniqueNumber(): string
    {
        return 'ITEST-' . random_int(1000000000, 9999999999);
    }

    private function remember(string $path): string
    {
        $this->created[] = $path;
        return $path;
    }

    private function cleanUp(): void
    {
        foreach ($this->created as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->created = [];
    }

    /** Every uncompressed PDF stream, decoded: embedded files and the text of the pages are compressed. */
    private function decodedStreams(string $pdf): string
    {
        $all = '';
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $matches) > 0) {
            foreach ($matches[1] as $raw) {
                $decoded = @gzuncompress($raw);
                if ($decoded === false) {
                    // The pattern drops a trailing CR before "endstream", but the last byte of a compressed stream
                    // (its checksum) is a CR about one time in 256: put it back and try again.
                    $decoded = @gzuncompress($raw . "\r");
                }
                $all .= $decoded === false ? '' : $decoded . "\n";
            }
        }
        return $all;
    }

    /** The XMP packet of the PDF, parsed, or null when the PDF has none. */
    private function xmp(string $pdf): ?DOMXPath
    {
        if (preg_match('~<x:xmpmeta.*?</x:xmpmeta>~s', $pdf, $match) !== 1) {
            return null;
        }
        $doc = new DOMDocument();
        if (!$doc->loadXML('<root xmlns:x="adobe:ns:meta/">' . $match[0] . '</root>')) {
            return null;
        }
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
        $xpath->registerNamespace('pdfaid', 'http://www.aiim.org/pdfa/ns/id/');
        $xpath->registerNamespace('zf', 'urn:ferd:pdfa:CrossIndustryDocument:invoice:1p0#');
        $xpath->registerNamespace('pdfaSchema', 'http://www.aiim.org/pdfa/ns/schema#');
        $xpath->registerNamespace('pdfaProperty', 'http://www.aiim.org/pdfa/ns/property#');
        return $xpath;
    }

    private function zugferdXml(string $number, ?Client $client = null): string
    {
        $iiaR = TypedMock::of(InvItemAmountRepository::class);
        return (new ZugferdXml($this->settings(), $this->invoice($number, $client), $iiaR, $this->invAmount(), $this->translator()))->xml();
    }

    private function xpath(string $xml): DOMXPath
    {
        $doc = new DOMDocument();
        Assert::true($doc->loadXML($xml));
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('rsm', 'urn:ferd:CrossIndustryDocument:invoice:1p0');
        $xpath->registerNamespace('ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:12');
        return $xpath;
    }

    // ── the ZUGFeRD XML ──────────────────────────────────────────────────────

    public function theZugferdXmlIsWellFormedAndCarriesTheInvoiceSellerBuyerAndTotals(): void
    {
        $number = $this->uniqueNumber();
        $xpath = $this->xpath($this->zugferdXml($number));

        Assert::same($xpath->evaluate('string(//rsm:HeaderExchangedDocument/ram:ID)'), $number);
        Assert::same($xpath->evaluate('string(//ram:SellerTradeParty/ram:Name)'), 'Virtual Supplier Ltd');
        Assert::same($xpath->evaluate('string(//ram:BuyerTradeParty/ram:PostalTradeAddress/ram:CityName)'), 'Buytown');
        Assert::same($xpath->evaluate('string(//ram:GrandTotalAmount)'), '120.00');
        Assert::same($xpath->evaluate('string(//ram:TaxBasisTotalAmount)'), '100.00');
        Assert::same($xpath->evaluate('string(//ram:TaxTotalAmount)'), '20.00');
        Assert::same($xpath->evaluate('string(//ram:DuePayableAmount)'), '120.00');
    }

    public function aBuyerNameWithAmpersandAndAngleBracketsIsEncodedExactlyOnceInTheXml(): void
    {
        $xml = $this->zugferdXml($this->uniqueNumber(), $this->client('Smith & Sons <Ltd>'));
        $xpath = $this->xpath($xml);

        Assert::same($xpath->evaluate('string(//ram:BuyerTradeParty/ram:Name)'), 'Smith & Sons <Ltd>');
        Assert::false(str_contains($xml, '&amp;amp;'));
    }

    public function theTempXmlFileHoldsTheSameXmlAndTheRdfNamesTheEmbeddedFile(): void
    {
        $number = $this->uniqueNumber();
        $helper = new ZugFerdHelper($this->settings(), TypedMock::of(InvItemAmountRepository::class), $this->invAmount(), $this->translator());

        $path = $this->remember($helper->generateInvoiceZugferdXmlTempFile($this->invoice($number)));
        try {
            Assert::true(is_file($path));
            Assert::true(str_contains($path, $number));
            $this->xpath((string) file_get_contents($path));
            Assert::same($this->xpath((string) file_get_contents($path))->evaluate('string(//rsm:HeaderExchangedDocument/ram:ID)'), $number);
            Assert::true(str_contains($helper->zugferdRdf(), 'ZUGFeRD-invoice.xml'));
            Assert::true(str_contains($helper->zugferdRdf(), '<zf:ConformanceLevel>COMFORT</zf:ConformanceLevel>'));
        } finally {
            $this->cleanUp();
        }
    }

    // ── the PDF ──────────────────────────────────────────────────────────────

    /** @param array<string, string> $settings */
    private function pdf(string $html, string $filename, array $settings = ['pdf_archive_inv' => '1'], ?string $password = null, bool $zugferd = false, string $zugferdFile = ''): string
    {
        $s = $this->settings($settings);
        $files = $zugferdFile === '' ? [] : [[
            'name' => 'ZUGFeRD-invoice.xml', 'description' => 'ZUGFeRD Invoice', 'AFRelationship' => 'Alternative',
            'mime' => 'text/xml', 'path' => $zugferdFile,
        ]];
        $context = new PdfCreateContext(
            $password,
            $zugferd ? TypedMock::of(InvItemAmountRepository::class) : null,
            $zugferd ? $this->invAmount() : null,
            true,
            $zugferd,
            $files,
            null,
        );
        $path = (new MpdfHelper($this->translator()))->pdfCreate($html, $filename, false, $s, $context);
        if ($path !== '') {
            $this->remember($path);
        }
        return $path;
    }

    public function aRealPdfIsWrittenThatOpensAsAPdfAndStartsAndEndsLikeOne(): void
    {
        $name = $this->uniqueNumber();
        try {
            $path = $this->pdf('<h1>Invoice ' . $name . '</h1><p>Total 120.00</p>', $name);
            $bytes = (string) file_get_contents($path);

            Assert::true(is_file($path));
            Assert::same(substr($bytes, 0, 5), '%PDF-');
            Assert::same(substr(rtrim($bytes), -5), '%%EOF');
            Assert::true(strlen($bytes) > 1000);
            Assert::true(str_contains($path, $name));
            Assert::true(str_ends_with($path, '.pdf'));
        } finally {
            $this->cleanUp();
        }
    }

    public function noPdfIsWrittenWhenArchivingIsOffAndTheOutputIsNotStreamed(): void
    {
        $path = $this->pdf('<p>x</p>', $this->uniqueNumber(), ['pdf_archive_inv' => '0']);

        Assert::same($path, '');
    }

    public function aPasswordProtectedPdfIsEncrypted(): void
    {
        $name = $this->uniqueNumber();
        try {
            $path = $this->pdf('<p>Secret ' . $name . '</p>', $name, password: 'S3cret-pw');

            Assert::true(str_contains((string) file_get_contents($path), '/Encrypt'));
        } finally {
            $this->cleanUp();
        }
    }

    public function theZugferdXmlIsEmbeddedInThePdfAndCanBeReadBackOut(): void
    {
        $number = $this->uniqueNumber();
        $helper = new ZugFerdHelper($this->settings(), TypedMock::of(InvItemAmountRepository::class), $this->invAmount(), $this->translator());
        try {
            $xmlPath = $this->remember($helper->generateInvoiceZugferdXmlTempFile($this->invoice($number)));
            $path = $this->pdf('<h1>Invoice ' . $number . '</h1>', $number, zugferd: true, zugferdFile: $xmlPath);
            $bytes = (string) file_get_contents($path);
            $streams = $this->decodedStreams($bytes);
            // Deliberately no assertion about the XMP metadata (zugferdRdf()): the PDF currently contains no XMP
            // packet at all, so the ZUGFeRD declaration is dropped. Asserting its absence would lock that in.
            Assert::true(str_contains($bytes, 'ZUGFeRD-invoice.xml'));
            Assert::true(str_contains($bytes, '/EmbeddedFile'));
            Assert::true(str_contains($streams, '<ram:ID>' . $number . '</ram:ID>'));
            Assert::true(str_contains($streams, 'Virtual Supplier Ltd'));
        } finally {
            $this->cleanUp();
        }
    }

    /** A ZUGFeRD PDF written by MpdfHelper, returned as its bytes. */
    private function zugferdPdfBytes(string $name, ?string $password): string
    {
        $helper = new ZugFerdHelper($this->settings(), TypedMock::of(InvItemAmountRepository::class), $this->invAmount(), $this->translator());
        $xmlPath = $this->remember($helper->generateInvoiceZugferdXmlTempFile($this->invoice($name)));
        $path = $this->pdf('<h1>Invoice ' . $name . '</h1>', $name, password: $password, zugferd: true, zugferdFile: $xmlPath);

        return (string) file_get_contents($path);
    }

    public function theZugferdDeclarationIsWrittenIntoThePdfAsPdfA3XmpMetadata(): void
    {
        try {
            $xmp = $this->xmp($this->zugferdPdfBytes($this->uniqueNumber(), null));

            Assert::true($xmp !== null);
            Assert::same($xmp->evaluate('string(//pdfaid:part)'), '3');
            Assert::same($xmp->evaluate('string(//pdfaid:conformance)'), 'B');
            Assert::same($xmp->evaluate('string(//zf:DocumentType)'), 'INVOICE');
            Assert::same($xmp->evaluate('string(//zf:DocumentFileName)'), 'ZUGFeRD-invoice.xml');
            Assert::same($xmp->evaluate('string(//zf:Version)'), '1.0');
            Assert::same($xmp->evaluate('string(//zf:ConformanceLevel)'), 'COMFORT');
        } finally {
            $this->cleanUp();
        }
    }

    public function theXmpDeclaresTheZugferdPropertiesInAPdfAExtensionSchema(): void
    {
        try {
            $xmp = $this->xmp($this->zugferdPdfBytes($this->uniqueNumber(), null));

            Assert::true($xmp !== null);
            Assert::same($xmp->evaluate('string(//pdfaSchema:prefix)'), 'zf');
            Assert::same(
                $xmp->evaluate('string(//pdfaSchema:namespaceURI)'),
                'urn:ferd:pdfa:CrossIndustryDocument:invoice:1p0#',
            );
            $declared = [];
            $names = $xmp->query('//pdfaProperty:name');
            foreach ($names === false ? [] : $names as $node) {
                if ($node instanceof \DOMElement) {
                    $declared[] = $node->textContent;
                }
            }
            Assert::same($declared, ['DocumentFileName', 'DocumentType', 'Version', 'ConformanceLevel']);
        } finally {
            $this->cleanUp();
        }
    }

    public function aZugferdPdfIsNotEncryptedWhenTheInvoicePasswordIsEmptyOrNull(): void
    {
        try {
            foreach ([null, ''] as $password) {
                Assert::false(str_contains($this->zugferdPdfBytes($this->uniqueNumber(), $password), '/Encrypt'));
            }
        } finally {
            $this->cleanUp();
        }
    }

    public function anInvoiceWithARealPasswordStaysEncryptedAndIsNotDeclaredPdfA(): void
    {
        try {
            $bytes = $this->zugferdPdfBytes($this->uniqueNumber(), 'S3cret-pw');

            Assert::true(str_contains($bytes, '/Encrypt'));
            Assert::true($this->xmp($bytes) === null);
        } finally {
            $this->cleanUp();
        }
    }

    public function aPdfWithoutZugferdAndWithAnEmptyPasswordIsNotEncrypted(): void
    {
        try {
            $path = $this->pdf('<p>Report</p>', $this->uniqueNumber(), password: '');

            Assert::false(str_contains((string) file_get_contents($path), '/Encrypt'));
        } finally {
            $this->cleanUp();
        }
    }

    /**
     * A paid invoice carries a transparent watermark image, and the pdf_watermark setting turns the watermark on.
     * PDF/A forbids transparency and mPDF throws when it draws a watermark in PDF/A mode, so such a PDF must still
     * render (mpdf_show_image_errors is on by default), keeping the attached ZUGFeRD XML but not claiming PDF/A.
     */
    public function aPaidInvoiceWithTheWatermarkOnStillRendersAndKeepsTheZugferdFileButIsNotDeclaredPdfA(): void
    {
        $name = $this->uniqueNumber();
        $helper = new ZugFerdHelper($this->settings(), TypedMock::of(InvItemAmountRepository::class), $this->invAmount(), $this->translator());
        $aliases = (new \ReflectionClass(SettingRepository::class))->newInstanceWithoutConstructor()->getImg();
        try {
            $xmlPath = $this->remember($helper->generateInvoiceZugferdXmlTempFile($this->invoice($name)));
            $html = '<h1>Invoice ' . $name . '</h1>'
                . \Yiisoft\Html\Html::tag('watermarkimage', '', [
                    'src' => \App\Invoice\Helpers\PdfWatermarkImage::dataUri('paid', $aliases),
                    'alpha' => '0.1',
                ]);

            $path = $this->pdf(
                $html,
                $name,
                ['pdf_archive_inv' => '1', 'pdf_watermark' => 'PAID', 'mpdf_show_image_errors' => '1'],
                zugferd: true,
                zugferdFile: $xmlPath,
            );
            $bytes = (string) file_get_contents($path);

            Assert::same(substr($bytes, 0, 5), '%PDF-');
            Assert::true(preg_match('~/Subtype\s*/Image~', $bytes) === 1);
            Assert::true(str_contains($bytes, 'ZUGFeRD-invoice.xml'));
            Assert::true($this->xmp($bytes) === null);
            Assert::false(str_contains($bytes, '/Encrypt'));
        } finally {
            $this->cleanUp();
        }
    }

    /** The paid template always emits the watermark tag; with the watermark setting off nothing is drawn. */
    public function aPaidInvoiceWithTheWatermarkSettingOffStillRendersAsAZugferdPdfA3(): void
    {
        $name = $this->uniqueNumber();
        $helper = new ZugFerdHelper($this->settings(), TypedMock::of(InvItemAmountRepository::class), $this->invAmount(), $this->translator());
        $aliases = (new \ReflectionClass(SettingRepository::class))->newInstanceWithoutConstructor()->getImg();
        try {
            $xmlPath = $this->remember($helper->generateInvoiceZugferdXmlTempFile($this->invoice($name)));
            $html = '<h1>Invoice ' . $name . '</h1>'
                . \Yiisoft\Html\Html::tag('watermarkimage', '', [
                    'src' => \App\Invoice\Helpers\PdfWatermarkImage::dataUri('paid', $aliases),
                    'alpha' => '0.1',
                ]);

            $path = $this->pdf($html, $name, ['pdf_archive_inv' => '1', 'mpdf_show_image_errors' => '1'], zugferd: true, zugferdFile: $xmlPath);
            $bytes = (string) file_get_contents($path);

            Assert::same(substr($bytes, 0, 5), '%PDF-');
            Assert::true($this->xmp($bytes) !== null);
        } finally {
            $this->cleanUp();
        }
    }

    public function theArchivedPdfIsInTheArchiveFolderAndNotInUploads(): void
    {
        try {
            $path = $this->pdf('<p>x</p>', $this->uniqueNumber());

            Assert::true(is_file($path));
            Assert::same(basename(dirname($path)), 'Archive');
            Assert::same(basename(dirname($path, 2)), 'Uploads');
            Assert::true(str_starts_with(basename($path), date('Y-m-d') . '_'));
        } finally {
            $this->cleanUp();
        }
    }

    public function thePdfNeverPrintsTheServersFolderLayoutInItsTitleOrHeader(): void
    {
        $name = $this->uniqueNumber();
        try {
            $path = $this->pdf('<p>x</p>', $name);
            // mPDF writes page text as UTF-16, with a NUL byte between characters, so compare with those taken out.
            $text = str_replace([' ', "\0"], '', $this->decodedStreams((string) file_get_contents($path)));

            Assert::true(str_contains($text, $name . '.pdf'));
            Assert::false(str_contains($text, 'Uploads'));
            Assert::false(str_contains($text, 'wamp'));
            Assert::false(str_contains($text, dirname(__DIR__, 4)));
        } finally {
            $this->cleanUp();
        }
    }

    // ── the whole service ────────────────────────────────────────────────────

    /** @param list<object> $items */
    private function reader(array $items): EntityReader
    {
        $reader = TypedMock::of(EntityReader::class);
        $reader->shouldReceive('getIterator')->andReturnUsing(static function () use ($items): \Generator {
            yield from $items;
        });
        return $reader;
    }

    private function service(Inv $inv, SettingRepository $s): InvPdfService
    {
        $iR = TypedMock::of(InvRepository::class);
        $iR->shouldReceive('repoInvUnloadedquery')->andReturn($inv);
        $iR->shouldReceive('repoInvLoadedquery')->andReturn($inv);
        $iR->shouldReceive('repoCount')->andReturn(1);
        $iaR = TypedMock::of(\App\Invoice\InvAmount\InvAmountRepository::class);
        $iaR->shouldReceive('repoInvAmountCount')->andReturn(1);
        $iaR->shouldReceive('repoInvquery')->andReturn($this->invAmount());
        $uiR = TypedMock::of(\App\Invoice\UserInv\UserInvRepository::class);
        $uiR->shouldReceive('repoUserInvcount')->andReturn(0);
        $cfR = TypedMock::of(\App\Invoice\CustomField\CustomFieldRepository::class);
        $cfR->shouldReceive('repoTablequery')->andReturn($this->reader([]));
        $cvR = TypedMock::of(\App\Invoice\CustomValue\CustomValueRepository::class);
        $cvR->shouldReceive('fixCfValueToCf')->andReturn([]);
        $cR = TypedMock::of(\App\Invoice\Client\ClientRepository::class);
        $cR->shouldReceive('repoClientqueryOrig')->andReturn(null);
        $icR = TypedMock::of(\App\Invoice\InvCustom\InvCustomRepository::class);
        $icR->shouldReceive('repoInvCount')->andReturn(0);
        $iiR = TypedMock::of(\App\Invoice\InvItem\InvItemRepository::class);
        $iiR->shouldReceive('repoCount')->andReturn(0);
        $itrR = TypedMock::of(\App\Invoice\InvTaxRate\InvTaxRateRepository::class);
        $itrR->shouldReceive('repoCount')->andReturn(0);
        $aciR = TypedMock::of(InvAllowanceChargeRepository::class);
        $aciR->shouldReceive('repoACIquery')->andReturn($this->reader([]));
        $renderer = TypedMock::of(WebViewRenderer::class);
        $renderer->shouldReceive('renderPartialAsString')->andReturnUsing(
            static fn (string $view): string => '<p>' . basename($view) . '</p>',
        );
        $session = TypedMock::of(SessionInterface::class);
        $session->shouldReceive('get')->andReturn('en');
        $session->shouldReceive('set');

        return new InvPdfService(
            $s,
            $session,
            $this->translator(),
            $renderer,
            TypedMock::build(InvPdfCoreDeps::class, ['iR' => $iR, 'iaR' => $iaR, 'uiR' => $uiR]),
            TypedMock::build(InvPdfDocDeps::class, ['cfR' => $cfR, 'cvR' => $cvR, 'cR' => $cR, 'icR' => $icR]),
            TypedMock::build(InvPdfItemDeps::class, ['iiR' => $iiR, 'itrR' => $itrR, 'aciR' => $aciR]),
        );
    }

    public function generateProducesARealPdfWithTheInvoiceAsAnEmbeddedZugferdFile(): void
    {
        $number = $this->uniqueNumber();
        $tempFolder = dirname(__DIR__, 4) . '/src/Invoice/Uploads/Temp/Zugferd';
        try {
            $service = $this->service($this->invoice($number), $this->settings(['include_zugferd' => '1']));

            $path = $service->generate(self::INV_ID, false, false);
            $this->remember($path);
            $bytes = (string) file_get_contents($path);
            $streams = $this->decodedStreams($bytes);

            Assert::true($path !== '');
            Assert::same(substr($bytes, 0, 5), '%PDF-');
            Assert::true(str_contains($bytes, 'ZUGFeRD-invoice.xml'));
            Assert::true(str_contains($streams, '<ram:ID>' . $number . '</ram:ID>'));
            Assert::true(str_contains($streams, 'Smith &amp; Sons &lt;Ltd&gt;'));
            Assert::true(str_contains($path, str_replace('/', '_', $number)));
            // The temp XML is removed once the PDF is written, instead of piling up in Uploads/Temp/Zugferd.
            Assert::same(glob($tempFolder . '/*' . $number . '_zugferd.xml'), []);
        } finally {
            $leftovers = glob($tempFolder . '/*' . $number . '_zugferd.xml');
            foreach ($leftovers === false ? [] : $leftovers as $file) {
                $this->remember($file);
            }
            $this->cleanUp();
        }
    }

    public function generateEncryptsThePdfOnlyWhenTheInvoiceHasAPassword(): void
    {
        $protected = $this->uniqueNumber();
        $open = $this->uniqueNumber();
        try {
            $protectedPath = $this->remember($this->service($this->invoice($protected, password: 'S3cret-pw'), $this->settings(['include_zugferd' => '0']))->generate(self::INV_ID, false, false));
            $openPath = $this->remember($this->service($this->invoice($open), $this->settings(['include_zugferd' => '0']))->generate(self::INV_ID, false, false));

            Assert::true(str_contains((string) file_get_contents($protectedPath), '/Encrypt'));
            Assert::false(str_contains((string) file_get_contents($openPath), '/Encrypt'));
        } finally {
            $this->cleanUp();
        }
    }

    public function generateWithZugferdSwitchedOffEmbedsNothing(): void
    {
        $number = $this->uniqueNumber();
        try {
            $service = $this->service($this->invoice($number), $this->settings(['include_zugferd' => '0']));

            $path = $service->generate(self::INV_ID, false, false);
            $this->remember($path);

            Assert::true($path !== '');
            Assert::false(str_contains((string) file_get_contents($path), 'ZUGFeRD-invoice.xml'));
        } finally {
            $this->cleanUp();
        }
    }
}
