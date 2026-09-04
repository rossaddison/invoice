<?php

declare(strict_types=1);

namespace App\Auth\Trait;

use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface;

/**
 * @property-read WebControllerService $webService
 */
trait Redirects
{
    private function redirectToMain(): ResponseInterface
    {
        return $this->webService->getRedirectResponse(
            'site/index',
            ['_language' => 'en']
        );
    }

    private function redirectToInvoiceIndex(): ResponseInterface
    {
        return $this->webService->getRedirectResponse('invoice/index');
    }

    protected function redirectToAdminMustMakeActive(): ResponseInterface
    {
        return $this->webService->getRedirectResponse(
            'site/adminmustmakeactive',
            ['_language' => 'en']
        );
    }

    protected function redirectToEmailNotVerified(): ResponseInterface
    {
        return $this->webService->getRedirectResponse(
            'site/emailnotverified',
            ['_language' => 'en']
        );
    }
}
