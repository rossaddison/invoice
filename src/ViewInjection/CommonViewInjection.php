<?php

declare(strict_types=1);

namespace App\ViewInjection;

use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\Renderer\CommonParametersInjectionInterface;
use App\Invoice\Entity\Company;
use App\Invoice\Entity\CompanyPrivate;
use App\Invoice\Company\CompanyRepository;
use App\Invoice\CompanyPrivate\CompanyPrivateRepository;
use App\Invoice\Setting\SettingRepository;

final readonly class CommonViewInjection implements CommonParametersInjectionInterface
{
    public function __construct(private UrlGeneratorInterface $url, private CompanyRepository $companyRepository, private CompanyPrivateRepository $companyPrivateRepository, private SettingRepository $settingRepository, private Translator $translator)
    {
    }

    /**
     * @return array
     * @psalm-return array<string, mixed>
     */
    #[\Override]
    public function getCommonParameters(): array
    {
        $companies = $this->companyRepository->findAllPreloaded();
        $companyPrivates = $this->companyPrivateRepository->findAllPreloaded();
        $companyName = '';
        $companyWeb = '';
        $companyAddress1 = '';
        $companyAddress2 = '';
        $companyCity = '';
        $companyState = '';
        $companyZip = '';
        $companyCountry = '';
        $companyPhone = '';
        $companyEmail = '';
        $companyLogoFileName = '';
        $companyStartDate = '';
        $arbitrationBody = '';
        $arbitrationJurisdiction = '';
        /**
         * @var Company $company
         */
        foreach ($companies as $company) {
            if ($company->getCurrent() == '1') {
                $companyName = $company->getName();
                $companyWeb = $company->getWeb();
                $companyAddress1 = $company->getAddress_1();
                $companyAddress2 = $company->getAddress_2();
                $companyCity = $company->getCity();
                $companyState = $company->getState();
                $companyZip = $company->getZip();
                $companyCountry = $company->getCountry();
                $companyPhone = $company->getPhone();
                $companyEmail = $company->getEmail();
                $arbitrationBody = $company->getArbitrationBody();
                $arbitrationJurisdiction = $company->getArbitrationJurisdiction();

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
                            $companyStartDate = $private->getStart_date()?->format('Y-m-d');
                            //  break;
                        }
                    }
                }
            }
        }
        $logoPath = (
            (isset($companyLogoFileName) && !empty($companyLogoFileName))
                                      ? '/logo/' . $companyLogoFileName
                                      : '/site/' . $this->settingRepository->public_logo() . '.png'
        );

        return [
            'arbitrationBody' => $arbitrationBody ?? '',
            'arbitrationJurisdiction' => $arbitrationJurisdiction ?? '',
            'companyAddress1' => $companyAddress1 ?? '',
            'companyAddress2' => $companyAddress2 ?? '',
            'companyCity' => $companyCity ?? '',
            'companyState' => $companyState ?? '',
            'companyZip' => $companyZip ?? '',
            'companyCountry' => $companyCountry ?? '',
            'companyPhone' => $companyPhone ?? '',
            'companyEmail' => $companyEmail ?? '',
            'companyLogoFileName' => $companyLogoFileName ?? '',
            'companyLogoWidth' => $companyLogoWidth ?? 80,
            'companyLogoHeight' => $companyLogoHeight ?? 40,
            'companyName' => $companyName ?? '',
            'companyStartDate' => $companyStartDate ?? date('Y-m-d'),
            'companyWeb' => $companyWeb ?? 'mywebpage.com',
            'logoPath' => $logoPath,
            'translator' => $this->translator,
            'url' => $this->url,

            /**
             * @see \invoice\resources\messages\en\app.php
             * @see \invoice\vendor\yiisoft\yii-view\src\ViewRenderer.php function getCommonParameters
             */
            'about' => [
                'we' => $this->translator->translate('site.soletrader.about.we'),
                'choose' => $this->translator->translate('site.soletrader.about.choose'),
                'competitive' => $this->translator->translate('site.soletrader.about.competitive.rates'),
                'quality' => $this->translator->translate('site.soletrader.about.quality'),
                'contemporary' => $this->translator->translate('site.soletrader.about.contemporary'),
                'trained' => $this->translator->translate('site.soletrader.about.trained'),
                'willing' => $this->translator->translate('site.soletrader.about.willing'),
                'dissatisfaction' => $this->translator->translate('site.soletrader.about.dissatisfaction'),
                'simply' => $this->translator->translate('site.soletrader.about.simply'),
                'happy' => $this->translator->translate('site.soletrader.about.happy'),
                'solved' => $this->translator->translate('site.soletrader.about.solved'),
                'finished' => $this->translator->translate('site.soletrader.about.finished'),
                'return' => $this->translator->translate('site.soletrader.about.return'),
            ],
            'team' => [
                'we' => $this->translator->translate('site.soletrader.team.we'),
                'coordinator' => $this->translator->translate('site.soletrader.team.coordinator'),
                'assistant' => $this->translator->translate('site.soletrader.team.assistant'),
            ],
            'pricing' => [
                'pricing' => $this->translator->translate('site.soletrader.pricing.pricing'),
                'explore' => $this->translator->translate('site.soletrader.pricing.explore'),
                'plans' => $this->translator->translate('site.soletrader.pricing.plans'),
                'starter' => $this->translator->translate('site.soletrader.pricing.starter'),
                'currencyAmount' => $this->translator->translate('site.soletrader.pricing.currencyAmount'),
                'currencyPerMonth' => $this->translator->translate('site.soletrader.pricing.currencyPerMonth'),
                'basic' => $this->translator->translate('site.soletrader.pricing.basic'),
                'visits' => $this->translator->translate('site.soletrader.pricing.visits'),
                'pro' => $this->translator->translate('site.soletrader.pricing.pro'),
                'proPrice' => $this->translator->translate('site.soletrader.pricing.proPrice'),
                'special' => $this->translator->translate('site.soletrader.pricing.special'),
                'choosePlan' => $this->translator->translate('site.soletrader.pricing.choosePlan'),
            ],
            'testimonial' => [
                'we' => $this->translator->translate('site.soletrader.testimonial.we'),
                'worker1' => $this->translator->translate('site.soletrader.testimonial.worker1'),
                'worker2' => $this->translator->translate('site.soletrader.testimonial.worker2'),
                'worker3' => $this->translator->translate('site.soletrader.testimonial.worker3'),
            ],
            'contact' => [
                'touch' => $this->translator->translate('site.soletrader.contact.touch'),
                'lookout' => $this->translator->translate('site.soletrader.contact.lookout'),
                'address' => $this->translator->translate('site.soletrader.contact.address'),
                'email' => $this->translator->translate('site.soletrader.contact.email'),
                'phone' => $this->translator->translate('site.soletrader.contact.phone'),
            ],
            'forgotalert' => [
                'passwordResetEmail' => $this->translator->translate('i.password_reset_email'),
            ],
            'adminmustmakeactive' => [
                'adminMustMakeActive' => $this->translator->translate('i.loginalert_user_inactive'),
            ],
            'forgotemailfailed' => [
                'passwordResetFailed' => $this->translator->translate('i.password_reset_failed'),
                'invoiceEmailException' => $this->translator->translate('invoice.email.exception'),
            ],
            'forgotusernotfound' => [
                'loginAlertUserNotFound' => $this->translator->translate('i.loginalert_user_not_found'),
            ],
            'oauth2callbackresultunauthorised' => [
                'oauth2callbackresultunauthorised' => $this->translator->translate('layout.page.not-authorised'),
            ],
            'resetpasswordfailed' => [
                'resetPasswordFailed' => $this->translator->translate('i.password_reset_failed'),
            ],
            'resetpasswordsuccess' => [
                'resetPasswordSuccess' => $this->translator->translate('i.password_reset'),
            ],
            'signupfailed' => [
                'emailNotSentSuccessfully' => $this->translator->translate('invoice.invoice.email.not.sent.successfully'),
                'invoiceEmailException' => $this->translator->translate('invoice.email.exception'),
                'localhostUserCanLoginAfterAdminMakesActive' => $this->translator->translate('i.loginalert_user_inactive'),
            ],
            'signupsuccess' => [
                'emailSuccessfullySent' => $this->translator->translate('i.email_successfully_sent'),
            ],
        ];
    }
}
