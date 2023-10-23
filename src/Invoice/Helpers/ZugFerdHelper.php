<?php
declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvAmount;
use App\Invoice\Libraries\ZugferdXml;
use App\Invoice\Setting\SettingRepository as SRepo;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Files\FileHelper;
use Yiisoft\Security\Random;

Class ZugFerdHelper
{

private SRepo $s;
private IIAR $iiaR;
private InvAmount $inv_amount;
/**
 * @param SRepo $s
 * @param IIAR $iiaR
 * @param InvAmount $inv_amount
 */
public function __construct(SRepo $s, IIAR $iiaR, InvAmount $inv_amount)
{
      $this->s = $s;
      $this->iiaR =  $iiaR;
      $this->inv_amount = $inv_amount;
}
/**
 * 
 * @param SRepo $sR
 * @return Aliases
 */
private function ensure_temp_zugferd_folder_and_uploads_folder_exist(): Aliases {
    $aliases = new Aliases(['@invoice' => dirname(__DIR__), '@Uploads' => '@invoice/Uploads']);
    // Invoice/Uploads/Archive
    $folder = $aliases->get('@Uploads');
    // Check if the uploads folder is available
    if (!(is_dir($folder) || is_link($folder))) {
        FileHelper::ensureDirectory($folder, 0775);
    }
    // Invoice/Uploads/Temp/Zugferd
    $temp_zugferd_folder = $aliases->get('@Uploads').$this->s::getTempZugferdfolderRelativeUrl();            
    if (!is_dir($temp_zugferd_folder)){
        FileHelper::ensureDirectory($temp_zugferd_folder, 0775);
    }
    return $aliases;
}

/**
 * @param Inv $invoice
 * @param IIAR $iiaR
 * @param InvAmount $inv_amount
 * @return string
 */
public function generate_invoice_zugferd_xml_temp_file(Inv $invoice, IIAR $iiaR, InvAmount $inv_amount) : string
{
    $this->ensure_temp_zugferd_folder_and_uploads_folder_exist();
    $path = dirname(__DIR__).DIRECTORY_SEPARATOR.'Uploads'
                            .DIRECTORY_SEPARATOR.'Temp'
                            .DIRECTORY_SEPARATOR.'Zugferd'
                            .DIRECTORY_SEPARATOR. 'invoice_' .  Random::string(8)
                            . ($invoice->getNumber() ?? '_search_null_invoice_id_' ). '_zugferd.xml';
    // Generate inv items from Entity Inv->getItems() HasMany function
    // Generate inv item amounts from $iiaR
    $z = new ZugferdXml($this->s, $invoice, $iiaR, $inv_amount);
    $f = fopen($path, 'wb');
    if (!$f) {
        throw new \Exception(sprintf('Unable to create output file %s', $path));			
    }
    fwrite($f, $z->xml(), strlen($z->xml()));
    fclose($f);
    return $path;
}

/**
 * Returns the correct RDF string for the Zugferd XML
 * @return string
 */
public function zugferd_rdf() : string
{
    $s = '<rdf:Description rdf:about="" xmlns:zf="urn:ferd:pdfa:CrossIndustryDocument:invoice:1p0#">' . "\n";
    $s .= '  <zf:DocumentType>INVOICE</zf:DocumentType>' . "\n";
    $s .= '  <zf:DocumentFileName>ZUGFeRD-invoice.xml</zf:DocumentFileName>' . "\n";
    $s .= '  <zf:Version>1.0</zf:Version>' . "\n";
    $s .= '  <zf:ConformanceLevel>COMFORT</zf:ConformanceLevel>' . "\n";
    $s .= '</rdf:Description>' . "\n";
    return $s;
}
}
