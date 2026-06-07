<?php

declare(strict_types=1);

namespace App\ViewInjection;

use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\Renderer\CommonParametersInjectionInterface;
use App\Infrastructure\Persistence\Company\Company;
use App\Infrastructure\Persistence\CompanyPrivate\CompanyPrivate;
use App\Invoice\Company\CompanyRepository;
use App\Invoice\CompanyPrivate\CompanyPrivateRepository;
use App\Invoice\Setting\SettingRepository;

final readonly class CommonViewInjection implements CommonParametersInjectionInterface
{
    public function __construct(private UrlGeneratorInterface $url,
        private CompanyRepository $companyRepository,
        private CompanyPrivateRepository $companyPrivateRepository,
        private SettingRepository $settingRepository,
        private Translator $translator)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function getCommonParameters(): array
    {
        return array_merge(
            $this->resolveCompanyData(),
            ['translator' => $this->translator, 'url' => $this->url],
            $this->translationParameters(),
        );
    }

    /**
     * Finds the current company and its active logo, returns all company fields.
     *
     * @return array<string, mixed>
     */
    private function resolveCompanyData(): array
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
                $companySeoDescription = $company->getSeoDescription();
                $companyAddress1 = $company->getAddress1();
                $companyAddress2 = $company->getAddress2();
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
                    // site's logo: take the first logo where the current date falls within
                    // the logo's start and end dates
                    if ($private->reqCompanyId() === $company->reqId()
                        && ($private->getStartDate()?->format('Y-m-d') <
                            (new \DateTimeImmutable('now'))->format('Y-m-d'))
                        && ($private->getEndDate()?->format('Y-m-d') >
                            (new \DateTimeImmutable('now'))->format('Y-m-d'))) {
                        $companyLogoFileName = $private->getLogoFilename() ?? '';
                        $companyLogoWidth = $private->getLogoWidth();
                        $companyLogoHeight = $private->getLogoHeight();
                        $companyStartDate = $private->getStartDate()?->format('Y-m-d');
                    }
                }
            }
        }
        $logoPath = $companyLogoFileName !== ''
            ? '/logo/' . $companyLogoFileName
            : '/site/' . $this->settingRepository->publicLogo() . '.png';

        return [
            'arbitrationBody' => $arbitrationBody,
            'arbitrationJurisdiction' => $arbitrationJurisdiction,
            'companyAddress1' => $companyAddress1,
            'companyAddress2' => $companyAddress2,
            'companyCity' => $companyCity,
            'companyState' => $companyState,
            'companyZip' => $companyZip,
            'companyCountry' => $companyCountry,
            'companyPhone' => $companyPhone,
            'companyEmail' => $companyEmail,
            'companyLogoFileName' => $companyLogoFileName,
            'companyLogoWidth' => $companyLogoWidth ?? 80,
            'companyLogoHeight' => $companyLogoHeight ?? 40,
            'companyName' => $companyName,
            'companySeoDescription' => $companySeoDescription ?? 'Search Engine Optimization Description',
            'companyStartDate' => $companyStartDate !== '' ? $companyStartDate : date('Y-m-d'),
            'companyWeb' => $companyWeb !== '' ? $companyWeb : 'mywebpage.com',
            'logoPath' => $logoPath,
        ];
    }

    /**
     * Returns all soletrader translation arrays and auth-alert parameters.
     *
     * Related logic: see resources/messages/en/app.php
     *
     * @return array<string, array<string, string>>
     */
    private function translationParameters(): array
    {
        $t = $this->translator;
        return [
            'about' => [
                'we' => $t->translate('site.soletrader.about.we'),
                'choose' => $t->translate('site.soletrader.about.choose'),
                'competitive' => $t->translate('site.soletrader.about.competitive.rates'),
                'quality' => $t->translate('site.soletrader.about.quality'),
                'contemporary' => $t->translate('site.soletrader.about.contemporary'),
                'trained' => $t->translate('site.soletrader.about.trained'),
                'willing' => $t->translate('site.soletrader.about.willing'),
                'dissatisfaction' => $t->translate('site.soletrader.about.dissatisfaction'),
                'simply' => $t->translate('site.soletrader.about.simply'),
                'happy' => $t->translate('site.soletrader.about.happy'),
                'solved' => $t->translate('site.soletrader.about.solved'),
                'finished' => $t->translate('site.soletrader.about.finished'),
                'return' => $t->translate('site.soletrader.about.return'),
            ],
            'team' => [
                'we' => $t->translate('site.soletrader.team.we'),
                'coordinator' => $t->translate('site.soletrader.team.coordinator'),
                'assistant' => $t->translate('site.soletrader.team.assistant'),
            ],
            'pricing' => [
                'pricing' => $t->translate('site.soletrader.pricing.pricing'),
                'explore' => $t->translate('site.soletrader.pricing.explore'),
                'plans' => $t->translate('site.soletrader.pricing.plans'),
                'starter' => $t->translate('site.soletrader.pricing.starter'),
                'currencyAmount' => $t->translate('site.soletrader.pricing.currencyAmount'),
                'currencyPerMonth' => $t->translate('site.soletrader.pricing.currencyPerMonth'),
                'basic' => $t->translate('site.soletrader.pricing.basic'),
                'visits' => $t->translate('site.soletrader.pricing.visits'),
                'pro' => $t->translate('site.soletrader.pricing.pro'),
                'proPrice' => $t->translate('site.soletrader.pricing.proPrice'),
                'special' => $t->translate('site.soletrader.pricing.special'),
                'choosePlan' => $t->translate('site.soletrader.pricing.choosePlan'),
            ],
            'testimonial' => [
                'we' => $t->translate('site.soletrader.testimonial.we'),
                'worker1' => $t->translate('site.soletrader.testimonial.worker1'),
                'worker2' => $t->translate('site.soletrader.testimonial.worker2'),
                'worker3' => $t->translate('site.soletrader.testimonial.worker3'),
            ],
            'contact' => [
                'touch' => $t->translate('site.soletrader.contact.touch'),
                'lookout' => $t->translate('site.soletrader.contact.lookout'),
                'address' => $t->translate('site.soletrader.contact.address'),
                'email' => $t->translate('site.soletrader.contact.email'),
                'phone' => $t->translate('site.soletrader.contact.phone'),
            ],
            'forgotalert' => [
                'passwordResetEmail' => $t->translate('password.reset.email'),
            ],
            'adminmustmakeactive' => [
                'adminMustMakeActive' => $t->translate('loginalert.user.inactive'),
            ],
            'forgotemailfailed' => [
                'passwordResetFailed' => $t->translate('password.reset.failed'),
                'invoiceEmailException' => $t->translate('email.exception'),
            ],
            'forgotusernotfound' => [
                'loginAlertUserNotFound' => $t->translate('loginalert.user.not.found'),
            ],
            'oauth2callbackresultunauthorised' => [
                'oauth2callbackresultunauthorised' => $t->translate('layout.page.not-authorised'),
            ],
            'onetimepassworderror' => [
                'onetimePasswordError' => $t->translate('two.factor.authentication.error'),
            ],
            'onetimepasswordfailure' => [
                'onetimePasswordFailure' => $t->translate('two.factor.authentication.attempt.failure'),
            ],
            'onetimepasswordsuccess' => [
                'onetimePasswordSuccess' => $t->translate('two.factor.authentication.attempt.success'),
            ],
            'resetpasswordfailed' => [
                'resetPasswordFailed' => $t->translate('password.reset.failed'),
            ],
            'resetpasswordsuccess' => [
                'resetPasswordSuccess' => $t->translate('password.reset'),
            ],
            'signupfailed' => [
                'emailNotSentSuccessfully' => $t->translate('email.not.sent.successfully'),
                'invoiceEmailException' => $t->translate('email.exception'),
                'localhostUserCanLoginAfterAdminMakesActive' => $t->translate('loginalert.user.inactive'),
            ],
            'signupsuccess' => [
                'emailSuccessfullySent' => $t->translate('email.successfully.sent'),
            ],
        ];
    }
}
