<?php

declare(strict_types=1);

namespace App\Auth\Trait;

use App\Auth\Token;
use App\Auth\TokenRepository;
use App\User\User;
use Yiisoft\Yii\AuthClient\Widget\AuthChoice;

trait Oauth2
{
    public const string DEVELOPER_SANDBOX_HMRC_ACCESS_TOKEN = 'developersandboxhmrc-access';

    public const string GITHUB_ACCESS_TOKEN = 'github-access';

    public const string FACEBOOK_ACCESS_TOKEN = 'facebook-access';

    public const string GOOGLE_ACCESS_TOKEN = 'google-access';

    public const string GOVUK_ACCESS_TOKEN = 'govuk-access';

    public const string LINKEDIN_ACCESS_TOKEN = 'linkedin-access';

    public const string MICROSOFTONLINE_ACCESS_TOKEN = 'microsoftonline-access';

    public const string OPENBANKING_ACCESS_TOKEN = 'openbanking-access';

    public const string OIDC_ACCESS_TOKEN = 'oidc-access';

    public const string VKONTAKTE_ACCESS_TOKEN = 'vkontakte-access';

    public const string X_ACCESS_TOKEN = 'x-access';

    public const string YANDEX_ACCESS_TOKEN = 'yandex-access';

    private function initializeOauth2IdentityProviderCredentials(): void
    {
        /**
         * Related logic: see config/common/params.php
         * No need to instantiate AuthChoice. It has been dependency injected
         * and generates a button on the auth/login view
         * at config/web/yii-auth-client
         *
         * Related logic: see https://entra.microsoft.com/#view/Microsoft_AAD_IAM/TenantOverview.ReactView
         * Rebuild the authUrl and tokenUrl to include the tenant (default: 'common') which can be
         * 'common', 'organisation', 'consumers', or ID. ID is used here.
         * The tenant can be acquired from Microsoft Entra Admin Centre ... Identity Overview ... Tenant
         * and is inserted into the root's .env file.
         */

        /** @psalm-var \Yiisoft\Yii\AuthClient\Client\MicrosoftOnline $microsoftOnline */
        $microsoftOnline = (AuthChoice::widget())->getClient('microsoftonline');

        $authUrl = $microsoftOnline->getAuthUrlWithTenantInserted($microsoftOnline->getTenant());

        $microsoftOnline->setAuthUrl($authUrl);

        $tokenUrl = $microsoftOnline->getTokenUrlWithTenantInserted($microsoftOnline->getTenant());
        $microsoftOnline->setTokenUrl($tokenUrl);
    }

    private function initializeOauth2IdentityProviderDualUrls(): void
    {
        if ($this->sR->getEnv() == 'dev') {

            $authChoice = AuthChoice::widget();
            $developerSandboxHmrc = $authChoice->getClient('developersandboxhmrc');
            /** @psalm-var \App\Auth\Client\DeveloperSandboxHmrc $developerSandboxHmrc */
            $developerSandboxHmrc->setEnvironment('dev');
        } else {
            /** @psalm-var \App\Auth\Client\DeveloperSandboxHmrc $developerSandboxHmrc */
            $developerSandboxHmrc->setEnvironment('prod');
        }
    }

    private function selectedIdentityProviders(string $codeChallenge): array
    {
        $noDeveloperSandboxHmrcContinueButton = $this->sR->getSetting('no_developer_sandbox_hmrc_continue_button') == '1' ? true : false;
        $noGithubContinueButton = $this->sR->getSetting('no_github_continue_button') == '1' ? true : false;
        $noGoogleContinueButton = $this->sR->getSetting('no_google_continue_button') == '1' ? true : false;
        $noGovUkContinueButton = $this->sR->getSetting('no_govuk_continue_button') == '1' ? true : false;
        $noFacebookContinueButton = $this->sR->getSetting('no_facebook_continue_button') == '1' ? true : false;
        $noLinkedInContinueButton = $this->sR->getSetting('no_linkedin_continue_button') == '1' ? true : false;
        $noMicrosoftOnlineContinueButton = $this->sR->getSetting('no_microsoftonline_continue_button') == '1' ? true : false;
        $noOidcContinueButton = $this->sR->getSetting('no_openidconnect_continue_button') == '1' ? true : false;
        $noVKontakteContinueButton = $this->sR->getSetting('no_vkontakte_continue_button') == '1' ? true : false;
        $noXContinueButton = $this->sR->getSetting('no_x_continue_button') == '1' ? true : false;
        $noYandexContinueButton = $this->sR->getSetting('no_yandex_continue_button') == '1' ? true : false;
        return $providers = [
            'developersandboxhmrc' => [
                'noflag' => $noDeveloperSandboxHmrcContinueButton,
                'params' => [
                    'code_challenge' => $codeChallenge,
                    'code_challenge_method' => 'S256',
                ],
                'buttonName' => $this->translator->translate('continue.with.developersandboxhmrc'),
            ],
            'facebook' => [
                'noflag' => $noFacebookContinueButton,
                'params' => [],
                'buttonName' => $this->translator->translate('continue.with.facebook'),
            ],
            'github' => [
                'noflag' => $noGithubContinueButton,
                'params' => [],
                'buttonName' => $this->translator->translate('continue.with.github'),
            ],
            'google' => [
                'noflag' => $noGoogleContinueButton,
                'params' => [],
                'buttonName' => $this->translator->translate('continue.with.google'),
            ],
            'govuk' => [
                'noflag' => $noGovUkContinueButton,
                'params' => [
                    'return_type' => 'id_token',
                    'code_challenge' => $codeChallenge,
                    'code_challenge_method' => 'S256',
                ],
                'buttonName' => $this->translator->translate('continue.with.govuk'),
            ],
            'linkedin' => [
                'noflag' => $noLinkedInContinueButton,
                'params' => [],
                'buttonName' => $this->translator->translate('continue.with.linkedin'),
            ],
            'microsoftonline' => [
                'noflag' => $noMicrosoftOnlineContinueButton,
                'params' => [],
                'buttonName' => $this->translator->translate('continue.with.microsoftonline'),
            ],
            'oidc' => [
                'noflag' => $noOidcContinueButton,
                'params' => [],
                'buttonName' => $this->translator->translate('continue.with.oidc'),
            ],
            'vkontakte' => [
                'noflag' => $noVKontakteContinueButton,
                'params' => [
                    'code_challenge' => $codeChallenge,
                    'code_challenge_method' => 'S256',
                ],
                'buttonName' => $this->translator->translate('continue.with.vkontakte'),
            ],
            'x' => [
                'noflag' => $noXContinueButton,
                'params' => [
                    'code_challenge' => $codeChallenge,
                    'code_challenge_method' => 'S256',
                ],
                'buttonName' => $this->translator->translate('continue.with.x'),
            ],
            'yandex' => [
                'noflag' => $noYandexContinueButton,
                'params' => [
                    'code_challenge' => $codeChallenge,
                    'code_challenge_method' => 'S256',
                ],
                'buttonName' => $this->translator->translate('continue.with.yandex'),
            ],
        ];
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
        $identityId = (int) $identity->getId();
        // This records the fact that the user has signed up with e.g. a Github 'access-token'
        $token = new Token($identityId, $self);
        // store the token amongst all the other types of tokens e.g. password-rest, email-verification, github-access
        $tR->save($token);
        $tokenString = $token->getToken();
        $timeString = (string) $token->getCreated_at()->getTimestamp();
        // build the token with a timestamp built into it for comparison later
        return null !== $tokenString ? ($tokenString . '_' . $timeString) : '';
    }
}
