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
// Yiisoft
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\LayoutParametersInjectionInterface;

/**
 * Related logic: see ./views/layout/main.php or alternative(s)
 * Related logic: see ./views/layout/templates/soletrader/main.php
 * Related logic: see ./invoice/config/common/params.php 'yiisoft/yii-view-renderer'
 */

final readonly class LayoutViewInjection implements LayoutParametersInjectionInterface
{
    public function __construct(private CurrentUser $currentUser, private CompanyRepository $companyRepository, private CompanyPrivateRepository $companyPrivateRepository, private SettingRepository $settingRepository, private Translator $translator) {}

    /**
     * @return array
     *
     * @psalm-return array<string, mixed>
     */
    #[\Override]
    public function getLayoutParameters(): array
    {
        $bootstrap5OffcanvasEnable = false;
        $bootstrap5OffcanvasPlacement = 'top';
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
         * Related logic: see src/Invoice/Entity/CompanyPrivate for default values 80, 40, 10 respectively
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
                    if ($private->getCompany_id() == (string) $company->getId()) {
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
        $bootstrap5OffcanvasPlacement = $this->settingRepository->getSetting('bootstrap5_offcanvas_placement') ?: 'top';
        $bootstrap5OffcanvasEnable = $this->settingRepository->getSetting('bootstrap5_offcanvas_enable') == '1' ? true : false;
        $bootstrap5LayoutInvoiceNavbarFont = $this->settingRepository->getSetting('bootstrap5_layout_invoice_navbar_font') ?: 'Arial';
        $bootstrap5LayoutInvoiceNavbarFontSize = $this->settingRepository->getSetting('bootstrap5_layout_invoice_navbar_font_size') ?: '10';
        $stopSigningUp = $this->settingRepository->getSetting('stop_signing_up') == '1' ? true : false;
        $stopLoggingIn = $this->settingRepository->getSetting('stop_logging_in') == '1' ? true : false;
        $noFrontPageAbout = $this->settingRepository->getSetting('no_front_about_page') == '1' ? true : false;
        $noFrontPageGallery = $this->settingRepository->getSetting('no_front_gallery_page') == '1' ? true : false;
        $noFrontPageAccreditations = $this->settingRepository->getSetting('no_front_accreditations_page') == '1' ? true : false;
        $noFrontPageTeam = $this->settingRepository->getSetting('no_front_team_page') == '1' ? true : false;
        $noFrontPagePricing = $this->settingRepository->getSetting('no_front_pricing_page') == '1' ? true : false;
        $noFrontPageTestimonial = $this->settingRepository->getSetting('no_front_testimonial_page') == '1' ? true : false;
        $noFrontPagePrivacyPolicy = $this->settingRepository->getSetting('no_front_privacy_policy_page') == '1' ? true : false;
        $noFrontPageTermsOfService = $this->settingRepository->getSetting('no_front_terms_of_service') == '1' ? true : false;
        $noFrontPageContactDetails = $this->settingRepository->getSetting('no_front_contact_details_page') == '1' ? true : false;
        $noFrontPageContactUs = $this->settingRepository->getSetting('no_front_contact_us_page') == '1' ? true : false;
        /**
         * Related logic: see .env.php $_ENV['YII_DEBUG'] and $_ENV['BUILD_DATABASE'] located in the root (first) folder
         *      e.g YII_DEBUG=true
         * Related logic: see {root} autoload.php
         */
        $debugMode = $_ENV['YII_DEBUG'] == 'true' ? true : false;
        $buildDatabase = $_ENV['BUILD_DATABASE'] == 'true' ? true : false;
        // Record the debugMode in a setting so that 'debug_mode' can be used in e.g. salesorder\guest.php`
        $this->settingRepository->debugMode($debugMode);
        $user = $identity instanceof Identity ? $identity->getUser() : null;
        $isGuest = ($user === null || $user->getId() === null);
        $userLogin = (null !== $user ? $user->getLogin() : null);
        // Show the default logo if the logo applicable dates have expired under CompanyPrivate
        $logoPath = ((isset($companyLogoFileName) && !empty($companyLogoFileName)) ? '/logo/' . $companyLogoFileName : '/site/logo.png');
        return [
            'bootstrap5OffcanvasEnable' => $bootstrap5OffcanvasEnable,
            'bootstrap5OffcanvasPlacement' => $bootstrap5OffcanvasPlacement,
            'bootstrap5LayoutInvoiceNavbarFont' => $bootstrap5LayoutInvoiceNavbarFont,
            'bootstrap5LayoutInvoiceNavbarFontSize' => $bootstrap5LayoutInvoiceNavbarFontSize,
            'title' => 'Home',
            'logoPath' => $logoPath,
            'buildDatabase' => $buildDatabase,
            'debugMode' => $debugMode,
            'stopSigningUp' => $stopSigningUp,
            'stopLoggingIn' => $stopLoggingIn,
            'noFrontPageAbout' => $noFrontPageAbout,
            'noFrontPageAccreditations' => $noFrontPageAccreditations,
            'noFrontPageGallery' => $noFrontPageGallery,
            'noFrontPagePricing' => $noFrontPagePricing,
            'noFrontPageTeam' => $noFrontPageTeam,
            'noFrontPageTestimonial' => $noFrontPageTestimonial,
            'noFrontPagePrivacyPolicy' => $noFrontPagePrivacyPolicy,
            'noFrontPageTermsOfService' => $noFrontPageTermsOfService,
            'noFrontPageContactDetails' => $noFrontPageContactDetails,
            'noFrontPageContactUs' => $noFrontPageContactUs,
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
            /**
             * Related logic: see Use the repository name to build a quick link to scrutinizer php and javascript code checks
             * in invoice/layout under debug mode
             */
            'scrutinizerRepository' => 'rossaddison/invoice',
        ];
    }
}
