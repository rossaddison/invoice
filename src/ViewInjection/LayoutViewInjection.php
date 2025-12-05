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
use Yiisoft\Bootstrap5\DropdownItem;
use Yiisoft\I18n\Locale;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface as UrlGenerator;
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
    public function __construct(
        private CurrentUser $currentUser,
        private CompanyRepository $companyRepository,
        private CompanyPrivateRepository $companyPrivateRepository,
        private SettingRepository $settingRepository,
        private Translator $translator,
        private UrlGenerator $urlGenerator,
        private CurrentRoute $currentRoute,
    ) {
    }

    /**
     * @return array
     *
     * @psalm-return array<string, mixed>
     */
    #[\Override]
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
                    if ($private->getCompany_id() == (string) $company->getId()
                        && (
                            $private->getStart_date()?->format('Y-m-d')
                           < (new \DateTimeImmutable('now'))->format('Y-m-d')
                           && $private->getEnd_date()?->format('Y-m-d')
                           > (new \DateTimeImmutable('now'))->format('Y-m-d')
                        )) {
                        $companyLogoFileName = $private->getLogo_filename();
                        $companyLogoWidth = $private->getLogo_width();
                        $companyLogoHeight = $private->getLogo_height();
                        $companyLogoMargin = $private->getLogo_margin();
                    }
                }
            }
        }
        $bootstrap5OffcanvasPlacement =
            $this->settingRepository
                 ->getSetting('bootstrap5_offcanvas_placement') ?: 'top';
        $bootstrap5OffcanvasEnable =
            $this->settingRepository
                 ->getSetting('bootstrap5_offcanvas_enable') == '1'
                   ? true
                   : false;
        $bootstrap5LayoutInvoiceNavbarFont =
            $this->settingRepository
                 ->getSetting('bootstrap5_layout_invoice_navbar_font')
                   ?: 'Arial';
        $bootstrap5LayoutInvoiceNavbarFontSize =
            $this->settingRepository
                 ->getSetting('bootstrap5_layout_invoice_navbar_font_size')
                   ?: '10';
        $stopSigningUp =
            $this->settingRepository->getSetting('stop_signing_up') == '1'
                   ? true : false;
        $stopLoggingIn =
            $this->settingRepository->getSetting('stop_logging_in') == '1'
                   ? true : false;
        $noFrontPageAbout =
            $this->settingRepository
                 ->getSetting('no_front_about_page') == '1'
                   ? true : false;
        $noFrontPageGallery =
            $this->settingRepository
                 ->getSetting('no_front_gallery_page') == '1'
                   ? true : false;
        $noFrontPageAccreditations =
            $this->settingRepository
                 ->getSetting('no_front_accreditations_page') == '1'
                   ? true : false;
        $noFrontPageTeam =
            $this->settingRepository
                 ->getSetting('no_front_team_page') == '1'
                   ? true : false;
        $noFrontPagePricing =
            $this->settingRepository
                 ->getSetting('no_front_pricing_page') == '1'
                   ? true : false;
        $noFrontPageTestimonial =
            $this->settingRepository
                 ->getSetting('no_front_testimonial_page') == '1'
                   ? true : false;
        $noFrontPagePrivacyPolicy =
            $this->settingRepository
                 ->getSetting('no_front_privacy_policy_page') == '1'
                   ? true : false;
        $noFrontPageTermsOfService =
            $this->settingRepository
                 ->getSetting('no_front_terms_of_service_page') == '1'
                   ? true : false;
        $noFrontPageContactDetails =
            $this->settingRepository
                 ->getSetting('no_front_contact_details_page') == '1'
                   ? true : false;
        $noFrontPageContactUs =
            $this->settingRepository
                 ->getSetting('no_front_contact_us_page') == '1'
                   ? true : false;
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
        $logoPath = ((isset($companyLogoFileName)
                      && !empty($companyLogoFileName))
                      ? '/logo/' . $companyLogoFileName
                      : '/site/logo.png');        
        $_language = '_language';
        $localeSplitter =  new Locale($this->currentRoute->getArgument('_language') ?? 'en');
        $siteIndex = 'site/index';
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
            'xdebug' =>
                (extension_loaded('xdebug')
                ? 'php.ini zend_extension xdebug Installed : Performance compromised!'
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
             * Related logic:
             * see Use the repository name to build a quick link to scrutinizer php and javascript code checks
             * in invoice/layout under debug mode
             */
            'scrutinizerRepository' => 'rossaddison/invoice',
            //e.g. af-ZA split into af
            'splitterLanguage' => $localeSplitter->language(),
            //e.g. af-ZA split into ZA
            'splitterRegion' => $localeSplitter->region(),
            'afZA' => DropdownItem::link('Afrikaans South African',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'af-ZA'], fallbackRouteName: $siteIndex)),
            'arBH' => DropdownItem::link('Arabic Bahrainian/ عربي',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'ar-BH'], fallbackRouteName: $siteIndex)),
            'az' => DropdownItem::link('Azerbaijani / Azərbaycan',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'az'], fallbackRouteName: $siteIndex)),
            'beBY' => DropdownItem::link('Belarusian / Беларуская',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'be-BY'], fallbackRouteName: $siteIndex)),
            'bs' => DropdownItem::link('Bosnian / Bosanski',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'bs'], fallbackRouteName: $siteIndex)),
            'zhCN' => DropdownItem::link('Chinese Simplified / 简体中文',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'zh-CN'], fallbackRouteName: $siteIndex)),
            'zhTW' => DropdownItem::link('Tiawanese Mandarin / 简体中文',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'zh-TW'], fallbackRouteName: $siteIndex)),
            'en' => DropdownItem::link('English',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'en'], fallbackRouteName: $siteIndex)),
            'fil' => DropdownItem::link('Filipino / Filipino',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'fil'], fallbackRouteName: $siteIndex)),
            'fr' => DropdownItem::link('French / Français',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'fr'], fallbackRouteName: $siteIndex)),
            'gdGB' => DropdownItem::link('Scots Gaelic / Gàidhlig na h-Alba',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'gd-GB'], fallbackRouteName: $siteIndex)),
            'haNG' => DropdownItem::link('Hausa Nigerian / Hausawa Ɗan Najeriya',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'ha-NG'], fallbackRouteName: $siteIndex)),
            'heIL' => DropdownItem::link('Hebrew Israel / העברית ישראל',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'he-IL'], fallbackRouteName: $siteIndex)),
            'nl' =>DropdownItem::link('Dutch / Nederlands',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'nl'], fallbackRouteName: $siteIndex)),
            'de' => DropdownItem::link('German / Deutsch',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'de'], fallbackRouteName: $siteIndex)),
            'id' => DropdownItem::link('Indonesian / bahasa Indonesia',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'id'], fallbackRouteName: $siteIndex)),
            'igNG' => DropdownItem::link('Igbo Nigerian / Igbo Naịjirịa',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'ig-NG'], fallbackRouteName: $siteIndex)),
            'it' => DropdownItem::link('Italian / Italiano',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'it'], fallbackRouteName: $siteIndex)),
            'ja' => DropdownItem::link('Japanese / 日本',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'ja'], fallbackRouteName: $siteIndex)),
            'pl' => DropdownItem::link('Polish / Polski',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'pl'], fallbackRouteName: $siteIndex)),
            'ptBR' => DropdownItem::link('Portugese Brazilian / Português Brasileiro',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'pt-BR'], fallbackRouteName: $siteIndex)),
            'ru' => DropdownItem::link('Russian / Русский',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'ru'], fallbackRouteName: $siteIndex)),
            'sk' => DropdownItem::link('Slovakian / Slovenský',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'sk'], fallbackRouteName: $siteIndex)),
            'sl' => DropdownItem::link('Slovenian / Slovenski',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'sl'], fallbackRouteName: $siteIndex)),
            'es' => DropdownItem::link('Spanish /  Española x',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'es'], fallbackRouteName: $siteIndex)),
            'uk' => DropdownItem::link('Ukrainian / українська',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'uk'], fallbackRouteName: $siteIndex)),
            'uz' => DropdownItem::link('Uzbek / o' . "'" . 'zbek',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'uz'], fallbackRouteName: $siteIndex)),
            'vi' => DropdownItem::link('Vietnamese / Tiếng Việt',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'vi'], fallbackRouteName: $siteIndex)),
            'yoNG' => DropdownItem::link('Yoruba Nigerian / Ọmọ orílẹ̀-èdè Nàìjíríà',
                $this->urlGenerator
                     ->generateFromCurrent([$_language => 'yo-NG'], fallbackRouteName: $siteIndex)),
            'zuZA' => DropdownItem::link('Zulu South African/ Zulu South African',
                $this->urlGenerator
                    ->generateFromCurrent([$_language => 'zu-ZA'], fallbackRouteName: $siteIndex)),
        ];
    }
}
