<?php

declare(strict_types=1);

namespace App\Invoice\Spreadsheet\Export;

use App\Service\WebControllerService;
use App\User\UserService;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

final class ExportController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;    
    private Session $session;
    private Flash $flash;
    private TranslatorInterface $translator;

    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,        
        Session $session,
        TranslatorInterface $translator
    ) {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/export')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->translator = $translator;
    }
    
    /**
     * Use: Prepare a blank Client Spreadsheet that can be opened and filled in. Imported later
     * @link https://github.com/PHPOffice/PhpSpreadsheet/blob/master/samples/Basic/01_Simple_download_ods.php
     * @return void
     */
    public function blankclientsheet_ods() : void {
        $spreadsheet = new Spreadsheet();
        $subject = $this->translator->translate('invoice.client.import.list.blank');
        $streets = $this->translator->translate('invoice.client.streets');
        $client_frequency = $this->translator->translate('invoice.client.frequency');
        $client_group = $this->translator->translate('invoice.client.group');
        $price = $this->translator->translate('i.product_price');
        $client_building_number = '#';       
        $user = $this->userService->getUser();
        $fileType = '';
        if (null!==$user) {
            $userName = $user->getLogin();
            $spreadsheet->getProperties()->setCreator($userName)
                ->setLastModifiedBy((new \DateTimeImmutable('now'))->format('Y-m-d'))
                ->setTitle($client_group)
                ->setSubject($subject)
                ->setDescription($this->translator->translate('site.todays.date').': '.(new \DateTimeImmutable('now'))->format('Y-m-d'))
                ->setKeywords($fileType)
                ->setCategory($client_group);
            $spreadsheet->setActiveSheetIndex(0)
                ->setCellValue('A1', $streets)
                ->setCellValue('B1', $client_building_number)
                ->setCellValue('C1', $client_frequency)
                ->setCellValue('D1', $price); 
            $spreadsheet->getActiveSheet()->setTitle((new \DateTimeImmutable('now'))->format('Y-m-d'));
            $spreadsheet->setActiveSheetIndex(0);
            header('Content-Type: application/vnd.oasis.opendocument.spreadsheet');
            header('Content-Disposition: attachment;filename="blankClientSheet.ods"');
            header('Cache-Control: max-age=0');
            // If you're serving to IE over SSL, then the following may be needed
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0
            $writer = IOFactory::createWriter($spreadsheet, 'Ods');
            $writer->save('php://output');
            exit;
        }
    }
    
   /**
   * @return string
   */
   private function alert(): string {
     return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
     [ 
       'flash' => $this->flash
     ]);
   }

    /**
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash_message(string $level, string $message): Flash {
      $this->flash->add($level, $message, true);
      return $this->flash;
    }
}
