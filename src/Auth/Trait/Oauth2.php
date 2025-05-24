<?php

declare(strict_types=1);

namespace App\Auth\Trait;

use App\Auth\Token;
use App\Auth\TokenRepository;
use App\User\User;
use App\Invoice\Setting\SettingRepository as sR;
use Yiisoft\Yii\AuthClient\Client\DeveloperSandboxHmrc;
use Yiisoft\Yii\AuthClient\Client\Facebook;
use Yiisoft\Yii\AuthClient\Client\GitHub;
use Yiisoft\Yii\AuthClient\Client\Google;
use Yiisoft\Yii\AuthClient\Client\GovUk;
use Yiisoft\Yii\AuthClient\Client\LinkedIn;
use Yiisoft\Yii\AuthClient\Client\MicrosoftOnline;
use Yiisoft\Yii\AuthClient\Client\VKontakte;
use Yiisoft\Yii\AuthClient\Client\X;
use Yiisoft\Yii\AuthClient\Client\Yandex;

trait Oauth2
{
    public const string DEVELOPER_SANDBOX_HMRC_ACCESS_TOKEN = 'developersandboxhmrc-access';
    
    public const string GITHUB_ACCESS_TOKEN = 'github-access';

    public const string FACEBOOK_ACCESS_TOKEN = 'facebook-access';

    public const string GOOGLE_ACCESS_TOKEN = 'google-access';

    public const string GOVUK_ACCESS_TOKEN = 'govuk-access';

    public const string LINKEDIN_ACCESS_TOKEN = 'linkedin-access';

    public const string MICROSOFTONLINE_ACCESS_TOKEN = 'microsoftonline-access';

    public const string VKONTAKTE_ACCESS_TOKEN = 'vkontakte-access';

    public const string X_ACCESS_TOKEN = 'x-access';

    public const string YANDEX_ACCESS_TOKEN = 'yandex-access';

