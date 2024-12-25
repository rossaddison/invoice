<?php

declare(strict_types=1);

namespace App\Auth\Trait;

use App\Auth\Token;
use App\Auth\TokenRepository;
use App\User\User;
use Yiisoft\Yii\AuthClient\Client\Facebook;
use Yiisoft\Yii\AuthClient\Client\GitHub;
use Yiisoft\Yii\AuthClient\Client\Google;

trait Oauth2
{
    public const string GITHUB_ACCESS_TOKEN = 'github-access';
    
    private function initializeOauth2IdentityProviderCredentials(Facebook $facebook, GitHub $github, Google $google) : void {
        $facebook->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('facebook'));
        $github->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('github'));
        $google->setOauth2ReturnUrl($this->sR->getOauth2IdentityProviderReturnUrl('google'));
        
        $facebook->setClientId($this->sR->getOauth2IdentityProviderClientId('facebook'));
        $github->setClientId($this->sR->getOauth2IdentityProviderClientId('github'));
        $google->setClientId($this->sR->getOauth2IdentityProviderClientId('google'));
        
        $facebook->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('facebook'));
        $github->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('github'));
        $google->setClientSecret($this->sR->getOauth2IdentityProviderClientSecret('google'));
    } 
        
    /**
     * @param User $user
     * @param TokenRepository $tR
     * @return string
     */
    private function getGithubAccessToken(User $user, TokenRepository $tR): string
    {
        $identity = $user->getIdentity();
        $identityId = (int)$identity->getId();
        // This records the fact that the user has signed up with a Github 'access-token'
        $token = new Token($identityId, self::GITHUB_ACCESS_TOKEN);
        // store the token amongst all the other types of tokens e.g. password-rest, email-verification, github-access
        $tR->save($token);
        $tokenString = $token->getToken();
        $timeString = (string)($token->getCreated_at())->getTimestamp();
        // build the token with a timestamp built into it for comparison later
        return $githubVerificationToken = null !== $tokenString ? ($tokenString. '_' . $timeString) : '';
    }
}
