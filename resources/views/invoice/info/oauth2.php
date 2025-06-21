<?php

declare(strict_types=1);

?>

<p><b>How do I add an OAuth2 Identity Provider e.g. LinkedIn or Facebook?</b></p>

<p><b>Purpose</b></p>
<p>User allows their Identity Provider to provide authentication to our site, so they do not have to submit their password into our site.</p>

<p><b>Steps</b></p>

<p><b>1. Ajust the root's .env file to accomodate your Identity Provider's Client Id, Client Secret, and returnUrl.</b></p>

<p><b>2. Adjust the config/common/params.php</b></p>
<pre>
'yiisoft/yii-auth-client' => [
      'enabled' => true,
      'clients' => [          
          'facebook' => [
              'class' => 'Yiisoft\Yii\AuthClient\Client\Facebook::class',
              'clientId' => $_ENV['FACEBOOK_API_CLIENT_ID'] ?? '', 
              'clientSecret' => $_ENV['FACEBOOK_API_CLIENT_SECRET'] ?? '',
              'returnUrl' => $_ENV['FACEBOOK_API_CLIENT_RETURN_URL'] ?? ''
          ],
          'github' => [
              'class' => 'Yiisoft\Yii\AuthClient\Client\Github::class',
              'clientId' => $_ENV['GITHUB_API_CLIENT_ID'] ?? '',
              'clientSecret' => $_ENV['GITHUB_API_CLIENT_SECRET'] ?? '',
              'returnUrl' => $_ENV['GITHUB_API_CLIENT_RETURN_URL'] ?? ''
           ],
          'google' => [
              'class' => 'Yiisoft\Yii\AuthClient\Client\Google::class',
              'clientId' => $_ENV['GOOGLE_PEOPLE_API_V1_CLIENT_ID'] ?? '',
              'clientSecret' => $_ENV['GOOGLE_PEOPLE_API_V1_CLIENT_SECRET'] ?? '',
              'returnUrl' => $_ENV['GOOGLE_PEOPLE_API_VI_CLIENT_RETURN_URL'] ?? ''  
          ],
          'linkedin' => [
              'class' => 'Yiisoft\Yii\AuthClient\Client\LinkedIn::class',
              'clientId' => $_ENV['LINKEDIN_API_CLIENT_ID'] ?? '',
              'clientSecret' => $_ENV['LINKEDIN_API_CLIENT_SECRET'] ?? '',
              'returnUrl' => $_ENV['LINKEDIN_API_CLIENT_RETURN_URL'] ?? ''  
          ],
     ],   
],   
</pre>
</p>
<p><b>3. Adjust src\Auth\Controller\AuthController.php so that the button can be enabled or disabled.</b></p>
<p>
    <pre>
    $noGithubContinueButton = $this->sR->getSetting('no_github_continue_button') == '1' ? true : false;
    $noGoogleContinueButton = $this->sR->getSetting('no_google_continue_button') == '1' ? true : false;
    $noFacebookContinueButton = $this->sR->getSetting('no_facebook_continue_button') == '1' ? true : false;
    $noLinkedInContinueButton = $this->sR->getSetting('no_linkedin_continue_button') == '1' ? true : false;
    return $this->viewRenderer->render('login', 
        [
            'formModel' => $loginForm,
            'facebookAuthUrl' => strlen($this->facebook->getClientId()) > 0 ? $this->facebook->buildAuthUrl($request, $params = []) : '',
            'githubAuthUrl' => strlen($this->github->getClientId()) > 0 ? $this->github->buildAuthUrl($request, $params = []) : '',   
            'googleAuthUrl' => strlen($this->google->getClientId()) > 0 ? $this->google->buildAuthUrl($request, $params = []) : '',
            'linkedInAuthUrl' => strlen($this->linkedIn->getClientId()) > 0 ? $this->linkedIn->buildAuthUrl($request, $params = []) : '',
            'noGithubContinueButton' => $noGithubContinueButton,
            'noGoogleContinueButton' => $noGoogleContinueButton,
            'noFacebookContinueButton' => $noFacebookContinueButton,
            'noLinkedInContinueButton' => $noLinkedInContinueButton
        ]);
    </pre>
</p>
<p><b>4. Adjust src\Auth\Controller\SignupController.php in a similar way.</b></p>
<p><b>5. Adjust src\Auth\Trait\Oauth2.php</b></p>
<p><b>6. Adjust resources\views\auth\login.php</b></p>
<p><b>7. Adjust resources\views\signup\signup.php</b></p>
<p><b>8. Modify the resources\messages\en\app.php and setup your own language.</b></p>
<p><b>9. Include a checkbox at resources\views\invoice\setting\views\partial_settings_oauth2.php</b></p>
<p><b>10. Include a setting at src\Invoice\InvoiceController.php e.g. 'no_linkedin_continue_button' => 1,</b></p>
<p><b>11. Add a button at src\Widget\Button.php</b></p>
<p>
<pre>
    public function linkedin(string $linkedInAuthUrl) : string {       
        return A::tag()
        ->addClass('btn btn-info bi bi-linkedin')
        ->content(' '.$this->translator->translate('continue.with.linkedin'))
        ->href($linkedInAuthUrl)
        ->id('btn-linkedin')
        ->render();
    }
</pre>
</p>