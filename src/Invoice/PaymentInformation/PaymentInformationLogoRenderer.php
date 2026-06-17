<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use App\Infrastructure\Persistence\Company\Company;
use App\Infrastructure\Persistence\CompanyPrivate\CompanyPrivate;
use App\Invoice\Company\CompanyRepository as compR;
use App\Invoice\CompanyPrivate\CompanyPrivateRepository as cPR;
use App\Invoice\Setting\SettingRepository as sR;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class PaymentInformationLogoRenderer
{
    public function __construct(
        private compR $compR,
        private cPR $cPR,
        private sR $sR,
        private WebViewRenderer $webViewRenderer,
    ) {
    }

    public function companyLogo(): string
    {
        $companies           = $this->compR->findAllPreloaded();
        $companyPrivates     = $this->cPR->findAllPreloaded();
        $companyLogoFileName = '';
        /**
         * @var Company $company
         */
        foreach ($companies as $company) {
            if ('1' == $company->getCurrent()) {
                /**
                 * @var CompanyPrivate $private
                 */
                foreach ($companyPrivates as $private) {
                    if ($private->reqCompanyId() === $company->reqId()) {
                        $companyLogoFileName = $private->getLogoFilename();
                        $companyLogoWidth    = $private->getLogoWidth() ?? 0;
                        $companyLogoHeight   = $private->getLogoHeight() ?? 0;
                        $companyLogoMargin   = $private->getLogoMargin() ?? 0;
                    }
                }
            }
            break;
        }
        $src = (null !== $companyLogoFileName
            ? '/logo/' . $companyLogoFileName
            : '/site/logo.png');
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/paymentinformation/logo/companyLogo',
            [
                'src'          => $src,
                'tooltipTitle' => '1' == $this->sR->getSetting('debug_mode') ? $src : '',
                'logoWidth'    => $companyLogoWidth ?? 0,
                'logoHeight'   => $companyLogoHeight ?? 0,
                'logoMargin'   => $companyLogoMargin ?? 0,
            ],
        );
    }

    public function braintreeLogo(string $merchantId): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/paymentinformation/logo/braintreeLogo',
            ['merchantId' => $merchantId],
        );
    }

    public function mollieLogo(): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/paymentinformation/logo/mollieLogo',
        );
    }
}
