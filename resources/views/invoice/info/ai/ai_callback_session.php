<?php

declare(strict_types=1);

?>

<p><b>How can I include https://github.com/rossaddison/yii-auth-client/blob/master/src/StateStorage/SessionStateStorage.php to improve the state management in the `callbackGithub` function?</b></p>

<p>To include <code>SessionStateStorage</code> from the <code>yii-auth-client</code> repository and use it to improve the state management in the <code>callbackGithub</code> function, you should follow these steps:</p>
<ol>
<li><strong>Install <code>yii-auth-client</code> package</strong> if it is not already installed.</li>
<li><strong>Update the <code>AuthController</code> constructor</strong> to inject <code>SessionStateStorage</code>.</li>
<li><strong>Use <code>SessionStateStorage</code> to manage the state</strong> in the <code>callbackGithub</code> function.</li>
</ol>
<p>Here is the updated <code>AuthController</code> class and <code>callbackGithub</code> function:</p>
<p><strong>Step 1: Install the <code>yii-auth-client</code> package</strong></p>
<p>Make sure you have the <code>yii-auth-client</code> package installed. You can install it using Composer:</p>
<pre><code class="lang-bash"><span class="hljs-symbol">composer</span> <span class="hljs-meta">require</span> yiisoft/yii-auth-client
</code></pre>
<p><strong>Step 2: Update the <code>AuthController</code> constructor</strong></p>
<p>Inject <code>SessionStateStorage</code> into the constructor:</p>
<pre><code class="lang-php"><span class="hljs-keyword">use</span> <span class="hljs-title">Yiisoft</span>\<span class="hljs-title">Yii</span>\<span class="hljs-title">AuthClient</span>\<span class="hljs-title">StateStorage</span>\<span class="hljs-title">SessionStateStorage</span>;

