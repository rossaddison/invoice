<?php

declare(strict_types=1);

namespace App\Auth\Trait;

use App\Auth\Token;
use App\Auth\TokenRepository;
use App\User\User;
use Yiisoft\Yii\AuthClient\Client\Facebook;
use Yiisoft\Yii\AuthClient\Client\GitHub;
use Yiisoft\Yii\AuthClient\Client\Google;
use Yiisoft\Yii\AuthClient\Client\LinkedIn;
use Yiisoft\Yii\AuthClient\Client\MicrosoftOnline;

trait Oauth2
{
    public const string GITHUB_ACCESS_TOKEN = 'github-access';
    
    public const string FACEBOOK_ACCESS_TOKEN = 'facebook-access';
    
    public const string GOOGLE_ACCESS_TOKEN = 'google-access';
    
    public const string LINKEDIN_ACCESS_TOKEN = 'linkedin-access';
    
    public const string MICROSOFTONLINE_ACCESS_TOKEN = 'microsoftonline-access';
    
    private function initializeOauth2IdentityProviderCredentials(
        Facebook $facebook, 
        GitHub $github, 
        Google $google,
        LinkedIn $linkedIn,
        MicrosoftOnline $microsoftOnline
    ) : void {
        /**
         * @see config/common/params.php
         */
        $facebook->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('facebook'));
        $github->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('github'));
        $google->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('google'));
        $linkedIn->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('linkedin'));
        $microsoftOnline->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('microsoftonline'));
        
        $facebook->setClientId($this->sR->getOauth2IdentityProviderClientId('facebook'));
        $github->setClientId($this->sR->getOauth2IdentityProviderClientId('github'));
        $google->setClientId($this->sR->getOauth2IdentityProviderClientId('google'));
        $linkedIn->setClientId($this->sR->getOauth2IdentityProviderClientId('linkedin'));
        $microsoftOnline->setClientId($this->sR->getOauth2IdentityProviderClientId('microsoftonline'));
        
        $facebook->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('facebook'));
        $github->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('github'));
        $google->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('google'));
        $linkedIn->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('linkedin'));
        $microsoftOnline->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('microsoftonline'));
        
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
     * 
     * @param User $user
     * @param TokenRepository $tR
     * @param string $self
     * @return string
     */
    private function getAccessToken(User $user, TokenRepository $tR, string $self) : string
    {
        $identity = $user->getIdentity();
        $identityId = (int)$identity->getId();
        // This records the fact that the user has signed up with a Github 'access-token'
        $token = new Token($identityId, $self);
        // store the token amongst all the other types of tokens e.g. password-rest, email-verification, github-access
        $tR->save($token);
        $tokenString = $token->getToken();
        $timeString = (string)($token->getCreated_at())->getTimestamp();
        // build the token with a timestamp built into it for comparison later
        return $githubAccessToken = null !== $tokenString ? ($tokenString. '_' . $timeString) : '';
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
    
    private function getLinkedInAccessToken(User $user, TokenRepository $tR): string
    {
        return $this->getAccessToken($user, $tR, self::LINKEDIN_ACCESS_TOKEN);
    }
    
    private function getMicrosoftOnlineAccessToken(User $user, TokenRepository $tR): string
    {
        return $this->getAccessToken($user, $tR, self::MICROSOFTONLINE_ACCESS_TOKEN);
    }
}

