<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvAmount;
use App\Invoice\Entity\Quote;
use App\Invoice\InvItemAmount\InvItemAmountRepository as iiaR;
use App\Invoice\Setting\SettingRepository as SR;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Files\FileHelper;

/**
 * \Mpdf\Output\Destination::INLINE, or "I"
 * send the file inline to the browser. The plug-in is used if available.
 * The name given by $filename is used when one selects the Save as option on the link generating the PDF.
 *
 * \Mpdf\Output\Destination::DOWNLOAD, or "D"
 * send to the browser and force a file download with the name given by $filename.
 *
 * \Mpdf\Output\Destination::FILE, or "F"
 * save to a local file with the name given by $filename (may include a path).
 *
 * \Mpdf\Output\Destination::STRING_RETURN, or "S"
 * return the document as a string. $filename is ignored.
 *
 * Yiisoft\Files\FileHelper::ensuredirectory static function ensures that folders exist and are writeable using the 0775 permission
 */
class MpdfHelper
{
    /**
     * Blank default mode.
     */
    public const string MODE_BLANK = '';
    /**
     * Core fonts mode.
     */
    public const string MODE_CORE = 'c';
    /**
     * Unicode UTF-8 encoded mode.
     */
    public const string MODE_UTF8 = 'UTF-8';
    /**
     * Asian fonts mode.
     */
    public const string MODE_ASIAN = '+aCJK';
    /**
     * A3 page size format.
     */
    public const string FORMAT_A3 = 'A3';
    /**
     * A4 page size format.
     */
    public const string FORMAT_A4 = 'A4';
    /**
     * Letter page size format.
     */
    public const string FORMAT_LETTER = 'Letter';
    /**
     * Legal page size format.
     */
    public const string FORMAT_LEGAL = 'Legal';
    /**
     * Folio page size format.
     */
    public const string FORMAT_FOLIO = 'Folio';
    /**
     * Ledger page size format.
     */
    public const string FORMAT_LEDGER = 'Ledger-L';
    /**
     * Tabloid page size format.
     */
    public const string FORMAT_TABLOID = 'Tabloid';
    /**
     * Portrait orientation.
     */
    public const string ORIENT_PORTRAIT = 'P';
    /**
     * Landscape orientation.
     */
    public const string ORIENT_LANDSCAPE = 'L';
    /**
     * File output sent to browser inline.
     */
    public const string DEST_BROWSER = 'I';
    /**
     * File output sent for direct download.
     */
    public const string DEST_DOWNLOAD = 'D';
    /**
     * File output sent to a file.
     */
    public const string DEST_FILE = 'F';
    /**
     * File output sent as a string.
     */
    public const string DEST_STRING = 'S';
    public string $mode             = self::MODE_BLANK;

    public string $format       = self::FORMAT_A4;
    public int $defaultFontSize = 0;
    public string $defaultFont  = '';
    public float $marginLeft    = 15;
    public float $marginRight   = 15;
    public float $marginTop     = 16;
    public float $marginBottom  = 16;
    public float $marginHeader  = 9;
    public float $marginFooter  = 9;
    public string $orientation  = self::ORIENT_PORTRAIT;

    public array $options = [
        'autoScriptToLang'    => true,
        'ignore_invalid_utf8' => true,
        'tabSpaces'           => 4,
    ];

