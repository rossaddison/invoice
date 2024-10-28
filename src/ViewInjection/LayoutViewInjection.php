<?php

declare(strict_types=1);

namespace App\ViewInjection;

use App\Auth\Identity;
// Entities
use App\Invoice\Entity\Company;
use App\Invoice\Entity\CompanyPrivate;
// Repositories
use App\Invoice\Company\CompanyRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\CompanyPrivate\CompanyPrivateRepository;
use App\Invoice\Helpers\DateHelper;
// Yiisoft
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\LayoutParametersInjectionInterface;

/**
 * @see ./views/layout/main.php or alternative(s)
 * @see ./views/layout/templates/soletrader/main.php
 * @see ./invoice/config/common/params.php 'yiisoft/yii-view-renderer' 
 */

final class LayoutViewInjection implements LayoutParametersInjectionInterface
{
    private CompanyRepository $companyRepository;
    private CompanyPrivateRepository $companyPrivateRepository;
    private SettingRepository $settingRepository;
    private Translator $translator;
    
    public function __construct(private CurrentUser $currentUser, 
        CompanyRepository $companyRepository,
        CompanyPrivateRepository $companyPrivateRepository,
        SettingRepository $settingRepository,
        Translator $translator,
    )
    {
        $this->companyRepository = $companyRepository;
        $this->companyPrivateRepository = $companyPrivateRepository;
        $this->settingRepository = $settingRepository;
        $this->translator = $translator;
    }
    
    /**
     * @return array
     * 
     * @psalm-return array<string, mixed> 
     */
    
    public function getLayoutParameters(): array
    {
        $brandLabel = '';
        $companyWeb = '';
        $companySlack = '';
        $companyFaceBook = '';
        $companyTwitter = '';
        $companyLinkedIn = '';
        $companyWhatsApp = '';
        $companyEmail = '';
        $companyLogoFileName = '';
        /**
         * @see src/Invoice/Entity/CompanyPrivate for default values 80, 40, 10 respectively
         */
        $companyLogoWidth = 80;
        $companyLogoHeight = 40;
        $companyLogoMargin = 10;
        $identity = $this->currentUser->getIdentity();
        // Iterate through the companies to find which one is active
        $companies = $this->companyRepository->findAllPreloaded();
        $companyPrivates = $this->companyPrivateRepository->findAllPreloaded();
        /**
         * @var Company $company
         */
        foreach ($companies as $company) {
            if ($company->getCurrent() == '1') {
                $brandLabel = $company->getName();
                $companyWeb = $company->getWeb();
                $companySlack = $company->getSlack();
                $companyFaceBook = $company->getFaceBook();
                $companyTwitter = $company->getTwitter();
                $companyLinkedIn = $company->getLinkedIn();
                $companyWhatsApp = $company->getWhatsApp();
                $companyEmail = $company->getEmail();
                /**
                 * @var CompanyPrivate $private
                 */
                foreach ($companyPrivates as $private) {
                    if ($private->getCompany_id() == (string)$company->getId()) {
                        // site's logo: take the first logo where the current date falls within the logo's start and end dates
                        if (($private->getStart_date()?->format('Y-m-d') < (new \DateTimeImmutable('now'))->format('Y-m-d')) && ($private->getEnd_date()?->format('Y-m-d') > (new \DateTimeImmutable('now'))->format('Y-m-d'))) {
                            $companyLogoFileName = $private->getLogo_filename();
                            $companyLogoWidth = $private->getLogo_width();
                            $companyLogoHeight = $private->getLogo_height();
                            $companyLogoMargin = $private->getLogo_margin();
                            //  break;
                        }    
                    }
                }
            }
        }
        $stopSigningUp = $this->settingRepository->getSetting('stop_signing_up') == '1' ? true : false;
        $stopLoggingIn = $this->settingRepository->getSetting('stop_logging_in') == '1' ? true : false;
        /**
         * @see .env.php $_ENV['YII_DEBUG'] located in the root (first) folder
         * @see {root} autoload.php 
         */
        $debugMode = $_SERVER['YII_DEBUG'] == '1' ? true : false;
        // Record the debugMode in a setting so that 'debug_mode' can be used in e.g. salesorder\guest.php`
        $this->settingRepository->debugMode($debugMode);
        $user = $identity instanceof Identity ? $identity->getUser() : null;
        $isGuest = ($user === null || $user->getId() === null);
        $userLogin = (null!==$user ? $user->getLogin() : null);
        // Show the default logo if the logo applicable dates have expired under CompanyPrivate
        $logoPath = ((isset($companyLogoFileName) && !empty($companyLogoFileName)) ? '/logo/'. $companyLogoFileName : '/site/logo.png');
        // https://api.jqueryui.com/datepicker
        $dateHelper = new DateHelper($this->settingRepository);
        $javascriptJqueryDateHelper = "$(function () {" .
          '$(".form-control.input-sm.datepicker").datepicker({dateFormat:"' . $dateHelper->datepicker_dateFormat()
          . '", firstDay:' . $dateHelper->datepicker_firstDay()
          . ', changeMonth: true'
          . ', changeYear: true'
          . ', yearRange: "-110:+10"'
          . ', clickInput: true'
          . ', constrainInput: false'
          . ', highlightWeek: true'
          . ' });' .
          '});';
        return [
            'title' => 'Home',
            'logoPath' => $logoPath,
            'debugMode' => $debugMode,
            'stopSigningUp' => $stopSigningUp,
            'stopLoggingIn' => $stopLoggingIn,
            'isGuest' => $isGuest,
            'user' => $user,
            'userLogin' => $userLogin,
            'xdebug' => $xdebug = (extension_loaded('xdebug') ? 'php.ini zend_extension xdebug Installed : Performance compromised!' 
                                                              : 'php.ini zend_extension Commented out: Performance NOT compromised'),
            // 0 => fast Read and Write, // 1 => slower Write Only
            'read_write' => $this->settingRepository->getSchemaProvidersMode(),
            'brandLabel' => $brandLabel ?? 'Yii3-i',
            'companyWeb' => $companyWeb ?? 'https://www.web.com',
            'companySlack' => $companySlack ?? 'https://www.slack.com',
            'companyFaceBook' => $companyFaceBook ?? 'https://www.facebook.com',
            'companyTwitter' => $companyTwitter ?? 'https://www.twitter.com',
            'companyLinkedIn' => $companyLinkedIn ?? 'https://www.linkedin.com',
            'companyWhatsApp' => $companyWhatsApp ?? 'https://www.whatsapp.com',
            'companyEmail' => $companyEmail ?? 'mailto:js@example.com',
            'companyLogoFileName' => $companyLogoFileName ?? '',
            'companyLogoWidth' => $companyLogoWidth ?? '',
            'companyLogoHeight' => $companyLogoHeight ?? '',
            'companyLogoMargin' => $companyLogoMargin ?? '',
            'javascriptJqueryDateHelper' => $javascriptJqueryDateHelper,
            /**
             * @see Use the repository name to build a quick link to scrutinizer php and javascript code checks 
             * in invoice/layout under debug mode
             */
            'scrutinizerRepository' => 'rossaddison/invoice',
        ];
    }    
}
