<?php

declare(strict_types=1);

namespace App\ViewInjection;

use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\CommonParametersInjectionInterface;
use App\Invoice\Entity\Company;
use App\Invoice\Company\CompanyRepository;

final class CommonViewInjection implements CommonParametersInjectionInterface
{
    private CompanyRepository $companyRepository;
    private Translator $translator;
    
    public function __construct(
            private UrlGeneratorInterface $url,
            CompanyRepository $companyRepository,
            Translator $translator
    )
    {
        $this->companyRepository = $companyRepository;
        $this->translator = $translator;
    }

    /**
     * @return array
     * @psalm-return array<string, mixed>
     */
    public function getCommonParameters(): array
    {
        $companies = $this->companyRepository->findAllPreloaded();
        $companyAddress1 = '';
        $companyAddress2 = '';
        $companyCity = '';
        $companyState = '';
        $companyZip = '';
        $companyPhone = '';
        $companyEmail = '';
        /**
         * @var Company $company
         */
        foreach ($companies as $company) {
            if ($company->getCurrent() == '1') {
                $companyAddress1 = $company->getAddress_1();
                $companyAddress2 = $company->getAddress_2();
                $companyCity = $company->getCity();
                $companyState = $company->getState();
                $companyZip = $company->getZip();
                $companyPhone = $company->getPhone();
                $companyEmail = $company->getEmail();
            }
        }
        return [
            'url' => $this->url,
            'companyAddress1' => $companyAddress1 ?? '',
            'companyAddress2' => $companyAddress2 ?? '',
            'companyCity' => $companyCity ?? '',
            'companyState' => $companyState ?? '',
            'companyZip' => $companyZip ?? '',
            'companyPhone' => $companyPhone ?? '',
            'companyEmail' => $companyEmail ?? '',
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
                'happy' =>  $this->translator->translate('site.soletrader.about.happy'),
                'solved' =>  $this->translator->translate('site.soletrader.about.solved'),
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
                'choosePlan' => $this->translator->translate('site.soletrader.pricing.choosePlan')
            ],
            'testimonial' => [
                'we' => $this->translator->translate('site.soletrader.testimonial.we'),
                'worker1' => $this->translator->translate('site.soletrader.testimonial.worker1'),
            ],
            'contact' => [
                'touch' => $this->translator->translate('site.soletrader.contact.touch'),
                'lookout' => $this->translator->translate('site.soletrader.contact.lookout'),
                'address' => $this->translator->translate('site.soletrader.contact.address'),
                'email' => $this->translator->translate('site.soletrader.contact.email'),
                'phone' => $this->translator->translate('site.soletrader.contact.phone'),
            ]
        ];
    }
}