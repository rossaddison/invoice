<?php
declare(strict_types=1);

Namespace App\Invoice\Helpers;

use App\Invoice\Setting\SettingRepository as SR;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;

Class InvoiceHelper
{
    private SR $s;     
    private SessionInterface $session;
    
    public function __construct(SR $s, SessionInterface $session,) {
        $this->s = $s;
        $this->session = $session;
    }
        
    /**
     * @psalm-param 'danger' $level
     */
    private function flash(string $level, string $message): Flash{
        $flash = new Flash($this->session);
        $flash->set($level, $message); 
        return $flash;
    }
    
    public function invoice_logo(): string
    {
        $aliases = new Aliases(['@invoice' => dirname(__DIR__), 
                                '@img' => dirname(__DIR__). DIRECTORY_SEPARATOR
                                          .'Asset/core/img']);
        if (!empty($this->s->get_setting('invoice_logo'))) {
            return '<img src="'. $aliases->get('@img') . $this->s->get_setting('invoice_logo') . '">';
        }
        return '';
    }

    /**
     * Returns the invoice logo for PDF files
     *
     * @return string
     */
    public function invoice_logo_pdf()
    {
        $aliases = new Aliases(['@invoice' => dirname(__DIR__),
                                '@img' => dirname(__DIR__). DIRECTORY_SEPARATOR
                                          .'Asset'
                                          .DIRECTORY_SEPARATOR.
                                          'core'
                                          .DIRECTORY_SEPARATOR.  
                                          'img']);
        if (!empty($this->s->get_setting('invoice_logo'))) {
            return '<img src="file://' . getcwd() . $aliases->get('@img'). $this->s->get_setting('invoice_logo')  . '" id="invoice-logo">';
        }
        return '';
    }

    /**
     * Returns a Swiss IS / IS+ code line
     * Documentation: https://www.postfinance.ch/binp/postfinance/public/dam.M26m_i6_6ceYcN2XtAN4w8OHMynQG7FKxJVK8TtQzr0.spool/content/dam/pf/de/doc/consult/manual/dlserv/inpayslip_isr_man_en.pdf
     *
     * @param string $slipType
     * @param mixed $amount
     * @param string $rnumb
     * @param mixed $subNumb
     * @return string
     */
    public function invoice_genCodeline(string $slipType, mixed $amount, string $rnumb, mixed $subNumb): string
    {
        $isEur = false;

        if ((int)$slipType > 14) {
            $isEur = true;
        } else {
            $amount = .5 * round((float)$amount / .5, 1);
        }

        if (!$isEur && $amount > 99999999.95) {
            $this->flash('danger', $this->s->trans('Invalid amount'));
        } elseif ($isEur && $amount > 99999999.99) {
            $this->flash('danger', $this->s->trans('Invalid amount'));
        }

        $amountLine = sprintf("%010d", (float)$amount * 100);
        $checkSlAmount = $this->invoice_recMod10($slipType . $amountLine);

        if (!preg_match("/\d{2}-\d{1,6}-\d{1}/", (string)$subNumb)) {
            $this->flash('danger', $this->s->trans('Invalid subscriber number'));
        }

        $subNumb_exploded = explode("-", (string)$subNumb);
        $fullSub = $subNumb_exploded[0] . sprintf("%06d", $subNumb_exploded[1]) . $subNumb_exploded[2];
        $rnumb_preg_replace = preg_replace('/\s+/', '', $rnumb);

        return $slipType . $amountLine . $checkSlAmount . ">" . $rnumb_preg_replace . "+ " . $fullSub . ">";
    }

    /**
     * Calculate checksum using Recursive Mod10
     * See https://www.postfinance.ch/binp/postfinance/public/dam.Ii-X5NgtAixO8cQPvja46blV6d7cZCyGUscxO15L5S8.spool/content/dam/pf/de/doc/consult/manual/dldata/efin_recdescr_man_en.pdf
     * Page 5
     *
     * @param string $in
     */
    function invoice_recMod10($in): int
    {
        $line = [0, 9, 4, 6, 8, 2, 7, 1, 3, 5];
        $carry = 0;
        $chars = str_split($in);

        foreach ($chars as $char) {
            /** 
             * @var int $carry
             * @psalm-suppress InvalidArrayOffset
             */ 
            $carry = $line[($carry + intval($char)) % 10];
        }

        return (10 - $carry) % 10;
    }
}