    public function pdf_create(
        string $html,
        string $filename,
        bool $stream,
        ?string $password,
        SR $sR,
        // ZugferdXml is not created for a quote => null
        // but iiaR is necessary for the invoice item amounts
        // along with the entity InvAmount
        ?iiaR $iiaR,
        ?InvAmount $inv_amount,
        bool $isInvoice = false,
        bool $zugferd_invoice = false,
        array $associated_files = [],
        ?object $quote_or_invoice = null,
    ): string {
        $sR->load_settings();
        $aliases       = $this->ensure_uploads_folder_exists($sR);
        $archived_file = $aliases->get('@uploads').$sR::getUploadsArchiveholderRelativeUrl().''.date('Y-m-d').'_'.$filename.'.pdf';
        $title         = '1' == $sR->getSetting('pdf_archive_inv') ? $archived_file : $filename.'.pdf';
        $start_mpdf    = $this->initialize_pdf($password, $sR, $title, $quote_or_invoice, $iiaR, $inv_amount, $aliases, $zugferd_invoice, $associated_files);
        $css           = $this->get_css_file($aliases);
        $mpdf          = $this->write_html_to_pdf($css, $html, $start_mpdf);
        if ($isInvoice) {
            $this->isInvoice($filename, $mpdf, $aliases, $sR);
        }
        if ($stream) {
            // send the file inline to the browser. The plug-in is used if available.
            $mpdf->Output($filename.'.pdf', self::DEST_BROWSER);
            if ('1' === $sR->getSetting('pdf_archive_inv')) {
                $mpdf->Output($aliases->get('@uploads').$sR::getUploadsArchiveholderRelativeUrl().''.date('Y-m-d').'_'.$filename.'.pdf', self::DEST_FILE);

                return $aliases->get('@uploads').$sR::getUploadsArchiveholderRelativeUrl().''.date('Y-m-d').'_'.$filename.'.pdf';
            }

            return 'streamed_not_saved';
        }      // save to a local file with the name given by $filename (may include a path).
        if ('1' === $sR->getSetting('pdf_archive_inv')) {
            $mpdf->Output($aliases->get('@uploads').$sR::getUploadsArchiveholderRelativeUrl().''.date('Y-m-d').'_'.$filename.'.pdf', self::DEST_FILE);

            return $aliases->get('@uploads').$sR::getUploadsArchiveholderRelativeUrl().''.date('Y-m-d').'_'.$filename.'.pdf';
        }

        return '';
    }

    private function isInvoice(string $filename, \Mpdf\Mpdf $mpdf, Aliases $aliases, SR $sR): string
    {
        // Archive the file if it is an invoice
        if ('1' === $sR->getSetting('pdf_archive_inv')) {
            $archive_folder = $aliases->get('@uploads').$sR::getUploadsArchiveholderRelativeUrl().'/Invoice';
            $archived_file  = $aliases->get('@uploads').$sR::getUploadsArchiveholderRelativeUrl().''.date('Y-m-d').'_'.$filename.'.pdf';
            if (!is_dir($archive_folder)) {
                FileHelper::ensureDirectory($archive_folder, 0775);
            }
            $mpdf->Output($archived_file, self::DEST_FILE);

            return $archived_file;
        }

        return '';
    }

    private function ensure_tmp_folder_exists(SR $sR): Aliases
    {
        // Define aliases for paths
        $aliases = new Aliases([
            '@invoice' => dirname(__DIR__), // Root directory for the invoice
            '@tmp'     => dirname(__DIR__).DIRECTORY_SEPARATOR.'Tmp'.DIRECTORY_SEPARATOR, // Directory for temporary files
        ]);

        // Define the Tmp directory path
        $tmpFolder = $aliases->get('@tmp');

        // Check if the Tmp directory exists, if not, create it
        if (!(is_dir($tmpFolder) || is_link($tmpFolder))) {
            FileHelper::ensureDirectory($tmpFolder, 0775); // Ensure the Tmp directory is created with the correct permissions
        }

        return $aliases;
    }

    private function ensure_uploads_folder_exists(SR $sR): Aliases
    {
        $aliases = new Aliases(['@invoice' => dirname(__DIR__),
            '@uploads'                     => dirname(__DIR__).DIRECTORY_SEPARATOR.'Uploads'.DIRECTORY_SEPARATOR]);

        // Invoice/Uploads/Archive
        $folder = $aliases->get('@uploads').$sR::getUploadsArchiveholderRelativeUrl();
        // Check if the archive folder is available
        if (!(is_dir($folder) || is_link($folder))) {
            FileHelper::ensureDirectory($folder, 0775);
        }

        return $aliases;
    }

