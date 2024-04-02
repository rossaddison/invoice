<?php

declare(strict_types=1);

namespace App\ViewInjection;

use App\Auth\Identity;
use App\Invoice\Entity\Company;
use App\Invoice\Entity\CompanyPrivate;
use App\Invoice\Company\CompanyRepository;
use App\Invoice\CompanyPrivate\CompanyPrivateRepository;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\LayoutParametersInjectionInterface;

/**
 * @see ./views/layout/main.php or alternative(s)
 * @see ./views/layout/templates/soletrader/main.php
 * @see ./invoice/config/common/params.php 'yiisoft/yii-view' 
 */

final class LayoutViewInjection implements LayoutParametersInjectionInterface
{
    private CompanyRepository $companyRepository;
    private CompanyPrivateRepository $companyPrivateRepository;
    
    public function __construct(private CurrentUser $currentUser, 
                                CompanyRepository $companyRepository,
                                CompanyPrivateRepository $companyPrivateRepository    
    )
    {
        $this->companyRepository = $companyRepository;
        $this->companyPrivateRepository = $companyPrivateRepository;
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
                              //  break;
                        }    
                    }
                }
            }
        }
        
        return [
            'title' => 'Home',
            'debugMode' => true,
            'brandLabel' => $brandLabel ?? 'Yii3-i',
            'user' => $identity instanceof Identity ? $identity->getUser() : null,
            'companyWeb' => $companyWeb ?? 'https://www.web.com',
            'companySlack' => $companySlack ?? 'https://www.slack.com',
            'companyFaceBook' => $companyFaceBook ?? 'https://www.facebook.com',
            'companyTwitter' => $companyTwitter ?? 'https://www.twitter.com',
            'companyLinkedIn' => $companyLinkedIn ?? 'https://www.linkedin.com',
            'companyWhatsApp' => $companyWhatsApp ?? 'https://www.whatsapp.com',
            'companyEmail' => $companyEmail ?? 'mailto:js@example.com',
            'companyLogoFileName' => $companyLogoFileName ?? ''
        ];
    }
}
