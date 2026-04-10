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
use Yiisoft\Html\NoEncode;
use Yiisoft\Html\Tag\Img;
use Yiisoft\I18n\Locale;
use Yiisoft\Rbac\Manager;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface as UrlGenerator;
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
        private UrlGenerator $urlGenerator,
        private CurrentRoute $currentRoute,
        private Manager $manager,
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
         * Related logic: see src/Invoice/Entity/CompanyPrivate for default
         *  values 80, 40, 10 respectively
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
                    if ($private->getCompanyId() == (string) $company->getId()
                        && (
                            $private->getStartDate()?->format('Y-m-d')
                           < (new \DateTimeImmutable('now'))->format('Y-m-d')
                           && $private->getEndDate()?->format('Y-m-d')
                           > (new \DateTimeImmutable('now'))->format('Y-m-d')
                        )) {
                        $companyLogoFileName = $private->getLogoFilename();
                        $companyLogoWidth = $private->getLogoWidth();
                        $companyLogoHeight = $private->getLogoHeight();
                        $companyLogoMargin = $private->getLogoMargin();
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
        $bootstrap5LayoutGuestNavbarFont =
            $this->settingRepository
                 ->getSetting('bootstrap5_layout_guest_navbar_font')
                   ?: 'Arial';
        $bootstrap5LayoutGuestNavbarFontSize =
            $this->settingRepository
                 ->getSetting('bootstrap5_layout_guest_navbar_font_size')
                   ?: '10';
        $bootstrap5LayoutMainNavbarFont =
            $this->settingRepository
                 ->getSetting('bootstrap5_layout_main_navbar_font')
                   ?: 'Arial';
        $bootstrap5LayoutMainNavbarFontSize =
            $this->settingRepository
                 ->getSetting('bootstrap5_layout_main_navbar_font_size')
                   ?: '10';
        $appCdnNotNodeModule =
            $this->settingRepository
                 ->getSetting('app_cdn_not_node_module') == '1'
                   ? true : false;
        $invCdnNotNodeModule =
            $this->settingRepository
                 ->getSetting('inv_cdn_not_node_module') == '1'
                   ? true : false;
        $bootstrap5CdnNotNodeModule =
            $this->settingRepository
                 ->getSetting('bootstrap5_cdn_not_node_module') == '1'
                   ? true : false;
        $bootstrap5FormFontSize =
            (int) ($this->settingRepository
                        ->getSetting('bootstrap5_form_font_size') ?: 16);
        $bootstrap5FormInputHeight =
            (int) ($this->settingRepository
                        ->getSetting('bootstrap5_form_input_height') ?: 60);
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
        // Record the debugMode in a setting so that 'debug_mode' can be used
        //  in e.g. salesorder\guest.php`
        $this->settingRepository->debugMode($debugMode);
        $user = $identity instanceof Identity ? $identity->getUser() : null;
        $isGuest = ($user === null || $user->getId() === null);
        $userLogin = (null !== $user ? $user->getLogin() : null);
        // Show the default logo if the logo applicable dates have expired
        //  under CompanyPrivate
        $logoPath = ((isset($companyLogoFileName)
                      && !empty($companyLogoFileName))
                      ? '/logo/' . $companyLogoFileName
                      : '/site/logo.png');
        $argLang = '_language';
        $localeSplitter =  new Locale($this->currentRoute->getArgument('_language') ?? 'en');
        $siteIndex = 'site/index';
        
        $status = '';
        if (null!== $user && null!==($userId = $user->getId())) {
            if (!$isGuest && $this->manager->getPermissionsByUserId($userId)
                  === $this->manager->getPermissionsByRoleName('observer')) {
                $status = 'observer';
            }
            if (!$isGuest && $this->manager->getPermissionsByUserId($userId)
                  === $this->manager->getPermissionsByRoleName('admin')) {
                $status = 'admin';
            }
            if (!$isGuest && $this->manager->getPermissionsByUserId($userId)
                  === $this->manager->getPermissionsByRoleName('accountant')) {
                $status = 'accountant';
            }
        }
        
        $translateSize =  match ($status) {
            'accountant' => $bootstrap5LayoutInvoiceNavbarFontSize,
            'observer' => $bootstrap5LayoutGuestNavbarFontSize,
            'admin' => $bootstrap5LayoutInvoiceNavbarFontSize,
            default => $bootstrap5LayoutMainNavbarFontSize,
        };
        
        $itemFontArray = [
            'style' => 'font-size: '
            . $translateSize
            . 'px;'
            . ' color: black;'
        ];
        
        $flags = $this->settingRepository->getLocaleFlags();
        $flagImg = static fn(string $code): string
            => new Img()
               ->src('https://flagcdn.com/16x12/' . $code . '.png')
               ->width(16)
               ->height(12)
               ->alt($code)
               ->addStyle('vertical-align:middle;margin-right:5px;')
               ->render();
        
        $fl = static fn(string $locale, string $label): NoEncode
        /**
         * @psalm-suppress PossiblyNullArrayOffset $flags
         */
            => NoEncode::string($flagImg($flags[$locale] ?? 'un') . $label);
        $currentLocaleCode = ($localeSplitter->region() !== null
                && $localeSplitter->region() !== ''
                && $localeSplitter->language() !== null)
            ? $localeSplitter->language() . '-' . $localeSplitter->region()
            : $localeSplitter->language();
        /**
         * @psalm-suppress PossiblyNullArrayOffset $flags
         */
        $currentLocaleFlag = $flagImg($flags[$currentLocaleCode] ?? 'un');
        return [
            'bootstrap5OffcanvasEnable' => $bootstrap5OffcanvasEnable,
            'bootstrap5OffcanvasPlacement' => $bootstrap5OffcanvasPlacement,
            'bootstrap5LayoutInvoiceNavbarFont' => $bootstrap5LayoutInvoiceNavbarFont,
            'bootstrap5LayoutInvoiceNavbarFontSize' => $bootstrap5LayoutInvoiceNavbarFontSize,
            'bootstrap5LayoutGuestNavbarFont' => $bootstrap5LayoutGuestNavbarFont,
            'bootstrap5LayoutGuestNavbarFontSize' => $bootstrap5LayoutGuestNavbarFontSize,
            'bootstrap5LayoutMainNavbarFont' => $bootstrap5LayoutMainNavbarFont,
            'bootstrap5LayoutMainNavbarFontSize' => $bootstrap5LayoutMainNavbarFontSize,
            'bootstrap5CdnNotNodeModule' => $bootstrap5CdnNotNodeModule,
            'bootstrap5FormFontSize' => $bootstrap5FormFontSize,
            'bootstrap5FormInputHeight' => $bootstrap5FormInputHeight,
            'appCdnNotNodeModule' => $appCdnNotNodeModule,
            'invCdnNotNodeModule' => $invCdnNotNodeModule,
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
            'localeFlags'       => $flags,
            'currentLocaleFlag' => $currentLocaleFlag,
            'afZA' => DropdownItem::link($fl('af-ZA', 'Afrikaans South African'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'af-ZA'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'arBH' => DropdownItem::link($fl('ar-BH', 'Arabic Bahrainian/ عربي'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'ar-BH'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'az' => DropdownItem::link($fl('az', 'Azerbaijani / Azərbaycan'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'az'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'beBY' => DropdownItem::link($fl('be-BY', 'Belarusian / Беларуская'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'be-BY'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'bs' => DropdownItem::link($fl('bs', 'Bosnian / Bosanski'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'bs'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'zhCN' => DropdownItem::link($fl('zh-CN', 'Chinese Simplified / 简体中文'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'zh-CN'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'zhTW' => DropdownItem::link($fl('zh-TW', 'Tiawanese Mandarin / 简体中文'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'zh-TW'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'en' => DropdownItem::link($fl('en', 'English'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'en'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'fil' => DropdownItem::link($fl('fil', 'Filipino / Filipino'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'fil'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'fr' => DropdownItem::link($fl('fr', 'French / Français'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'fr'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'gdGB' => DropdownItem::link($fl('gd-GB', 'Scots Gaelic / Gàidhlig na h-Alba'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'gd-GB'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'haNG' => DropdownItem::link($fl('ha-NG', 'Hausa Nigerian / Hausawa Ɗan Najeriya'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'ha-NG'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'heIL' => DropdownItem::link($fl('he-IL', 'Hebrew Israel / העברית ישראל'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'he-IL'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'nl' =>DropdownItem::link($fl('nl', 'Dutch / Nederlands'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'nl'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'de' => DropdownItem::link($fl('de', 'German / Deutsch'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'de'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'id' => DropdownItem::link($fl('id', 'Indonesian / bahasa Indonesia'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'id'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'igNG' => DropdownItem::link($fl('ig-NG', 'Igbo Nigerian / Igbo Naịjirịa'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'ig-NG'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'it' => DropdownItem::link($fl('it', 'Italian / Italiano'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'it'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'ja' => DropdownItem::link($fl('ja', 'Japanese / 日本'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'ja'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'pl' => DropdownItem::link($fl('pl', 'Polish / Polski'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'pl'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'ptBR' => DropdownItem::link($fl('pt-BR', 'Portugese Brazilian / Português Brasileiro'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'pt-BR'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'ru' => DropdownItem::link($fl('ru', 'Russian / Русский'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'ru'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'sk' => DropdownItem::link($fl('sk', 'Slovakian / Slovenský'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'sk'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'sl' => DropdownItem::link($fl('sl', 'Slovenian / Slovenski'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'sl'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'es' => DropdownItem::link($fl('es', 'Spanish /  Española x'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'es'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'uk' => DropdownItem::link($fl('uk', 'Ukrainian / українська'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'uk'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'uz' => DropdownItem::link($fl('uz', "Uzbek / o'zbek"),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'uz'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'vi' => DropdownItem::link($fl('vi', 'Vietnamese / Tiếng Việt'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'vi'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'yoNG' => DropdownItem::link($fl('yo-NG', 'Yoruba Nigerian / Ọmọ orílẹ̀-èdè Nàìjíríà'),
                $this->urlGenerator
                     ->generateFromCurrent([$argLang => 'yo-NG'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
            'zuZA' => DropdownItem::link($fl('zu-ZA', 'Zulu South African/ Zulu South African'),
                $this->urlGenerator
                    ->generateFromCurrent([$argLang => 'zu-ZA'],
                     fallbackRouteName: $siteIndex),
                     itemAttributes: $itemFontArray),
        ];
    }
}