    private function initialize_pdf(?string $password, SR $sR, string $title, ?object $quote_or_invoice, ?iiaR $iiaR, ?InvAmount $inv_amount, Aliases $aliases, bool $zugferd_invoice, array $associated_files = []): \Mpdf\Mpdf
    {
        $optionsArray = $this->options($sR);
        $mpdf         = new \Mpdf\Mpdf($optionsArray);
        // mPDF configuration
        $mpdf->SetDirectionality('ltr');
        $mpdf->useAdobeCJK              = ('1' === $sR->getSetting('mpdf_cjk') ? true : false);
        $mpdf->autoScriptToLang         = ('1' === $sR->getSetting('mpdf_auto_script_to_lang') ? true : false);
        $mpdf->autoVietnamese           = ('1' === $sR->getSetting('mpdf_auto_vietnamese') ? true : false);
        $mpdf->allow_charset_conversion = ('0' === $sR->getSetting('mpdf_allow_charset_conversion') ? false : true);
        $mpdf->autoArabic               = ('1' === $sR->getSetting('mpdf_auto_arabic') ? true : false);
        $mpdf->autoLangToFont           = ('1' === $sR->getSetting('mpdf_auto_language_to_font') ? true : false);
        $mpdf->SetTitle($title);
        $mpdf->showImageErrors = ('1' === $sR->getSetting('mpdf_show_image_errors') ? true : false);

        // Include zugferd if enabled
        if (true === $zugferd_invoice && null !== $inv_amount && null !== $iiaR) {
            $z = new ZugFerdHelper($sR, $iiaR, $inv_amount);
            // https://mpdf.github.io/reference/mpdf-variables/useadobecjk.html
            // A zugferd invoice must have fully embedded fonts => $mpdf->useAdobeCJK = false
            $mpdf->useAdobeCJK = false;

            $mpdf->PDFX = false;

            // https://mpdf.github.io/what-else-can-i-do/pdf-a1-b-compliance.html
            $mpdf->PDFA = false;

            $mpdf->PDFAauto = true;
            $mpdf->SetAdditionalXmpRdf($z->zugferd_rdf());
            $mpdf->SetAssociatedFiles($associated_files);
        }

        $content = $title.': '.date($sR->trans('date_format'));
        $mpdf->SetHTMLHeader('<div style="text-align: right; font-size: 8px; font-weight: lighter;">'.$content.'</div>');

        // Set the footer if is invoice and if set in settings
        if (!empty($sR->getSetting('pdf_invoice_footer'))) {
            $mpdf->setAutoBottomMargin = 'stretch';
            $mpdf->SetHTMLFooter('<div id="footer">'.$sR->getSetting('pdf_invoice_footer').'</div>');
        }

        // Watermark
        if (!empty($sR->getSetting('pdf_watermark'))) {
            $mpdf->showWatermarkText  = true;
            $mpdf->showWatermarkImage = true;
        }

        if (($quote_or_invoice instanceof Quote) || ($quote_or_invoice instanceof Inv)) {
            if (null !== $quote_or_invoice->getClient()?->getClient_language()) {
                if ('Arabic' === $quote_or_invoice->getClient()?->getClient_language()) {
                    $mpdf->SetDirectionality('rtl');
                }
            }
        }
        // Set a password if set for the voucher
        if (null !== $password) {
            $mpdf->SetProtection(['copy', 'print'], $password, $password);
        }

        return $mpdf;
    }

    private function get_css_file(Aliases $aliases): string|false
    {
        $cssFile = $aliases->get('@invoice/Asset/kartik-v/kv-mpdf-bootstrap.min.css');

        return file_get_contents($cssFile);
    }

    private function write_html_to_pdf(string|false $css, string $html, \Mpdf\Mpdf $mpdf): \Mpdf\Mpdf
    {
        if (is_string($css)) {
            $mpdf->writeHtml($css, 1);
        }
        $mpdf->WriteHTML($html, 2);

        return $mpdf;
    }

    /**
     * Acknowledgement to yii2-mpdf.
     *
     * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2014 - 2020
     *
     * @version 1.0.6
     */
    private function options(SR $sR): array
    {
        $aliases = $this->ensure_tmp_folder_exists($sR);

        $this->options['mode']              = $this->mode;
        $this->options['format']            = $this->format;
        $this->options['default_font_size'] = $this->defaultFontSize;
        $this->options['default_font']      = $this->defaultFont;
        $this->options['margin_left']       = $this->marginLeft;
        $this->options['margin_right']      = $this->marginRight;
        $this->options['margin_top']        = $this->marginTop;
        $this->options['margin_bottom']     = $this->marginBottom;
        $this->options['margin_header']     = $this->marginHeader;
        $this->options['margin_footer']     = $this->marginFooter;
        $this->options['orientation']       = $this->orientation;
        $this->options['tempDir']           = $aliases->get('@tmp');

        return $this->options;
    }
}