    private function initializeOauth2IdentityProviderCredentials(
        DeveloperSandboxHmrc $developerSandboxHmrc,    
        Facebook $facebook,
        GitHub $github,
        Google $google,
        GovUk $govUk,
        LinkedIn $linkedIn,
        MicrosoftOnline $microsoftOnline,
        VKontakte $vkontakte,
        X $x,
        Yandex $yandex
    ): void {
        /**
         * @see config/common/params.php
         */
        $developerSandboxHmrc->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('developersandboxhmrc'));
        $facebook->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('facebook'));
        $github->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('github'));
        $google->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('google'));
        $govUk->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('govuk'));
        $linkedIn->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('linkedin'));
        $microsoftOnline->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('microsoftonline'));
        $vkontakte->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('vkontakte'));
        $x->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('x'));
        $yandex->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('yandex'));

        $developerSandboxHmrc->setClientId($this->sR->getOauth2IdentityProviderClientId('developersandboxhmrc'));
        $facebook->setClientId($this->sR->getOauth2IdentityProviderClientId('facebook'));
        $github->setClientId($this->sR->getOauth2IdentityProviderClientId('github'));
        $google->setClientId($this->sR->getOauth2IdentityProviderClientId('google'));
        $govUk->setClientId($this->sR->getOauth2IdentityProviderClientId('govuk'));
        $linkedIn->setClientId($this->sR->getOauth2IdentityProviderClientId('linkedin'));
        $microsoftOnline->setClientId($this->sR->getOauth2IdentityProviderClientId('microsoftonline'));
        $vkontakte->setClientId($this->sR->getOauth2IdentityProviderClientId('vkontakte'));
        $x->setClientId($this->sR->getOauth2IdentityProviderClientId('x'));
        $yandex->setClientId($this->sR->getOauth2IdentityProviderClientId('yandex'));
        
        $developerSandboxHmrc->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('developersandboxhmrc'));
        $facebook->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('facebook'));
        $github->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('github'));
        $google->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('google'));
        $govUk->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('govuk'));
        $linkedIn->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('linkedin'));
        $microsoftOnline->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('microsoftonline'));
        $vkontakte->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('vkontakte'));
        $x->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('x'));
        $yandex->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('yandex'));

        /**
         * @see https://entra.microsoft.com/#view/Microsoft_AAD_IAM/TenantOverview.ReactView
         * Rebuild the authUrl and tokenUrl to include the tenant (default: 'common') which can be
         * 'common', 'organisation', 'consumers', or ID. ID is used here.
         * The tenant can be acquired from Microsoft Entra Admin Centre ... Identity Overview ... Tenant
         * and is inserted into the root's .env file.
         */
        $microsoftOnline->setTenant($this->sR->getOauth2MicrosoftEntraIdentityOverviewTenant('microsoftonline'));

        $authUrl = $microsoftOnline->getAuthUrlWithTenantInserted($microsoftOnline->getTenant());
        $microsoftOnline->setAuthUrl($authUrl);

        $tokenUrl = $microsoftOnline->getTokenUrlWithTenantInserted($microsoftOnline->getTenant());
        $microsoftOnline->setTokenUrl($tokenUrl);
    }
    
    /**
     * Initialize development or production urls
     * 
     * @param sR $sR
     * @param DeveloperSandboxHmrc $developerSandboxHmrc
     * @return void
     */
    private function initializeOauth2IdentityProviderDualUrls(
        sR $sR,    
        DeveloperSandboxHmrc $developerSandboxHmrc,    
    ): void 
    {
        if ($sR->getEnv() == 'dev') {
            $developerSandboxHmrc->setEnvironment('dev');
        } else {
            $developerSandboxHmrc->setEnvironment('prod');
        }
    }

    /**
     * @param User $user
     * @param TokenRepository $tR
     * @param string $self
     * @return string
     */
    private function getAccessToken(User $user, TokenRepository $tR, string $self): string
    {
        $identity = $user->getIdentity();
        $identityId = (int)$identity->getId();
        // This records the fact that the user has signed up with e.g. a Github 'access-token'
        $token = new Token($identityId, $self);
        // store the token amongst all the other types of tokens e.g. password-rest, email-verification, github-access
        $tR->save($token);
        $tokenString = $token->getToken();
        $timeString = (string)$token->getCreated_at()->getTimestamp();
        // build the token with a timestamp built into it for comparison later
        return null !== $tokenString ? ($tokenString . '_' . $timeString) : '';
    }
    
    private function getDeveloperSandboxHmrcAccessToken(User $user, TokenRepository $tR): string
    {
        return $this->getAccessToken($user, $tR, self::DEVELOPER_SANDBOX_HMRC_ACCESS_TOKEN);
    }

    private function getGithubAccessToken(User $user, TokenRepository $tR): string
    {
        return $this->getAccessToken($user, $tR, self::GITHUB_ACCESS_TOKEN);
    }

    private function getFaceBookAccessToken(User $user, TokenRepository $tR): string
    {
        return $this->getAccessToken($user, $tR, self::FACEBOOK_ACCESS_TOKEN);
    }

    private function getGoogleAccessToken(User $user, TokenRepository $tR): string
    {
        return $this->getAccessToken($user, $tR, self::GOOGLE_ACCESS_TOKEN);
    }

    private function getGovUkAccessToken(User $user, TokenRepository $tR): string
    {
        return $this->getAccessToken($user, $tR, self::GOVUK_ACCESS_TOKEN);
    }

    private function getLinkedInAccessToken(User $user, TokenRepository $tR): string
    {
        return $this->getAccessToken($user, $tR, self::LINKEDIN_ACCESS_TOKEN);
    }

    private function getMicrosoftOnlineAccessToken(User $user, TokenRepository $tR): string
    {
        return $this->getAccessToken($user, $tR, self::MICROSOFTONLINE_ACCESS_TOKEN);
    }

    private function getVKontakteAccessToken(User $user, TokenRepository $tR): string
    {
        return $this->getAccessToken($user, $tR, self::VKONTAKTE_ACCESS_TOKEN);
    }

    private function getXAccessToken(User $user, TokenRepository $tR): string
    {
        return $this->getAccessToken($user, $tR, self::X_ACCESS_TOKEN);
    }

    private function getYandexAccessToken(User $user, TokenRepository $tR): string
    {
        return $this->getAccessToken($user, $tR, self::YANDEX_ACCESS_TOKEN);
    }
}