<span class="hljs-keyword">final</span> <span class="hljs-class"><span class="hljs-keyword">class</span> <span class="hljs-title">AuthController</span>
</span>{
    <span class="hljs-comment">// Other code...</span>

    <span class="hljs-keyword">private</span> SessionStateStorage $stateStorage;

    <span class="hljs-keyword">public</span> <span class="hljs-function"><span class="hljs-keyword">function</span> <span class="hljs-title">__construct</span><span class="hljs-params">(
        private readonly AuthService $authService,
        private readonly WebControllerService $webService,
        private WebViewRenderer $webViewRenderer,
        private Manager $manager,
        private SessionInterface $session,
        private SettingRepository $sR,
        private Facebook $facebook,
        private GitHub $github,
        private Google $google,
        private LinkedIn $linkedIn,
        private MicrosoftOnline $microsoftOnline,
        private VKontakte $vkontakte,
        private X $x,
        private Yandex $yandex,
        private UrlGenerator $urlGenerator,
        SessionStateStorage $stateStorage
    )</span> </span>{
        <span class="hljs-keyword">$this</span>-&gt;viewRenderer = $webViewRenderer-&gt;withControllerName(<span class="hljs-string">'auth'</span>);
        <span class="hljs-keyword">$this</span>-&gt;manager = $manager;
        <span class="hljs-keyword">$this</span>-&gt;session = $session;
        <span class="hljs-keyword">$this</span>-&gt;sR = $sR;
        <span class="hljs-keyword">$this</span>-&gt;facebook = $facebook;
        <span class="hljs-keyword">$this</span>-&gt;github = $github;
        <span class="hljs-keyword">$this</span>-&gt;google = $google;
        <span class="hljs-keyword">$this</span>-&gt;linkedIn = $linkedIn;
        <span class="hljs-keyword">$this</span>-&gt;microsoftOnline = $microsoftOnline;
        <span class="hljs-keyword">$this</span>-&gt;vkontakte = $vkontakte;
        <span class="hljs-keyword">$this</span>-&gt;x = $x;
        <span class="hljs-keyword">$this</span>-&gt;yandex = $yandex;
        <span class="hljs-keyword">$this</span>-&gt;urlGenerator = $urlGenerator;
        <span class="hljs-keyword">$this</span>-&gt;stateStorage = $stateStorage;

        <span class="hljs-keyword">$this</span>-&gt;initializeOauth2IdentityProviderCredentials(
            $facebook,
            $github,
            $google,
            $linkedIn,
            $microsoftOnline,
            $vkontakte,
            $x,
            $yandex
        );
    }

    <span class="hljs-comment">// Other code...</span>
}
</code></pre>
<p><strong>Step 3: Use <code>SessionStateStorage</code> to manage the state in the <code>callbackGithub</code> function</strong></p>
<p>Update the <code>callbackGithub</code> function to use <code>SessionStateStorage</code> for state management:</p>
<pre><code class="lang-php"><span class="hljs-keyword">public</span> <span class="hljs-function"><span class="hljs-keyword">function</span> <span class="hljs-title">callbackGithub</span><span class="hljs-params">(
    ServerRequestInterface $request,
    TranslatorInterface $translator,
    TokenRepository $tR,
    UserInvRepository $uiR,
    UserRepository $uR,
    #[RouteArgument<span class="hljs-params">(<span class="hljs-string">'_language'</span>)</span>] string $_language,
    #[Query<span class="hljs-params">(<span class="hljs-string">'code'</span>)</span>] string $code = null,
    #[Query<span class="hljs-params">(<span class="hljs-string">'state'</span>)</span>] string $state = null
)</span> : <span class="hljs-title">ResponseInterface</span>
</span>{
    <span class="hljs-keyword">if</span> ($code == <span class="hljs-keyword">null</span> || $state == <span class="hljs-keyword">null</span>) {
        <span class="hljs-keyword">return</span> <span class="hljs-keyword">$this</span>-&gt;redirectToMain();
    }

    $sessionState = <span class="hljs-keyword">$this</span>-&gt;stateStorage-&gt;get(<span class="hljs-string">'oauth2state'</span>);
    <span class="hljs-keyword">if</span> (!$sessionState || $state !== $sessionState) {
        <span class="hljs-comment">// State is invalid, possible cross-site request forgery. Exit with an error code.</span>
        <span class="hljs-keyword">exit</span>(<span class="hljs-number">1</span>);
    }

    <span class="hljs-comment">/**
     * <span class="hljs-doctag">@psalm</span>-suppress DocblockTypeContradiction $code
     */</span>
    <span class="hljs-keyword">if</span> (strlen($code) == <span class="hljs-number">0</span>) {
        <span class="hljs-comment">// Generate state and store in session</span>
        $generatedState = bin2hex(random_bytes(<span class="hljs-number">16</span>));
        <span class="hljs-keyword">$this</span>-&gt;stateStorage-&gt;set(<span class="hljs-string">'oauth2state'</span>, $generatedState);

        <span class="hljs-comment">// If we don't have an authorization code then get one</span>
        $authorizationUrl = <span class="hljs-keyword">$this</span>-&gt;github-&gt;buildAuthUrl($request, [<span class="hljs-string">'state'</span> =&gt; $generatedState]);
        header(<span class="hljs-string">'Location: '</span> . $authorizationUrl);
        <span class="hljs-keyword">exit</span>;
    } <span class="hljs-keyword">elseif</span> ($code == <span class="hljs-number">401</span>) {
        <span class="hljs-keyword">return</span> <span class="hljs-keyword">$this</span>-&gt;redirectToOauth2CallbackResultUnAuthorised();
    }

    <span class="hljs-comment">// Try to get an access token (using the 'authorization code' grant)</span>
    $oAuthTokenType = <span class="hljs-keyword">$this</span>-&gt;github-&gt;fetchAccessToken($request, $code, $params = []);

    <span class="hljs-comment">// Validate the user's identity using the access token</span>
    $userArray = <span class="hljs-keyword">$this</span>-&gt;github-&gt;getCurrentUserJsonArray($oAuthTokenType);
    $githubId = $userArray[<span class="hljs-string">'id'</span>] ?? <span class="hljs-number">0</span>;
    <span class="hljs-keyword">if</span> ($githubId &gt; <span class="hljs-number">0</span>) {
        $githubLogin = <span class="hljs-string">'g'</span>;
        <span class="hljs-keyword">if</span> (strlen($githubLogin) &gt; <span class="hljs-number">0</span>) {
            $login = <span class="hljs-string">'github'</span> . (string)$githubId . $githubLogin;
            $email = $userArray[<span class="hljs-string">'email'</span>] ?? <span class="hljs-string">'noemail'</span> . $login . <span class="hljs-string">'@github.com'</span>;
            $password = Random::string(<span class="hljs-number">32</span>);
            <span class="hljs-keyword">if</span> (<span class="hljs-keyword">$this</span>-&gt;authService-&gt;oauthLogin($login)) {
                <span class="hljs-comment">// Handle existing user login</span>
                $identity = <span class="hljs-keyword">$this</span>-&gt;authService-&gt;getIdentity();
                $userId = $identity-&gt;getId();
                <span class="hljs-keyword">if</span> (<span class="hljs-keyword">null</span> !== $userId) {
                    $userInv = $uiR-&gt;repoUserInvUserIdquery($userId);
                    <span class="hljs-keyword">if</span> (<span class="hljs-keyword">null</span> !== $userInv) {
                        $status = $userInv-&gt;getActive();
                        <span class="hljs-keyword">if</span> ($status || $userId == <span class="hljs-number">1</span>) {
                            $userId == <span class="hljs-number">1</span> ? <span class="hljs-keyword">$this</span>-&gt;disableToken($tR, <span class="hljs-string">'1'</span>, <span class="hljs-keyword">$this</span>-&gt;getTokenType(<span class="hljs-string">'github'</span>)) : <span class="hljs-string">''</span>;
                            <span class="hljs-keyword">return</span> <span class="hljs-keyword">$this</span>-&gt;redirectToInvoiceIndex();
                        } <span class="hljs-keyword">else</span> {
                            <span class="hljs-keyword">$this</span>-&gt;disableToken($tR, $userId, <span class="hljs-keyword">$this</span>-&gt;getTokenType(<span class="hljs-string">'github'</span>));
                            <span class="hljs-keyword">return</span> <span class="hljs-keyword">$this</span>-&gt;redirectToAdminMustMakeActive();
                        }
                    }
                }
                <span class="hljs-keyword">return</span> <span class="hljs-keyword">$this</span>-&gt;redirectToMain();
            } <span class="hljs-keyword">else</span> {
                <span class="hljs-comment">// Handle new user registration</span>
                $user = <span class="hljs-keyword">new</span> User($login, $email, $password);
                $uR-&gt;save($user);
                $userId = $user-&gt;getId();
                <span class="hljs-keyword">if</span> ($userId &gt; <span class="hljs-number">0</span>) {
                    <span class="hljs-keyword">if</span> ($uR-&gt;repoCount() == <span class="hljs-number">1</span>) {
                        <span class="hljs-keyword">$this</span>-&gt;manager-&gt;revokeAll($userId);
                        <span class="hljs-keyword">$this</span>-&gt;manager-&gt;assign(<span class="hljs-string">'admin'</span>, $userId);
                    } <span class="hljs-keyword">else</span> {
                        <span class="hljs-keyword">$this</span>-&gt;manager-&gt;revokeAll($userId);
                        <span class="hljs-keyword">$this</span>-&gt;manager-&gt;assign(<span class="hljs-string">'observer'</span>, $userId);
                    }
                    $login = $user-&gt;getLogin();
                    $languageArray = <span class="hljs-keyword">$this</span>-&gt;sR-&gt;locale_language_array();
                    $language = $languageArray[$_language];
                    $randomAndTimeToken = <span class="hljs-keyword">$this</span>-&gt;getGithubAccessToken($user, $tR);
                    $proceedToMenuButton = <span class="hljs-keyword">$this</span>-&gt;proceedToMenuButtonWithMaskedRandomAndTimeTokenLink(
                        $translator,
                        $user,
                        $uiR,
                        $language,
                        $_language,
                        $randomAndTimeToken,
                        <span class="hljs-string">'github'</span>
                    );
                    <span class="hljs-keyword">return</span> <span class="hljs-keyword">$this</span>-&gt;viewRenderer-&gt;render(<span class="hljs-string">'proceed'</span>, [
                        <span class="hljs-string">'proceedToMenuButton'</span> =&gt; $proceedToMenuButton
                    ]);
                }
            }
        }
    }
    <span class="hljs-keyword">$this</span>-&gt;authService-&gt;logout();
    <span class="hljs-keyword">return</span> <span class="hljs-keyword">$this</span>-&gt;redirectToMain();
}
</code></pre>
<h3 id="summary-of-changes-">Summary of Changes:</h3>
<ol>
<li><strong>Injected <code>SessionStateStorage</code></strong> into the <code>AuthController</code> constructor.</li>
<li><strong>Used <code>SessionStateStorage</code></strong> to store and retrieve the OAuth2 state.</li>
<li><strong>Updated the <code>callbackGithub</code> function</strong> to use <code>SessionStateStorage</code> for state management, ensuring the state is correctly validated to prevent CSRF attacks.</li>
</ol>