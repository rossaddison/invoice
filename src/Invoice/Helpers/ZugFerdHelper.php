<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Invoice\Libraries\ZugferdXml;
use App\Invoice\Setting\SettingRepository as SRepo;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Files\FileHelper;
use Yiisoft\Security\Random;
use Yiisoft\Translator\TranslatorInterface as Translator;

final readonly class ZugFerdHelper
{
    /**
     * @param SRepo $s
     * @param IIAR $iiaR
     * @param InvAmount $inv_amount
     */
    public function __construct(
        private SRepo $s,
        private IIAR $iiaR,
        private InvAmount $inv_amount,
        private readonly Translator $translator
    ) {
    }

    /**
     * @return Aliases
     */
    /** @psalm-suppress UnusedReturnValue */
    private function ensureTempZugferdFolderAndUploadsFolderExist(): Aliases
    {
        $aliases = new Aliases(['@invoice' => dirname(__DIR__), '@Uploads' => '@invoice/Uploads']);
        // Invoice/Uploads/Archive
        $folder = $aliases->get('@Uploads');
        // Check if the uploads folder is available
        if (!(is_dir($folder) || is_link($folder))) {
            FileHelper::ensureDirectory($folder, 0o775);
        }
        // Invoice/Uploads/Temp/Zugferd
        $temp_zugferd_folder = $aliases->get('@Uploads') . $this->s::getTempZugferdfolderRelativeUrl();
        if (!is_dir($temp_zugferd_folder)) {
            FileHelper::ensureDirectory($temp_zugferd_folder, 0o775);
        }
        return $aliases;
    }

    /**
     * @param Inv $invoice
     * @return string
     */
    public function generateInvoiceZugferdXmlTempFile(Inv $invoice): string
    {
        $this->ensureTempZugferdFolderAndUploadsFolderExist();
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Uploads'
                                . DIRECTORY_SEPARATOR . 'Temp'
                                . DIRECTORY_SEPARATOR . 'Zugferd'
                                . DIRECTORY_SEPARATOR . 'invoice_' . Random::string(8)
                                . ($invoice->getNumber() ?? '_search_null_invoice_id_') . '_zugferd.xml';
        // Generate inv items from Entity Inv->getItems() HasMany function
        // Generate inv item amounts from $iiaR
        $z = new ZugferdXml($this->s, $invoice, $this->iiaR, $this->inv_amount, $this->translator);
        $f = fopen($path, 'wb');
        if (!$f) {
            throw new ZugFerdHelperException(sprintf('Unable to create output file %s', $path));
        }
        fwrite($f, $z->xml(), strlen($z->xml()));
        fclose($f);
        return $path;
    }

    /**
     * Returns the correct RDF string for the Zugferd XML
     * @return string
     */
    public function zugferdRdf(): string
    {
        // PDF/A needs custom XMP properties declared: this extension schema describes the zf: properties below, as
        // the ZUGFeRD 1.0 specification prescribes.
        $s = '<rdf:Description rdf:about="" xmlns:pdfaExtension="http://www.aiim.org/pdfa/ns/extension/"'
            . ' xmlns:pdfaSchema="http://www.aiim.org/pdfa/ns/schema#"'
            . ' xmlns:pdfaProperty="http://www.aiim.org/pdfa/ns/property#">' . "\n";
        $s .= '  <pdfaExtension:schemas>' . "\n";
        $s .= '    <rdf:Bag>' . "\n";
        $s .= '      <rdf:li rdf:parseType="Resource">' . "\n";
        $s .= '        <pdfaSchema:schema>ZUGFeRD PDFA Extension Schema</pdfaSchema:schema>' . "\n";
        $s .= '        <pdfaSchema:namespaceURI>urn:ferd:pdfa:CrossIndustryDocument:invoice:1p0#</pdfaSchema:namespaceURI>' . "\n";
        $s .= '        <pdfaSchema:prefix>zf</pdfaSchema:prefix>' . "\n";
        $s .= '        <pdfaSchema:property>' . "\n";
        $s .= '          <rdf:Seq>' . "\n";
        foreach ([
            'DocumentFileName' => 'name of the embedded XML invoice file',
            'DocumentType' => 'INVOICE',
            'Version' => 'The actual version of the ZUGFeRD data',
            'ConformanceLevel' => 'The conformance level of the ZUGFeRD data',
        ] as $name => $description) {
            $s .= '            <rdf:li rdf:parseType="Resource">' . "\n";
            $s .= '              <pdfaProperty:name>' . $name . '</pdfaProperty:name>' . "\n";
            $s .= '              <pdfaProperty:valueType>Text</pdfaProperty:valueType>' . "\n";
            $s .= '              <pdfaProperty:category>external</pdfaProperty:category>' . "\n";
            $s .= '              <pdfaProperty:description>' . $description . '</pdfaProperty:description>' . "\n";
            $s .= '            </rdf:li>' . "\n";
        }
        $s .= '          </rdf:Seq>' . "\n";
        $s .= '        </pdfaSchema:property>' . "\n";
        $s .= '      </rdf:li>' . "\n";
        $s .= '    </rdf:Bag>' . "\n";
        $s .= '  </pdfaExtension:schemas>' . "\n";
        $s .= '</rdf:Description>' . "\n";
        $s .= '<rdf:Description rdf:about="" xmlns:zf="urn:ferd:pdfa:CrossIndustryDocument:invoice:1p0#">' . "\n";
        $s .= '  <zf:DocumentType>INVOICE</zf:DocumentType>' . "\n";
        $s .= '  <zf:DocumentFileName>ZUGFeRD-invoice.xml</zf:DocumentFileName>' . "\n";
        $s .= '  <zf:Version>1.0</zf:Version>' . "\n";
        $s .= '  <zf:ConformanceLevel>COMFORT</zf:ConformanceLevel>' . "\n";
        $s .= '</rdf:Description>' . "\n";
        return $s;
    }
}
