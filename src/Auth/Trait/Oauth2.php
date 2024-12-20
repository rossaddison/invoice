<?php

declare(strict_types=1);

namespace App\Auth\Trait;

use Yiisoft\Yii\AuthClient\Client\Facebook;
use Yiisoft\Yii\AuthClient\Client\GitHub;
use Yiisoft\Yii\AuthClient\Client\Google;

trait Oauth2
{
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
}
