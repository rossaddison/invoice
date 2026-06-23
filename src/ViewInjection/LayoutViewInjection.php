<?php

declare(strict_types=1);

namespace App\ViewInjection;

use App\Infrastructure\Persistence\Identity\Identity;
// Entities
use App\Infrastructure\Persistence\Company\Company;
use App\Infrastructure\Persistence\CompanyPrivate\CompanyPrivate;
// Repositories
use App\Invoice\Company\CompanyRepository;
use App\Invoice\CompanyPrivate\CompanyPrivateRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserInv\UserInvRepository;
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
        private UserInvRepository $userInvRepository,
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
        $company = $this->resolveActiveCompany();
        $bs = $this->resolveBootstrapSettings();
        $userState = $this->resolveUserState($company['companyLogoFileName']);

        $argLang = '_language';
        $localeSplitter = new Locale($this->currentRoute->getArgument('_language') ?? 'en');
        $siteIndex = 'site/index';

        /** @var string $userStatus */
        $userStatus = $userState['status'];
        $translateSize = match ($userStatus) {
            'accountant', 'admin' => $bs['bootstrap5LayoutInvoiceNavbarFontSize'],
            'observer' => $bs['bootstrap5LayoutGuestNavbarFontSize'],
            default => $bs['bootstrap5LayoutMainNavbarFontSize'],
        };

        $itemFontArray = [
            'style' => 'font-size: '
            . $translateSize
            . 'px;'
            . ' color: black;',
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
            'guestPageSizeUrlTemplate' => $userState['guestPageSizeUrlTemplate'],
            'guestCurrentPageSize' => $userState['guestCurrentPageSize'],
            'bootstrap5OffcanvasEnable' => $bs['bootstrap5OffcanvasEnable'],
            'bootstrap5OffcanvasPlacement' => $bs['bootstrap5OffcanvasPlacement'],
            'bootstrap5LayoutInvoiceNavbarFont' => $bs['bootstrap5LayoutInvoiceNavbarFont'],
            'bootstrap5LayoutInvoiceNavbarFontSize' => $bs['bootstrap5LayoutInvoiceNavbarFontSize'],
            'bootstrap5LayoutGuestNavbarFont' => $bs['bootstrap5LayoutGuestNavbarFont'],
            'bootstrap5LayoutGuestNavbarFontSize' => $bs['bootstrap5LayoutGuestNavbarFontSize'],
            'bootstrap5LayoutMainNavbarFont' => $bs['bootstrap5LayoutMainNavbarFont'],
            'bootstrap5LayoutMainNavbarFontSize' => $bs['bootstrap5LayoutMainNavbarFontSize'],
            'bootstrap5CdnNotNodeModule' => $bs['bootstrap5CdnNotNodeModule'],
            'bootstrap5FormFontSize' => $bs['bootstrap5FormFontSize'],
            'bootstrap5FormInputHeight' => $bs['bootstrap5FormInputHeight'],
            'appCdnNotNodeModule' => $bs['appCdnNotNodeModule'],
            'invCdnNotNodeModule' => $bs['invCdnNotNodeModule'],
            'title' => 'Home',
            'logoPath' => $userState['logoPath'],
            'buildDatabase' => $userState['buildDatabase'],
            'debugMode' => $userState['debugMode'],
            'stopSigningUp' => $bs['stopSigningUp'],
            'stopLoggingIn' => $bs['stopLoggingIn'],
            'noFrontPageAbout' => $bs['noFrontPageAbout'],
            'noFrontPageAccreditations' => $bs['noFrontPageAccreditations'],
            'noFrontPageGallery' => $bs['noFrontPageGallery'],
            'noFrontPagePricing' => $bs['noFrontPagePricing'],
            'noFrontPageTeam' => $bs['noFrontPageTeam'],
            'noFrontPageTestimonial' => $bs['noFrontPageTestimonial'],
            'noFrontPagePrivacyPolicy' => $bs['noFrontPagePrivacyPolicy'],
            'noFrontPageTermsOfService' => $bs['noFrontPageTermsOfService'],
            'noFrontPageContactDetails' => $bs['noFrontPageContactDetails'],
            'noFrontPageContactUs' => $bs['noFrontPageContactUs'],
            'isGuest' => $userState['isGuest'],
            'user' => $userState['user'],
            'userLogin' => $userState['userLogin'],
            'xdebug' =>
                (extension_loaded('xdebug')
                ? 'php.ini zend_extension xdebug Installed : Performance compromised!'
                : 'php.ini zend_extension Commented out: Performance NOT compromised'),
            // 0 => fast Read and Write, // 1 => slower Write Only
            'read_write' => $this->settingRepository->getSchemaProvidersMode(),
            'brandLabel' => $company['brandLabel'] ?? 'Yii3-i',
            'companyWeb' => $company['companyWeb'] ?? 'https://www.web.com',
            'companySlack' => $company['companySlack'] ?? 'https://www.slack.com',
            'companyFaceBook' => $company['companyFaceBook'] ?? 'https://www.facebook.com',
            'companyTwitter' => $company['companyTwitter'] ?? 'https://www.twitter.com',
            'companyLinkedIn' => $company['companyLinkedIn'] ?? 'https://www.linkedin.com',
            'companyWhatsApp' => $company['companyWhatsApp'] ?? 'https://www.whatsapp.com',
            'companyEmail' => $company['companyEmail'] ?? 'mailto:js@example.com',
            'companyLogoFileName' => $company['companyLogoFileName'] ?? '',
            'companyLogoWidth' => $company['companyLogoWidth'] ?? '',
            'companyLogoHeight' => $company['companyLogoHeight'] ?? '',
            'companyLogoMargin' => $company['companyLogoMargin'] ?? '',
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
            ...$this->buildLocaleDropdownItemsAtoO($argLang, $siteIndex, $itemFontArray, $fl),
            ...$this->buildLocaleDropdownItemsPtoZ($argLang, $siteIndex, $itemFontArray, $fl),
        ];
    }

    /**
     * @psalm-return array{
     *     brandLabel: string,
     *     companyWeb: string,
     *     companySlack: string,
     *     companyFaceBook: string,
     *     companyTwitter: string,
     *     companyLinkedIn: string,
     *     companyWhatsApp: string,
     *     companyEmail: string,
     *     companyLogoFileName: string,
     *     companyLogoWidth: int,
     *     companyLogoHeight: int,
     *     companyLogoMargin: int,
     * }
     */
    private function resolveActiveCompany(): array
    {
        $data = [
            'brandLabel' => '',
            'companyWeb' => '',
            'companySlack' => '',
            'companyFaceBook' => '',
            'companyTwitter' => '',
            'companyLinkedIn' => '',
            'companyWhatsApp' => '',
            'companyEmail' => '',
            'companyLogoFileName' => '',
            'companyLogoWidth' => 80,
            'companyLogoHeight' => 40,
            'companyLogoMargin' => 10,
        ];

        $companies = $this->companyRepository->findAllPreloaded();
        $companyPrivates = $this->companyPrivateRepository->findAllPreloaded();

        /**
         * @var Company $company
         */
        foreach ($companies as $company) {
            if ($company->getCurrent() == '1') {
                $data['brandLabel'] = $company->getName() ?? '';
                $data['companyWeb'] = $company->getWeb() ?? '';
                $data['companySlack'] = $company->getSlack() ?? '';
                $data['companyFaceBook'] = $company->getFaceBook() ?? '';
                $data['companyTwitter'] = $company->getTwitter() ?? '';
                $data['companyLinkedIn'] = $company->getLinkedIn() ?? '';
                $data['companyWhatsApp'] = $company->getWhatsApp() ?? '';
                $data['companyEmail'] = $company->getEmail() ?? '';
                /**
                 * @var CompanyPrivate $private
                 */
                foreach ($companyPrivates as $private) {
                    if ($private->reqCompanyId() === $company->reqId()
                        && (
                            $private->getStartDate()?->format('Y-m-d')
                           < (new \DateTimeImmutable('now'))->format('Y-m-d')
                           && $private->getEndDate()?->format('Y-m-d')
                           > (new \DateTimeImmutable('now'))->format('Y-m-d')
                        )) {
                        $data['companyLogoFileName'] = $private->getLogoFilename() ?? '';
                        $data['companyLogoWidth'] = $private->getLogoWidth() ?? 80;
                        $data['companyLogoHeight'] = $private->getLogoHeight() ?? 40;
                        $data['companyLogoMargin'] = $private->getLogoMargin() ?? 10;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @psalm-return array{
     *     bootstrap5OffcanvasPlacement: string,
     *     bootstrap5OffcanvasEnable: bool,
     *     bootstrap5LayoutInvoiceNavbarFont: string,
     *     bootstrap5LayoutInvoiceNavbarFontSize: string,
     *     bootstrap5LayoutGuestNavbarFont: string,
     *     bootstrap5LayoutGuestNavbarFontSize: string,
     *     bootstrap5LayoutMainNavbarFont: string,
     *     bootstrap5LayoutMainNavbarFontSize: string,
     *     appCdnNotNodeModule: bool,
     *     invCdnNotNodeModule: bool,
     *     bootstrap5CdnNotNodeModule: bool,
     *     bootstrap5FormFontSize: int,
     *     bootstrap5FormInputHeight: int,
     *     stopSigningUp: bool,
     *     stopLoggingIn: bool,
     *     noFrontPageAbout: bool,
     *     noFrontPageGallery: bool,
     *     noFrontPageAccreditations: bool,
     *     noFrontPageTeam: bool,
     *     noFrontPagePricing: bool,
     *     noFrontPageTestimonial: bool,
     *     noFrontPagePrivacyPolicy: bool,
     *     noFrontPageTermsOfService: bool,
     *     noFrontPageContactDetails: bool,
     *     noFrontPageContactUs: bool,
     * }
     */
    private function resolveBootstrapSettings(): array
    {
        $s = $this->settingRepository;
        return [
            'bootstrap5OffcanvasPlacement' =>
                $s->getSetting('bootstrap5_offcanvas_placement') ?: 'top',
            'bootstrap5OffcanvasEnable' =>
                $s->getSetting('bootstrap5_offcanvas_enable') == '1',
            'bootstrap5LayoutInvoiceNavbarFont' =>
                $s->getSetting('bootstrap5_layout_invoice_navbar_font') ?: 'Arial',
            'bootstrap5LayoutInvoiceNavbarFontSize' =>
                $s->getSetting('bootstrap5_layout_invoice_navbar_font_size') ?: '10',
            'bootstrap5LayoutGuestNavbarFont' =>
                $s->getSetting('bootstrap5_layout_guest_navbar_font') ?: 'Arial',
            'bootstrap5LayoutGuestNavbarFontSize' =>
                $s->getSetting('bootstrap5_layout_guest_navbar_font_size') ?: '10',
            'bootstrap5LayoutMainNavbarFont' =>
                $s->getSetting('bootstrap5_layout_main_navbar_font') ?: 'Arial',
            'bootstrap5LayoutMainNavbarFontSize' =>
                $s->getSetting('bootstrap5_layout_main_navbar_font_size') ?: '10',
            'appCdnNotNodeModule' =>
                $s->getSetting('app_cdn_not_node_module') == '1',
            'invCdnNotNodeModule' =>
                $s->getSetting('inv_cdn_not_node_module') == '1',
            'bootstrap5CdnNotNodeModule' =>
                $s->getSetting('bootstrap5_cdn_not_node_module') == '1',
            'bootstrap5FormFontSize' =>
                (int) ($s->getSetting('bootstrap5_form_font_size') ?: 16),
            'bootstrap5FormInputHeight' =>
                (int) ($s->getSetting('bootstrap5_form_input_height') ?: 60),
            'stopSigningUp' =>
                $s->getSetting('stop_signing_up') == '1',
            'stopLoggingIn' =>
                $s->getSetting('stop_logging_in') == '1',
            'noFrontPageAbout' =>
                $s->getSetting('no_front_about_page') == '1',
            'noFrontPageGallery' =>
                $s->getSetting('no_front_gallery_page') == '1',
            'noFrontPageAccreditations' =>
                $s->getSetting('no_front_accreditations_page') == '1',
            'noFrontPageTeam' =>
                $s->getSetting('no_front_team_page') == '1',
            'noFrontPagePricing' =>
                $s->getSetting('no_front_pricing_page') == '1',
            'noFrontPageTestimonial' =>
                $s->getSetting('no_front_testimonial_page') == '1',
            'noFrontPagePrivacyPolicy' =>
                $s->getSetting('no_front_privacy_policy_page') == '1',
            'noFrontPageTermsOfService' =>
                $s->getSetting('no_front_terms_of_service_page') == '1',
            'noFrontPageContactDetails' =>
                $s->getSetting('no_front_contact_details_page') == '1',
            'noFrontPageContactUs' =>
                $s->getSetting('no_front_contact_us_page') == '1',
        ];
    }

    /** @return array<string, mixed> */
    private function resolveUserState(string $companyLogoFileName): array
    {
        $identity = $this->currentUser->getIdentity();
        $user = $identity instanceof Identity ? $identity->getUser() : null;
        $isGuest = ($user === null);
        $userLogin = (null !== $user ? $user->getLogin() : null);
        $logoPath = ($companyLogoFileName !== '')
            ? '/logo/' . $companyLogoFileName
            : '/site/logo.png';
        /**
         * Related logic: see .env.php $_ENV['YII_DEBUG'] and $_ENV['BUILD_DATABASE'] in root folder
         * Related logic: see {root} autoload.php
         */
        $debugMode = $_ENV['YII_DEBUG'] == 'true';
        $buildDatabase = $_ENV['BUILD_DATABASE'] == 'true';
        $this->settingRepository->debugMode($debugMode);

        $status = '';
        if (null !== $user) {
            $status = $this->resolveUserStatus($user->reqId());
        }

        $guestPageSizeUrlTemplate = '';
        $guestCurrentPageSize = 10;
        if (!$isGuest && $user !== null) {
            $userInv = $this->userInvRepository->repoUserInvUserIdquery($user->reqId());
            if ($userInv !== null) {
                $routeName = $this->currentRoute->getName() ?? '';
                $slashPos = strpos($routeName, '/');
                $guestOrigin = $slashPos !== false ? substr($routeName, 0, $slashPos) : 'inv';
                $guestPageSizeUrlTemplate = $this->urlGenerator->generate('userinv/guestlimit', [
                    'userinv_id' => $userInv->reqId(),
                    'limit' => '__SIZE__',
                    'origin' => $guestOrigin,
                ]);
                $guestCurrentPageSize = $userInv->getListLimit() ?? 10;
            }
        }

        return [
            'user' => $user,
            'isGuest' => $isGuest,
            'userLogin' => $userLogin,
            'logoPath' => $logoPath,
            'debugMode' => $debugMode,
            'buildDatabase' => $buildDatabase,
            'status' => $status,
            'guestPageSizeUrlTemplate' => $guestPageSizeUrlTemplate,
            'guestCurrentPageSize' => $guestCurrentPageSize,
        ];
    }

    private function resolveUserStatus(int $userId): string
    {
        if ($this->manager->getPermissionsByUserId($userId)
              === $this->manager->getPermissionsByRoleName('observer')) {
            return 'observer';
        }
        if ($this->manager->getPermissionsByUserId($userId)
              === $this->manager->getPermissionsByRoleName('admin')) {
            return 'admin';
        }
        if ($this->manager->getPermissionsByUserId($userId)
              === $this->manager->getPermissionsByRoleName('accountant')) {
            return 'accountant';
        }
        return '';
    }

    /**
     * @param array<string, string> $itemFontArray
     * @param \Closure(string, string): NoEncode $fl
     * @return array<string, DropdownItem>
     */
    private function buildLocaleDropdownItemsAtoO(
        string $argLang,
        string $siteIndex,
        array $itemFontArray,
        \Closure $fl,
    ): array {
        return [
            'afZA' => DropdownItem::link($fl('af-ZA', 'Afrikaans South African'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'af-ZA'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'arBH' => DropdownItem::link($fl('ar-BH', 'Arabic Bahrainian/ عربي'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'ar-BH'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'az' => DropdownItem::link($fl('az', 'Azerbaijani / Azərbaycan'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'az'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'beBY' => DropdownItem::link($fl('be-BY', 'Belarusian / Беларуская'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'be-BY'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'bs' => DropdownItem::link($fl('bs', 'Bosnian / Bosanski'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'bs'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'zhCN' => DropdownItem::link($fl('zh-CN', 'Chinese Simplified / 简体中文'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'zh-CN'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'zhTW' => DropdownItem::link($fl('zh-TW', 'Tiawanese Mandarin / 简体中文'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'zh-TW'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'en' => DropdownItem::link($fl('en', 'English'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'en'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'fil' => DropdownItem::link($fl('fil', 'Filipino / Filipino'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'fil'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'fr' => DropdownItem::link($fl('fr', 'French / Français'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'fr'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'gdGB' => DropdownItem::link($fl('gd-GB', 'Scots Gaelic / Gàidhlig na h-Alba'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'gd-GB'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'haNG' => DropdownItem::link($fl('ha-NG', 'Hausa Nigerian / Hausawa Ɗan Najeriya'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'ha-NG'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'heIL' => DropdownItem::link($fl('he-IL', 'Hebrew Israel / העברית ישראל'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'he-IL'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'nl' => DropdownItem::link($fl('nl', 'Dutch / Nederlands'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'nl'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'de' => DropdownItem::link($fl('de', 'German / Deutsch'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'de'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
        ];
    }

    /**
     * @param array<string, string> $itemFontArray
     * @param \Closure(string, string): NoEncode $fl
     * @return array<string, DropdownItem>
     */
    private function buildLocaleDropdownItemsPtoZ(
        string $argLang,
        string $siteIndex,
        array $itemFontArray,
        \Closure $fl,
    ): array {
        return [
            'id' => DropdownItem::link($fl('id', 'Indonesian / bahasa Indonesia'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'id'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'igNG' => DropdownItem::link($fl('ig-NG', 'Igbo Nigerian / Igbo Naịjirịa'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'ig-NG'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'it' => DropdownItem::link($fl('it', 'Italian / Italiano'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'it'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'ja' => DropdownItem::link($fl('ja', 'Japanese / 日本'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'ja'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'pl' => DropdownItem::link($fl('pl', 'Polish / Polski'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'pl'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'ptBR' => DropdownItem::link($fl('pt-BR', 'Portugese Brazilian / Português Brasileiro'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'pt-BR'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'ru' => DropdownItem::link($fl('ru', 'Russian / Русский'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'ru'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'sk' => DropdownItem::link($fl('sk', 'Slovakian / Slovenský'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'sk'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'sl' => DropdownItem::link($fl('sl', 'Slovenian / Slovenski'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'sl'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'es' => DropdownItem::link($fl('es', 'Spanish /  Española x'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'es'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'uk' => DropdownItem::link($fl('uk', 'Ukrainian / українська'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'uk'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'uz' => DropdownItem::link($fl('uz', "Uzbek / o'zbek"),
                $this->urlGenerator->generateFromCurrent([$argLang => 'uz'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'vi' => DropdownItem::link($fl('vi', 'Vietnamese / Tiếng Việt'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'vi'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'yoNG' => DropdownItem::link($fl('yo-NG', 'Yoruba Nigerian / Ọmọ orílẹ̀-èdè Nàìjíríà'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'yo-NG'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
            'zuZA' => DropdownItem::link($fl('zu-ZA', 'Zulu South African/ Zulu South African'),
                $this->urlGenerator->generateFromCurrent([$argLang => 'zu-ZA'],
                fallbackRouteName: $siteIndex), itemAttributes: $itemFontArray),
        ];
    }
}
