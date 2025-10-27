<h6 id="invoice">Development Summary: (...resources/views/invoice/info/invoice.php)</h6>

<p><b>Aim: To develop a similar invoicing system to InvoicePlane with <s>integrating with the latest Jquery, and</s> the security features of Yii3, Snyk, CodeQl using wampserver 
        as a test platform for WAMP and also lightweight Alpine / heavier Ubuntu 22.04 LTS for LAMP.</b>
</p>
<p>Less emphasis has been placed on using javascript due to security vulnerabilities with jQuery. More emphasis is being placed on using php forms instead of javascript. </p> 
<p>Free sponsorship has been provided by vultr.com in Frankfurt on yii3i.online for testing.<p>
<p><b>To Do's - Longer Term Goals</b></p>
<p>1. <s>Integration of Payment Gateways</s>PCI Compliant Stripe, Amazon Pay, Braintree, and Mollie have been introduced.</p>
<p>2. Accountant Role with the ability of an accountant/bookkeeper to record payments against invoices.</p>
<p><s>3. Include a <b>Company Private Detail</b> specific logo on a pdf invoice.</s></p>
<p>4. Acceptance Tests for Invoice</p>
<p>5. Further validation and exceptions to be included in testing of e-Invoicing using PeppolHelper.</p>
<p><s>6. Filters to be introduced on grids</s></p>
<p><s>7. Improve Generator index template using Yiisoft functions.</s></p>
<p><s>8. Include Product images</s></p>
<p><s>9. Remove all $s variables passed to views since the variable is defined under config/params.php</s></p>
<p>10. Upskilling on Docker. </p>
<br>
<code>
'yiisoft/view' => [
    'basePath' => '@views',
    'parameters' => [
      'assetManager' => Reference::to(AssetManager::class),
      'urlGenerator' => Reference::to(UrlGeneratorInterface::class),
      'currentRoute' => Reference::to(CurrentRoute::class),
      'translator' => Reference::to(TranslatorInterface::class),
      // yii-invoice - Below parameters are specifically used in views/layout/invoice
      's' => Reference::to(SettingRepository::class),
      'session' => Reference::to(SessionInterface::class),
      'datehelper' => Reference::to(DateHelper::class),
      'pageSizeLimiter' => Reference::to(PageSizeLimiter::class),
      'gridComponents' => Reference::to(GridComponents::class)  
    ],
  ],      
</code>    
</p>    
<p><s>Pdf template construction upon emailing.</s></p>
<p><s>Work on info issues</s></p>
<p>Work In Progress - Shorter Term Goals</p>
<p><s>Psalm Level 2 - Testing</s></p>
<p><s>Dead Code Removal with Psalm 3 Testing</s></p>
<p><s>Language array generator using Google Translate</s></p>
<p><s>All invoices with dates falling within CompanyPrivate start and end dates will have the specific logo uploaded</s></p>
<p><s>for this CompanyPrivate record attached to these invoices.</s></p>
<p><s>CompanyPrivate logo will be automatically input on invoice/quotes depending on whether the date of the invoice falls between the start and end date.</s></p>
<p>Introducing Paypal.</p>
<p>Introducing India's PayTm payment gateway's QR code method of payment and comparing this with Stripe's method.</p>
<p>A General Sales Tax (GST) Tax System will have to be implemented first for this purpose.</p>
<p>Testing Credit Notes against Invoices with refunds (if payment made) linked to each of the payment gateways.</p>
<p>Retest signing up procedure because middleware authentication class moved into group header</p>
<p>Payment gateway testing on alpine</p>
<p>Callback traits i.e. C:\wamp128\www\invoice\src\Auth\Trait\Callback.php still to be tested</p>
<p><b>26h October 2025</b></p>
<p>Bugfix: Length annotation: include skipOnEmpty = true</p>
<p>Bugfix: Delete invoice items. src\Invoice\Asset\rebuild\js\inv.js</p> 
<p>Use vs code to increase test coverage from 18.3 to approx. 40%</p>
<p>New invoices, and quotes redirect to the new invoice and quote respectively and not the index.</p>
<p>Inv to inv copy redirects to the new invoice's view</p>
<p><b>25th October 2025</b></p>
<p>I am now using VS Code and integrating it with SonarQube Cloud using the Connection Method</p>
<p>This is to bring code replication down</p>
<p>A FormFields widget has been created for code reduction. Each function is tailored to specific entity needs.
<p>Next: Custom Fields: Move basic functionality to the BaseController so that it code can also be reduced and try and implement some sort of validation on the custom fields. </p>
<p>Also remove the 'NULL' appearing in the custom field.</p>
<p><b>24th October 2025</b></p>
<p>jQuery Dependency removed completely via remove_jquery branch</p>
<p>pre_jquery_deletion branch created</p>
<p>Custom Fields Views Improved - Multiple Selection(a.k.a Choice) dropdown selected items now displayed in single Text field.</p>
<p><table style="border:2px solid #000; border-collapse:collapse; width:100%;" cellpadding="6" cellspacing="0">
  <thead>
    <tr>
      <th style="border:1px solid #000; padding:6px; text-align:left;">Reason</th>
      <th style="border:1px solid #000; padding:6px; text-align:left;">Explanation</th>
      <th style="border:1px solid #000; padding:6px; text-align:left;">Effect on the App</th>
      <th style="border:1px solid #000; padding:6px; text-align:left;">What Changed</th>
      <th style="border:1px solid #000; padding:6px; text-align:left;">Recommended Action</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Bundle size &amp; load performance</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">jQuery adds a large, global dependency which increases page payload and slows initial load.</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Smaller JS bundles and faster first paint after removal.</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Replaced jQuery usage with small vanilla JS helpers and modern APIs (fetch, Element.closest, URLSearchParams).</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Keep utilities small and shared (one ajax helper), and use code-splitting where appropriate.</td>
    </tr>
    <tr>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Modern browser APIs available</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Native DOM, fetch, classList, dataset, and other APIs cover most needs previously solved by jQuery.</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Code can be more direct and often faster (less indirection).</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Rewrote event delegation, AJAX, and DOM manipulation to use standards-based APIs.</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Prefer native APIs; polyfill only when supporting older browsers that require it.</td>
    </tr>
    <tr>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Maintainability &amp; readability</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Vanilla JS avoids mixed paradigms and reduces cognitive load for new developers not familiar with jQuery.</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Clearer code paths and fewer runtime surprises from global overrides.</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Normalized selectors and helpers (parsedata, getJson) to centralize behavior.</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Document common helpers and keep them in a single shared file to avoid duplication.</td>
    </tr>
    <tr>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Security &amp; attack surface</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Smaller, more focused code reduces the attack surface and the chance of inadvertently importing insecure plugins.</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Lower risk from third-party jQuery plugins or misused APIs.</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Removed global jQuery plugin calls; used explicit DOM APIs and safe JSON parsing.</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Review any remaining third-party scripts for CSP/nonce compatibility and sanitize dynamic data server-side.</td>
    </tr>
    <tr>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Interference &amp; selector bugs</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Global id/class reuse and jQuery-driven handlers previously caused accidental cross-form submits and selector collisions.</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Fixes for bugs where one handler submitted the wrong form (CSRF issues, logout redirects).</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Removed shared element IDs (e.g. btn-submit) and moved to class-based delegation and closest(form) logic.</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Audit templates to avoid duplicate IDs, prefer classes, and ensure CSRF inputs live inside the form they protect.</td>
    </tr>
    <tr>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Testing &amp; automation</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Vanilla JS is easier to test in headless browser environments and reduces reliance on heavy DOM shims.</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Simpler unit and integration tests; fewer fakes/mocks for jQuery.</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Event handlers are attached via addEventListener and are deterministic for tests.</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Add unit tests for shared helpers (getJson, parsedata) and integration tests for form submit flows.</td>
    </tr>
    <tr>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Progressive enhancement</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Where appropriate, native controls (e.g., type="submit", form="...") are preferred so basic functionality works without JS.</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Better resilience (forms still submit if JS fails or is blocked).</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Changed backSave controls to native submit behavior or use form attribute instead of anchors/buttons that require JS.</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Audit UI components so they degrade gracefully and keep server-side fallbacks intact.</td>
    </tr>
    <tr>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Migration effort and consistency</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Replacing jQuery required careful conversion of many small behaviors (event delegation, serialization of arrays, AJAX semantics).</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Short-term risk: missing handlers or serialization differences (e.g., keylist[] vs keylist) caused bugs.</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Introduced a small shared ajax helper and consistent array serialization to match server expectations.</td>
      <td style="border:1px solid #000; padding:6px; vertical-align:top;">Create a migration checklist, run QA on all pages that previously used jQuery, and centralize helpers to avoid regressions.</td>
    </tr>
  </tbody>
</table></p>
<p>The custom fields positions array has been moved to the SettingRepository</p>
<p>Next: DOM error reduction</p>
<p><b>16th October 2025</b></p>
<p>Bugfix: Front pages ... Settings ... View ... Front Page</p>
<p><b>24th September 2025</b></p>
<p>Dependency Injector yii-auth-client.php created under config/web/di</p>
<p>AuthChoice integrated into AuthController</p>
<p>Instead of AuthChoice absoluteButtons function on auth/login</p>
<p><b>3rd September 2025</b></p>
<p>After installing google-gemini/gemini-cli globally with <code>npm install -g @google/gemini-cli</code>, created Readme's using</p>
<p>Google Gemini from the command line and creation of ge_min_i.bat for inter alia analysing code, creating documentation, summaries in this repo.</p>
<p>Bugfixes for PDF and product sorting/filtering, improvements to index screens, and enhancements to Peppol invoice handling.</p>
<p>Codeception and phpunit test upgrades, plus improvements to developer scripts (e.g., <code>m.bat</code>).</p>
<p><b>17th August 2025</b></p>
<p>Peppol arrays have been updated to the 24th November 2024 arrays. It has been over 2 years since this has been done. 
   The $vat field for InvAllowanceCharge and InvItemAllowanceCharge have been adjusted to $vatOrTax so that non-vat operating
   companies can include a gst tax or a tax similar to vat when working with handling, packing, and shipping fees. 
   I have included a field called Packhandleship_tax and packhandleship_total in the InvAmount entity which will reflect
   the overall value of invoice allowances and charges when using the categories under Peppol. These categories are useful
   in a non-peppol context and particularly a non-vat context hence the more flexible approach and renaming of $vat to 
   $vat_or_tax. The InvAmount entity has been adjusted to accomodate these two new totals which really are a net 
   invallowancecharge field ... so not strictly just packing, handling, and shipping. The InvAllowanceCharge index
   has been upgraded to allow indexing and sorting. </p>

<p><b>Step 1:</b> Peppol ... Add Parent Invoice Allowance and Charges</p>
<p><b>Step 2:</b> Under Options ... Add Overall Invoice Allowance and Charges</p>
<p><b>Step 3:</b> Use the + sign on individual line items under the invoice view to input allowances or charges.</p>

Refresh the view screen to see updated totals so allow redirects to complete once Inv Allowance Charges are entered.
The NumberHelper works independently of screen refreshes and redirects. 
 
Next: Include some Codeception Tests which will secure these concepts if bugs creep in later with 'failure' notifications output in the cli.</p>
<p><b>3rd August 2025</b></p>
<p>Replaced int with float since amount being sent via Wonderful was being truncated. e.g. £3.60 truncated to £3.00</p>
<p>The return url is functional but ref was being truncated because the route did not have it so included ref in the route.</p>  
<p>A more general message is preferred on the completion page for Wonderful which is compiled in the PaymentInformationController function wonderful_complete.</p>
<p>Next: Refund a Credit Note if Payment has been made by the customer via Wonderful</p>
<p>Next: Include an Oauth2.0 payment provider e.g. Tink in the Open Banking selection process. Tink uses OIDC so this will be an interesting integration.</p>
<p>Current Payment Providers: ...src\Invoice\Setting\Trait\OpenBankProviders.php</p>
<p><b>4th August 2025</b></p>
<p>TFA should be run with Oauth so the callbacks in src/Auth/Trait/Callback.php now include TFA if chosen.</p>
<p><b>1st August 2025</b></p>
<p>Bugfix: Adjusting CompanyLogo width: required changing the logo as well.</p>
<p>Open Banking Wonderful api is functional</p>
<p><strong>Steps to using Wonderful api</strong></p>
<p><b>Step 1:</b> Settings ... View ... Online Payment ... Enable Online Payments ... Add a Payment Provider ... Online Banking</p>
<p><b>Step 2:</b> Goto wonderful.co.uk and get your authToken a.k.a apiToken</p>
<p><b>Step 3:</b> Fill in and enable the OpenBanking Form on the same page.</p>
<p><b>Step 4:</b> If you are not using Wonderful as a third party provider, leave the apiToken blank.</p>
<p><img src="/img/wonderful.jpg" height="300" width="450"/></p>
<p><b>29th July 2025</b></p>
<p>Payment Gateways: AmazonPay, Braintree, Stripe, Mollie tested</p>
<p><b>27th July 2025</b></p>
<p>Open Banking Wonderful Preliminary code created.</p>
<p>Increased the rate-limit from 2 to 5 in config\web\di\rate-limit.php because it was affecting codeception tests</p>
<p>Removed Omnipay since it is not PCI compliant and not being maintained sufficiently.</p>
<p><b>25th July 2025</b></p>
<p>Installation Testing</p>
<p><b>Step 1:</b> Create a database in phpMyAdmin using default settings - yii3_i</p>
<p><b>Step 2:</b> Adjust .env file's BUILD_DATABASE=true</p>
<p><b>Step 3:</b> Select your public/index.php url link .. let it run until see main screen</p>
<p><b>Step 4:</b> Adjust .env file's BUILD_DATABASE=</p>
<p><b>Step 5:</b> Signup admin</p>
<p><b>Step 6:</b> Signup observer</p>
<p><b>Step 7:</b> Login as admin and assign a client to observer ... Invoice User Accounts</p>
<p><b>19th July 2025</b></p>
<p>Replace styleci.yml with cs.yml</p>
<p>Purpose: The cs.yml has been restructured to complete a dry-run, then create a pull request if changes are needed ... similar to styleci.yml</p>
<p>PHP CS Fixer GitHub Actions workflow Adjusted</p>
<p><b>18th July 2025</b></p>
<p>Include a new function opCacheHealthCheck() in App\Widget\PerformanceMetrics</p>
<p>Used in resources/views/layout/invoice.php to identify poor php.ini setups</p>
<p>Wonderful - Open Banking removed from Oauth2.0. An authToken is required. Not an authUrl.
<p>New function: getOpenBankingProvidersWithAuthUrl used to differentiate between TPP offering Oauth2.0 and those only providing it through a token.</p>
<p>Incorporating Open Banking into PaymentInformationController</p>
<p><b>15th July 2025</b></p>
<p>An environment branch has been created and is passing all tests but is degraded in terms of performance.</p>
<p>Improve ChangePasswordForm and ResetPasswordForm</p>
<p>Include php 8.4 in the invoice_build</p>
<p><b>12th July 2025</b></p>
<p>Introduce Open Banking to the AuthController and SignupController</p>
<p>https://github.com/rossaddison/invoice/discussions/288</p>
<p><b>10th July 2025</b></p>
<p>Create separate service for AmazonPay</p>
<p>Create di\payment-service.php to increase performance</p>
<p>Copilot created a pull-request for Braintree</p>
<p>Request closed due to Copilot request limit reached</p>
<p>Statically analysed Braintree related data submitted</p>
<p><b>9th July 2025</b></p>
<p>Create separate service for Stripe.</p>
<p>Performance boost: DI's created: inv.php and quote.php</p>
<p>Move Stripe related PaymentInformationController code into a separate service for Stripe.</p>
<p>This is to facilitate future unit testing.</p>
<p>Similar work will commence on the other Payment Providers.</p>
<p><b>8th July 2025</b></p>
<p>Created an additional Group called Credit Note in the InvoiceController</p>
<p>Credit Notes can be generated if the original invoice has been sent.</p>
<p>The Credit Note is simply a 'reversed sent Invoice'.</p>
<p>Bugfix: When a Credit Note is created from an Invoice, this Invoice's creditinvoiceparent_id is set to the new Credit Notes id (effectively an invoice id)</p>
<p>I have not created a separate Credit Note entity</p>
<p>Payment methods can be made active. Only active payment methods should appear in the dropdowns</p>
<p>Set the default payment method to online as dealing with cheques or cash complicates matters.</p>
<p><b>7th July 2025</b></p>
<p>Add more appropriate error messages to the AuthController.php</p>
<p><b>5th July 2025</b></p>
<p>1. routes.php - A non-middleware ratelimiter is applied within the functions verifySetup and verifyLogin.</p>
<p>2. config\web\di\rate-limit reduced to 2 for strictness and security.</p>
<p>3. The expression 'rate limit exceeded' is replaced with 'rate limit reached'.</p>
<p>4. Apply recommendations from https://github.com/rossaddison/invoice/pull/273</p>
<p><b>4th July 2025</b></p>
<p>Recovery Code Entity and Service supports the user.</p>
<p>Option of entering the 8 digit recovery code on the verify login screen only i.e. not during setup.</p>
<p>Fade out 40 seconds script on auth login. Ai generated scripts converted to php.</p>
<p>Fade-out effect using php implemented</p>
<p>Url's no longer carrying annoying trailing hash. src/Service/WebControllerService Bugfix: ?string = null and not string = ''</p>
<p><b>28th June 2025</b></p>
<p>Installation testing</p>
<p>The invoice autoload.php function has been adjusted to check for composer. This simplifies the InstallCommand run with Symfony</p>
<p>The m.bat has been updated to include a submenu for installation</p>
<p>The installation submenu includes options Post Composer Install with Symfony, Pre Composer Install, and a yiipath existance check mainly for Unix and runs independently as install.bat.</p>
<p><b>27th June 2025</b></p>
<p>Adjusting error handling for PHP 8.4</a></p>
<p><b>26th June 2025</b></p>
<p>Applied rector and included 2 rector options in m.bat</p>
<p>Removed files no longer needed for Google Translate e.g. ip_lang.php</p>
<p>Use option 5c with caution i.e. all code is subject to change. First apply 5b i.e. process --dry-run.</p>
<p><b>24th June 2025</b></p>
<p>Restore Sentry using vcs rossaddison/yii-sentry.</p>
<p>Next Signup for performance testing with Sentry.</p>
<p><b>21st June 2025</b></p>
<p>Scripts for filtering used translation keys included in separate folder in root</a></p>
<p>Include hash in WebControllerService function getRedirectResponse</p>
<p>Remove underscores in translation keys and replace with dots</p>
<p>resources/messages/en/app.php has simplified and sorted keys</p>
<p>en/app.php keys replaced old underscore keys in src and resources folders</p> 
<p>Scripts produced by Copilot stored in scripts folder</p>
<p>After a locale is selected from Google Translate dropdown, and app.php produced, redirection to this dropdown occurs using hash</p>
<p><b>18th June 2025</b></p>
<p>Transfer code e.g. new Flash($session) into a di container under config/common/params/di/flash.php. 53 Controllers affected after Autowiring discussion.</p>
<p><b>15th June 2025</b></p>
<p>Script for filtering used translation keys stored in root</p>
<p><b>14th June 2025</b></p>
<p>uopz has been removed from composer.json for compatibility with docker.</p>
<p>Docker php requirement reduced to 8.3</p>
<p>Setting up a repository on Docker</p>
<p><h5>How to Connect 'Docker on Linux' running in Docker Desktop to WampServer phpMyAdmin Database</h5></p>
<p><ol>
    <li class="step">
      <strong>Make WampServer MySQL Accessible from the Network</strong>
      <ul>
        <li>In your WampServer’s MySQL config file (<code>C:\wamp\bin\mysql\mysql8.3.0\my.ini</code>), set:
            <pre><p>[mysqld]</p>
                 <p>bind-address=0.0.0.0</p></pre>
        </li>
        <li>Adjust config/common/params.php docker $dbHost to a suitable local IPV4 address ... c:\>ipconfig</li>
        <li>Restart MySQL from WampServer to apply changes.</li>
      </ul>
    </li>
    <li class="step">
      <strong>Create a MySQL User for Remote Access</strong>
      <ul>
        <li>Log in to MySQL (preferably phpMyAdmin or the MySQL CLI) and run:
          <pre>
CREATE USER 'root'@'host.docker.internal' IDENTIFIED BY '';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'host.docker.internal';
FLUSH PRIVILEGES;
          </pre>
        </li>
        <li>This allows the root user to connect from host.docker.internal (Docker-to-host bridge).</li>
      </ul>
    </li>
    <li class="step">
      <strong>Allow Windows Firewall Access</strong>
      <ul>
        <li>Open port <b>3306</b> (or your MySQL port) in Windows Firewall to allow inbound connections.</li>
      </ul>
    </li>
    <li class="step">
      <strong>Find Your Windows Host IP Address</strong>
      <ul>
        <li>On your Windows machine, run <code>ipconfig</code> to find your local IPv4 address (e.g., <code>192.168.1.100</code>).</li>
      </ul>
    </li>
    <li class="step">
      <strong>Configure Your Docker App</strong>
      <ul>
        <li>In your Docker container’s database configuration c:\...invoice\docker-compose.yml, use:
          <pre>
DB_HOST=192.168.1.100   # Use your Windows host's IP address
DB_PORT=3306
DB_USER=youruser
DB_PASS=yourpassword
          </pre>
        </li>
        <li>Do <b>not</b> use <code>localhost</code> or <code>host.docker.internal</code> unless specially configured.</li>
      </ul>
    </li>
    <li class="step">
      <strong>Test the Connection</strong>
      <ul>
        <li>From inside your Docker container, install the MySQL client if needed and run:</li>
        <pre>
mysql -h 192.168.1.100 -u youruser -p
        </pre>
        <li>If this connects, your application should work too.</li>
      </ul>
    </li>
  </ol>
  <p>
    <strong>Summary:</strong><br>
    By making MySQL accessible from the network, granting remote user privileges, opening the firewall, and using your Windows IP in Docker configuration, your Linux Docker container can connect to the WampServer MySQL database on your Windows host.
  </p>
  <pre><b>docker-composer.yml</b>
      services:
  php:
    image: yiisoftware/yii-php:8.3-apache
    working_dir: /app
    volumes:
      - ./:/app
      - ~/.composer-docker/cache:/root/.composer/cache:delegated
    ports:
      - '30080:80'
    depends_on:
      - db
    environment:
      # You can pass DB parameters as env vars if your app supports it
      # Development Environment: Docker Desktop docker-linux image using cycle/orm database on Wampserver
      # Step 1: Docker Desktop settings: Open Docker Desktop ... Settings ... Docker Engine
      # Add or update the "dns" section: i.e. "dns": ["8.8.8.8", "1.1.1.1"]
      # Step 2: Get IP v4 address at c:\wamp\www\invoice>ipconfig. Insert in DB_HOST:
      # Step 3: Modify C:\wamp\bin\mysql\mysql8.3.0\my.ini [mysqld]bind-address=0.0.0.0
      # Step 4: phpadmin: CREATE USER 'root'@'host.docker.internal' IDENTIFIED BY '';
      #                   GRANT ALL PRIVILEGES ON *.* TO 'root'@'host.docker.internal';
      #                   FLUSH PRIVILEGES;
      DB_HOST: 192.168.0.1
      DB_DATABASE: yii3_i
      DB_USER: root
      DB_PASSWORD: root
      
      
  db:
    image: mysql:8.0
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: yii3_i
      # The user below is optional; root is fine for local dev
      MYSQL_USER: root
      MYSQL_PASSWORD: 
    ports:
      - "33060:3306" # Optional: expose if you want to connect from host
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
  </pre>
  <pre><b>C:\wamp\www\invoice\docker\dev\php\Dockerfile</b>
      FROM php:8.3-fpm-alpine

# Install system dependencies and PHP build tools
RUN apk add --no-cache \
      acl \
      fcgi \
      file \
      gettext \
      git \
      nano \
      curl \
      libjpeg-turbo-dev \
      libpng-dev \
      libwebp-dev \
      freetype-dev \
      icu-dev \
      libzip-dev \
      zlib-dev \
      gd-dev \
      oniguruma-dev \
      postgresql-dev \
      rabbitmq-c-dev \
      $PHPIZE_DEPS

# Install and enable PECL extension (amqp)
RUN pecl install amqp-1.11.0 \
    && docker-php-ext-enable amqp

# Clean up build dependencies and pear cache for a smaller image
RUN apk del --purge $PHPIZE_DEPS \
    && pecl clear-cache \
    && rm -rf /tmp/pear \
    && docker-php-source delete \
    && rm -rf /var/cache/apk/*

# Set up Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

# Use development php.ini as default
RUN ln -sf $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini

# Set the working directory
WORKDIR /app

# Copy configuration files (remove if not needed)
COPY docker/dev/php/conf.d/ $PHP_INI_DIR/conf.d/
COPY docker/dev/php/php-fpm.d/ /usr/local/etc/php-fpm.d/

# Copy and install PHP dependencies
COPY composer.json composer.lock* ./
RUN composer install --no-plugins --no-scripts --prefer-dist && composer clear-cache

# Copy the rest of your application code
COPY ./ ./

# Run post-install scripts
RUN composer run-script post-install-cmd

# Healthcheck for FPM
HEALTHCHECK --interval=30s --timeout=5s --start-period=1s \
  CMD REQUEST_METHOD=GET SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping cgi-fcgi -bind -connect 127.0.0.1:9000

# Entrypoint permissions and command
RUN chmod 0555 ./docker/docker-entrypoint.sh
ENTRYPOINT ["docker/docker-entrypoint.sh"]
CMD ["php-fpm"]
  </pre>
</p>
<p><b>13th June 2025</b></p>
<p>Aegis: </a></p>
<p>Most actively maintained MFA</p>
<P>Html for textbox string length</p>
<p>Php based 9 digit keypad</p>
<p>Adding an additional permission to middleware:</p>
<p>Best practices for using OTP's</p>
<p><b>12th June 2025</b></p>
<p>I am not using Telegram for OTP.</p>
<p>Two Factor Authentication using Aegis Authenticator app for android is functional</p>
<p>Enable Two Factor Authentication under Settings ... View ... Two Factor Authentication</p>
<p>Two options exist with this setting: </p>
<p>Option 1: With disabling: i.e. always show Qr code. Two Factor Authentication is currently enabled for additional security and is disabled after successful authentication for an additional layer of security until the next login. The Qr code will be presented each time after login for a new secret.</p>
<p>Option 2: Without disabling: i.e. Qr code shown once only during setup. Two Factor Authentication is currently enabled for additional security and is not disabled after successful authentication. The Qr code will not be presented each time after logging in after setting up.</p>
<p>When tfa starts, a new permission noEntryToBaseController is created. This prevents access to the BaseController</p>
<p>As soon as tfa ends, a new permission entryToBaseController is created. This remains until logging out.</p>
<p>Logging-in also initializes these permissions. See functions tfaNotEnabledUnblockBaseController and tfaIsEnabledBlockBaseController</p>
<p>Next: Docker Implementation and Testing</p>
<p><b>29th May 2025</b></p>
<p>Preparing for TOTP authentication using AegisAuthenticator</p>
<p>I will be trying to follow Copilot's suggestions on https://github.com/rossaddison/invoice/discussions/219</p>
<p>https://github.com/beemdevelopment/Aegis</p>
<p><b>23rd May 2025</b></p>
<p>Making Tax Digital - UK becomes compulsory on April 2026</p>
<p>1. Test Fraud Prevention Headers API is functional.<p>
<p>1a. Step 1: Settings ... View ... Making Tax Digital tab ... Generate. This app uses the WEB_APP_VIA_SERVER connection method. </p>
<p>There are approx. 13 headers that need to be generated - action 'setting/fphGenerate' and rebuild/js/settings.js.</p>
<p>1b. Step 2. Reports ... Test Fraud Prevention Headers API. </p>
<p>2. HmrcController.php has been setup in the backend folder and will include other API implementations. </p>   
<p>3. https://developer.service.hmrc.gov.uk/guides/fraud-prevention/connection-method/web-app-via-server
<p><b>26th April 2025</b></p>
<p>Retested oauth2 with rossaddison/yii-auth-client and introduced a gov uk oauth2 which has not been tested.</p>
<p><b>16th April 2025</b></p>
<p>Removed flash messages from BaseController and messages\en\app.php</p>
<p>Included new feature Modal Pdf for invoicing in options</p>
<p><b>10th April 2025</b></p>
<p>The following m.bat has been introduced to the root folder.</p>
<p><pre>@echo off
:: This batch script provides a menu to run common commands for the Invoice System project.
:: It allows users to execute PHP Psalm, check for outdated Composer dependencies, 
:: and run the Composer Require Checker, all from the directory where the script is located
:: Acknowledgement: Copilot

title Invoice System Command Menu
cd /d "%~dp0"

:menu
cls
echo =======================================
echo         INVOICE SYSTEM MENU
echo =======================================
echo [1] Run PHP Psalm
echo [2] Check Composer Outdated
echo [3] Run Composer Require Checker
echo [4] Exit
echo =======================================
set /p choice="Enter your choice [1-4]: "

if "%choice%"=="1" goto psalm
if "%choice%"=="2" goto outdated
if "%choice%"=="3" goto require_checker
if "%choice%"=="4" goto exit
echo Invalid choice. Please try again.
pause
goto menu

:psalm
echo Running PHP Psalm...
php vendor/bin/psalm
pause
goto menu

:outdated
echo Checking Composer Outdated...
composer outdated
pause
goto menu

:require_checker
echo Running Composer Require Checker...
php vendor/bin/composer-require-checker
pause
goto menu

:exit
echo Exiting. Goodbye!
pause
exit
</pre></p>
<p>A BaseController has been introduced for InvController, SalesOrderController, and QuoteController</p>
<p>Buttons in the inv/index are disabled using the following combination: 
<pre>'disabled' => 'disabled', 'aria-disabled' => 'true', 'style' => 'pointer-events:none'</pre>
</p>
<p><b>7th April 2025</b></p>
<p>DependencyDropdown created for Family Search</p>
<p>Product Index Family Filter introduced</p>
<p>The Family search (2 tier) will be extended to include a product search (3 tier) later.</p>
<p><b>4th April 2025</b></p>
<p>Dropdown sub menu for php details within resources\views\layout\invoice.php dropdown menu</p>
<p>Use DropdownItem::text($subMenu->generate($translator->translate('faq.php.info.details'),$urlGenerator, $subMenuPhpInfo)),  to create a submenu dropdown.

src\Widget\SubMenu compiles from an array specified in the layout  
</p>
<p><b>3rd April 2025</b></p>
<p>
Introduce CategoryPrimary and CategorySecondary Class in preparation for a dependency dropdown. They have not been built into the menu yet.

Psalm: Insert Final Class into all classes besides Entity Classes since these have to be non-final since they are extended by the Cycle/ORM proxy to create proxies.

Remove 'Invalid Argument' from psalm since all errors have been corrected.

Tip for solving difficult psalm errors: Ask Copilot online with the repository open at a specific page:  Insert Line number: Psalm Error press enter. Apply the suggestion.

Run 'php vendor/bin/psalter --issues=MissingOverrideAttribute' at the command prompt to install the #Override attribute automatically.

see https://psalm.dev/docs/running_psalm/issues/MissingOverrideAttribute/

psalm-suppress 16 NonInvariantDocblockPropertyType errors relating to Assets $js and $css

Update the \invoice\resources\views\invoice\generator\templates_protected files after creating the CategoryPrimary and CategorySecondary classes and views with the Generator.
Improvements to the Generator will improve with use.</p>
<p><b>30 March 2025</b></p>
<p>html encode check of views</p>
<p><b>26 March 2025</b></p>
<p>Removed the session interface from SettingRepository because console commands that use the SettingRepository do not use Session.</p>
<p>Only the Required red reminder hints are on the client forms. 'Not Required have been removed on the client forms. This will speed up the signing up of clients.</p>
<p>Client form's number of Required fields has been reduced to reduce input.</p>
<p>The following commands (config/console/commands.php and src/Command/Invoice) can be used at the console: </p>
<p>yii invoice/items -- creates a list of random invoice items with a Summary Table for the Item tax and two Invoice specific Taxes of 15% and 20%.</p>
<p>yii invoice/setting/truncate -- removes all the settings in the setting table -- An array in future can be passed to the InvoiceController which can be tweaked from within the config/common/params.</p>
<p>yii invoice/generator/truncate -- removes all the records in the gentor and gentor relation tables -- Reuse the generator to build CRUD for another something else.</p>
<p>yii invoice/inv/truncate1 -- removes all invoices and invoice related tables -- for development testing</p>
<p>yii invoice/quote/truncate2 -- removes all quotes and quote related tables -- for development testing</p>
<p>yii invoice/salesorder/truncate3 -- removes all salesorder and salesorder related tables for development testing</p>
<p>yii invoice/nonuserrelated/truncate4 -- removes all subsequent tables besides tables responsible for logging in</p>
<p>Console commands with a number after truncate  indicates the general sequence in order to avoid integrity constraint violations</p>
<p>General Rule of Thumb to avoid integrity constraint violations: If a table contains a foreign key e.g. tax_rate_id, then the TaxRate Table will be truncated later</p>
<p>Apply https://github.com/yiisoft/router/pull/262/files Hashing to urls in breadcrumbs for quick access to settings.</p>
<p>Bugfix printing of pdf documents in a client\'s language. The default language setting will be used to print documents unless the client has a different language.</p>
<p>Set the locale when the view is being rendered partially i.e. without a layout using <pre>$translator->setLocale($cldr);</pre></p>
<p>Include some functional tests with <pre>php vendor/bin/codecept run</pre></p>
<p><b>23 March 2025</b></p>

<p><pre>

c:\wamp128\www\invoice>yii invoice/items

+------------+-----------------+----------+------------+-----------------+------------+------------+----------------+------------+------------+------------+
| Name       | Description     | Quantity | Price/unit | (Discount/unit) | Subtotal   | (Discount) | After Discount | Tax(%)     | Tax        | Total      |
+------------+-----------------+----------+------------+-----------------+------------+------------+----------------+------------+------------+------------+
| Mouse      | 3-button        | 4        |       1.00 |       1.00      |       4.00 |       4.00 |       0.00     |      20.00 |       0.00 |       0.00 |
+------------+-----------------+----------+------------+-----------------+------------+------------+----------------+------------+------------+------------+
| Keyboard   | US              | 2        |       4.00 |       1.00      |       8.00 |       2.00 |       6.00     |      20.00 |       1.20 |       7.20 |
+------------+-----------------+----------+------------+-----------------+------------+------------+----------------+------------+------------+------------+
| Screen     | 24inch x 16inch | 1        |       3.00 |       1.00      |       3.00 |       1.00 |       2.00     |      15.00 |       0.30 |       2.30 |
+------------+-----------------+----------+------------+-----------------+------------+------------+----------------+------------+------------+------------+
| Hard drive | 1 TB            | 2        |       3.00 |       1.00      |       6.00 |       2.00 |       4.00     |      15.00 |       0.60 |       4.60 |
+------------+-----------------+----------+------------+-----------------+------------+------------+----------------+------------+------------+------------+
| Box        | Standard        | 1        |       3.00 |       1.00      |       3.00 |       1.00 |       2.00     |      15.00 |       0.30 |       2.30 |
+------------+-----------------+----------+------------+-----------------+------------+------------+----------------+------------+------------+------------+
|            |                 |          |            |                 |            |            |      14.00     |            |       2.40 |      16.40 |
+------------+-----------------+----------+------------+-----------------+------------+------------+----------------+------------+------------+------------+
+----------------------------------------------------+------------+
| After Item Discount                                |      14.00 |
| Add: Item Tax Total                                |       2.40 |
+----------------------------------------------------+------------+
| With Item Tax                                      |      16.40 |
| Invoice Taxes (15%       2.46, 20%       3.28)     |       5.74 |
+----------------------------------------------------+------------+
| Before Invoice Discount Total                      |      22.14 |
| (Invoice Discount as 10% of Before Discount Total) |       2.21 |
+----------------------------------------------------+------------+
| Total                                              |      19.93 |
+----------------------------------------------------+------------+
</pre></p>
<p><pre>
  C:\wamp128\www\invoice>php vendor/bin/codecept run
Codeception 5.2.1

Usage:
  command [options] [arguments]

Options:
  -h, --help             Display help for the given command. When no command is given display help for the list command
  -q, --quiet            Do not output any message
  -V, --version          Display this application version
      --ansi|--no-ansi   Force (or disable --no-ansi) ANSI output
  -n, --no-interaction   Do not ask any interactive question
  -c, --config[=CONFIG]  Use custom path for config
  -v|vv|vvv, --verbose   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  bootstrap             Creates default test suites and generates all required files
  build                 Generates base classes for all suites
  clean                 Recursively cleans log and generated code
  completion            Dump the shell completion script
  console               Launches interactive test console
  dry-run               Prints step-by-step scenario-driven test or a feature
  help                  Display help for a command
  init                  Creates test suites by a template
  list                  List commands
  run                   Runs the test suites
 config
  config:validate       Validates and prints config to screen
 generate
  generate:cest         Generates empty Cest file in suite
  generate:environment  Generates empty environment config
  generate:feature      Generates empty feature file in suite
  generate:groupobject  Generates Group subscriber
  generate:helper       Generates a new helper
  generate:pageobject   Generates empty PageObject class
  generate:scenarios    Generates text representation for all scenarios
  generate:snapshot     Generates empty Snapshot class
  generate:stepobject   Generates empty StepObject class
  generate:suite        Generates new test suite
  generate:test         Generates empty unit test file in suite
 gherkin
  gherkin:snippets      Fetches empty steps from feature files of suite and prints code snippets for them
  gherkin:steps         Prints all defined feature steps  
</pre></p>
<p><b>9 March 2025</b></p>
<p>Replace jquery datepicker with bootstrap datepicker due to consistent security vulnerabilities with the jquery datepicker.

Remove flexible date formats i.e. Field::text and replace with Field::date, or in Html

Remove the flexible date format under settings ... views ... general ... date format  for the time being, with possible reintroduction later. 

The js files are jquery based i.e. Basic syntax is: $(selector).action() so retain jquery.</p>
<p><b>8 March 2025</b></p>
<p>Include css for the invoice/inv/index.php breadcrumb courtesy from openCart into src\Invoice\Asset\invoice\css\style.css</p>
<p>The flash alert message can be adjusted for font and font-size and the close button can be adjusted in terms of fontsize in bootstrap5 settings</p>
<p>Codecov has been introduced as a separate workflow</p>
<p><b>5 March 2025</b></p>
<p>Apply breaking changes of latest yiisoft/router version 4 to config/common/di/router.php (https://github.com/yiisoft/router/pull/207)</p>
<p>c:\wamp64\www\invoice>php vendor/bin/codecept  run</p>
<p>Included a few additional forks which will be replaced later with updated composer.json's: 
        "rossaddison/yii-cycle-1": ">=1",                
        "rossaddison/yii-gii": "dev-master",
        "rossaddison/yii-middleware": "dev-master",
</p>
<p>Compiled round 1 of Codeception Acceptance tests, similar to yiisoft/demo, at the command prompt.</p>
<p>Use the error messages of yiisoft/validator in acceptance testing by including a new alias '@validatorCategory' in the config\common\di\translator.php</p>
<p><b>3 March 2025</b></p>
<p>Modal Layouts adjusted to reflect no 'aria-hidden' usage. Refactored yi-bootstrap/Modal has coded 'aria-hidden' issues and is not being used since these have to be suppressed with an 'inert' attribute via javascript in Chrome. i.e. modal_layout_yii_bs.php</p>
<p>Invoices that have been marked as sent so that they appear on the customer side can be reverted to draft provided the 'disable_read_only' setting is adjusted to 1.</p>
<p><b>22nd February 2025</b></p>
<p>'Bootstrap5' Setting tab introduced in tab_index.php with Offcanvas setting - start(left), end(right), top, bottom.</p>
<p>Bootstrap5 Offcanvas functional</p>
<p><b>19th February 2025</b></p>
<p>Google Translate version 2 functional.</p>
<p>A new GeneratorController function rebuildLocale() compares the source resources/messages/en/app.php with an already built {locale}/app.php. 
   A diff_lang.php file is created under src/Invoice/Language/English folder, and its keys are used to create the timestamped translated file in resources/views/invoice/generator/output_overwrite.
   An exception is thrown if the diff_lang.php file is too large for Google to translate.
</p>
<p>A few additonal psalm suppress statements have been included in psalm.xml and will be tackled soon.</p>
<p>
<pre>
     Implemented 16/02/2025 psalm version upgrade to 6.6.0
     This will have to be addressed with the cycle/orm which is error-messaging classes that are being made final.
     ClassMustBeFinal<br>
     
     Implemented 16/02/2025 new ActionButton(url: ...unable to psalm-suppress named parameters.
     InvalidArgument<br>

     Implemented 17/02/2025 upgrade to psalm version 6.7.1
     MissingOverrideAttribute<br>        
</pre>    
</p>
<p><b>10th February 2025</b></p>
<p>Scrutinizer removed with the possibility of reimplementation.</p>
<p>Branches updated with commits.</p>
<p>Google Cloud Translate version 2 branch created - work commencing.</p>
<p>ActionButtons implemented in all ActionColumns</p>
<p><pre>C:\wamp128\www\invoice>composer outdated
Color legend:
- patch or minor release available - update recommended
- major release available - update possible

Direct dependencies required in composer.json:
cycle/annotated              3.5.0  4.2.3  Cycle ORM Annotated Entities generator
google/cloud-translate       1.21.0 2.0.0  Cloud Translation Client for PHP
phpbench/phpbench            1.2.15 1.4.0  PHP Benchmarking Framework
phpunit/phpunit              11.5.7 12.0.2 The PHP Unit Testing framework.
spatie/phpunit-watcher       1.23.6 1.24.0 Automatically rerun PHPUnit tests when source code changes
symfony/console              6.4.17 7.2.1  Eases the creation of beautiful and testable command line interfaces
yiisoft/error-handler        3.3.0  4.0.0  Yii Error Handling Library
zircote/swagger-php          4.11.1 5.0.3  swagger-php - Generate interactive documentation for your RESTful API us...

Transitive dependencies not required in composer.json:
jolicode/jolinotif           2.7.3  3.0.0  Send desktop notifications on Windows, Linux, MacOS.
jolicode/php-os-helper       0.1.0  0.2.0  Helpers to detect the OS of the machine where PHP is running.
justinrainbow/json-schema    5.3.0  6.1.0  A library to validate a json schema.
netresearch/jsonmapper       4.5.0  5.0.0  Map nested JSON structures onto PHP classes
php-http/message-factory     1.1.0  1.1.0  Factory interfaces for PSR-7 HTTP Message
Package php-http/message-factory is abandoned, you should avoid using it. Use psr/http-factory instead.
phpbench/dom                 0.3.3  0.3.3  DOM wrapper to simplify working with the PHP DOM implementation
Package phpbench/dom is abandoned, you should avoid using it. No replacement was suggested.
phpunit/php-code-coverage    11.0.8 12.0.2 Library that provides collection, processing, and rendering functionalit...
phpunit/php-file-iterator    5.1.0  6.0.0  FilterIterator implementation that filters files based on a list of suff...
phpunit/php-invoker          5.0.1  6.0.0  Invoke callables with a timeout
phpunit/php-text-template    4.0.1  5.0.0  Simple template engine.
phpunit/php-timer            7.0.1  8.0.0  Utility class for timing
sebastian/cli-parser         3.0.2  4.0.0  Library for parsing CLI options
sebastian/comparator         6.3.0  7.0.0  Provides the functionality to compare PHP values for equality
sebastian/complexity         4.0.1  5.0.0  Library for calculating the complexity of PHP code units
sebastian/diff               6.0.2  7.0.0  Diff implementation
sebastian/environment        7.2.0  8.0.0  Provides functionality to handle HHVM/PHP environments
sebastian/exporter           6.3.0  7.0.0  Provides the functionality to export PHP variables for visualization
sebastian/global-state       7.0.2  8.0.0  Snapshotting of global state
sebastian/lines-of-code      3.0.1  4.0.0  Library for counting the lines of code in PHP source code
sebastian/object-enumerator  6.0.1  7.0.0  Traverses array structures and object graphs to enumerate all referenced...
sebastian/object-reflector   4.0.1  5.0.0  Allows reflection of object attributes, including inherited and non-publ...
sebastian/recursion-context  6.0.2  7.0.0  Provides functionality to recursively process PHP variables
sebastian/type               5.1.0  6.0.0  Collection of value objects that represent the types of the PHP type system
sebastian/version            5.0.2  6.0.0  Library that helps with managing the version number of Git-hosted PHP pr...
sentry/sdk                   3.6.0  4.0.0  This is a metapackage shipping sentry/sentry with a recommended HTTP cli...
sentry/sentry                3.22.1 4.10.0 A PHP SDK for Sentry (http://sentry.io)
symfony/config               6.4.14 7.2.3  Helps you find, load, combine, autofill and validate configuration value...
symfony/dependency-injection 6.4.16 7.2.3  Allows you to standardize and centralize the way objects are constructed...
symfony/finder               5.4.45 7.2.2  Finds files and directories via an intuitive fluent interface
symfony/framework-bundle     6.4.18 7.2.3  Provides a tight integration between Symfony components and the Symfony ...
symfony/http-kernel          6.4.18 7.2.3  Provides a structured process for converting a Request into a Response
symfony/process              6.4.15 7.2.0  Executes commands in sub-processes
symfony/yaml                 6.4.18 7.2.3  Loads and dumps YAML files</pre></p>
<p><b>8th February 2025</b></p>
<p>1. Include a function blockInvalidState in all the AuthController callback clients which checks for csrf attacks </p>
<p>and null session Auth state. Copilot was asked a question regarding this however the yii-auth-client repository </p>
<p>already stores the state during the yii-auth-client oauth2 buildAuthUrl function as 'authState'. </p>
<p>This value is compared with the state value returned by the IdentityProvider and if compared and </p>
<p>they are not the same then there is likely a csrf attack. This potential has been included as well </p>
<p>as potential for a null value since the AuthClient getState function is mixed so can return either a string value or null.</p>
<p>2. Include an idle_timeout setting into composer.json as suggested by Scrutinizer.</p>
<p>3a. Apply yiisoft/demo/pull/636 Adjust Mailer structure in Controllers.</p>
<p>3b. Include default bootstrap5 theme in config/web/params.php</p>
<p><b>1st February 2025</b></p>
<p>Privacy policy and terms of service introduced in preparation for TikTok OAuth2.0</p>
<p>vimeo/psalm 6.1 introduced - 186 errors fixed mainly related to PossiblyFalseArgument.</p>
<p>layout\invoice.php i.e.Internal non-guest Menu has been split between debug and production. Adjust YII_DEBUG=true in .env file at root.</p>
<pre>
C:\wamp128\www\invoice>php ./vendor/bin/psalm

JIT acceleration: OFF (disabled on Windows and PHP < 8.4)
Install PHP 8.4+ to make use of JIT on Windows for a 20%+ performance boost!

Target PHP version: 8.3 (inferred from composer.json) Enabled extensions: dom (unsupported extensions: fileinfo, pdo_sqlite, uopz).

Scanning files...

1576 / 1576...

Analyzing files...
------------------------------

       No errors found!

------------------------------
<br>
C:\wamp128\www\invoice>composer outdated
Color legend:
- patch or minor release available - update recommended
- major release available - update possible
<br>
Direct dependencies required in composer.json:
cycle/annotated                    3.5.0   4.2.3  Cycle ORM Annotated Entities generator
phpbench/phpbench                  1.2.15  1.4.0  PHP Benchmarking Framework
phpunit/phpunit                    10.5.44 11.5.6 The PHP Unit Testing framework.
spatie/phpunit-watcher             1.23.6  1.24.0 Automatically rerun PHPUnit tests when source code changes
symfony/console                    6.4.17  7.2.1  Eases the creation of beautiful and testable command line interfaces
zircote/swagger-php                4.11.1  5.0.3  swagger-php - Generate interactive documentation for your RESTful...
<br>
C:\wamp128\www\invoice>composer why-not cycle/annotated 4.2.3
rossaddison/invoice -         requires cycle/annotated (^3.5)
yiisoft/yii-cycle   2.0.x-dev requires cycle/annotated (^3.5)
Not finding what you were looking for? Try calling `composer require "cycle/annotated:4.2.3" --dry-run` to get another view on the problem.
<br>
C:\wamp128\www\invoice>composer why-not zircote/swagger-php 5.0.3
rossaddison/invoice   -         requires zircote/swagger-php (^4.0)
yiisoft/yii-debug-api 3.0.x-dev requires zircote/swagger-php (^4.0)
Not finding what you were looking for? Try calling `composer require "zircote/swagger-php:5.0.3" --dry-run` to get another view on the problem.

</pre>
<p><b>23rd January 2025</b></p>
<p>VKontakte Oauth2.0 Authorization Code introduced. </p>
<p><b>19th January 2025</b></p>
<p>LinkedIn Oauth2.0 Authorization Code introduced. </p>
<p><b>18th January 2025</b></p>
<p>Yandex Oauth2.0 Authorization Code with PKCE introduced. </p>
<p>Code Challenge Method S256 used.</p>
<p>X i.e. Twitter has been adjusted to include Code Challenge Method S256 instead of plain.</p>
<p><b>15th January 2025</b></p>
<p>X i.e. Twitter Oauth2.0 Authorization Code with PKCE Public Client (Not Confidential Client) functional. Confidential client will be introduced later.</p>
<p><b>9th January 2025</b></p>
<p>MicrosoftOnline Oauth2 functional</p>
<p><b>31st December 2024</b></p>
<p>Google Oauth2 functional.</p>
<p>Improve the ordering of the parameters in the AuthController functions and include callbackGoogle in the controller.</p>
<pre>..resources/rbac/assignments.php has been reduced to an empty array to allow for signing up. If you encounter any difficulties this is the format:
    return [
        1 => [
            'user_id' => '1',
            'item_name' => 'admin',
        ],
        2 => [
            'user_id' => '2',
            'item_name' => 'observer',
        ],
    ]; 
</pre>
<p>Next LinkedIn Oauth2</p>
<p><b>27th December 2024</b></p>
<p>Facebook Oauth2 functional.</p>
<p>src\Auth\Controller\AuthController <pre>function getAuthTokenType($identityProvider)</pre> shows the token-type for each identityProvider e.g. 'facebook' => 'access-token', 'email' => 'email-verification</p>
<p>and see UserInvController function signup RouteArgument $tokenType</p>
<p><b>25th December 2024</b></p>
<p>Github Oauth2 functional. A Github user is authenticated, signed up, and assigned a client automatically without disclosing passwords.</p>
<p>A new oAuthService function oauthLogin has been created which does not require a password since authentication is already provided by Github.</p>
<p>In summary, the user is found by means of their login i.e. username with code<pre>
    Auth\AuthService
    
    public function oauthLogin(string $login): bool
    {
        $user = $this->userRepository->findByLoginWithAuthIdentity($login);
        
        if ($user === null) {
            return false;
        }

        return $this->currentUser->login($user->getIdentity());
    }
</pre> and using the user identity, the current user is logged in.</p>
<p><b>Step 1.</b> When the user chooses to 'Continue to Github', Github returns an access token using <pre>$oAuthTokenType = $this->github->fetchAccessToken($request, $code, $params = [])</pre>, 
   and we use this token in a header request, to get information from the user by means of the Github Client in fork rossaddison/yii-auth-client
   <pre>$this->github->getCurrentUserJsonArray($oAuthTokenType);</pre>
   The auth/callbackGithub function concatenates a user using:</p>
<pre> $login = 'github'.(string)$githubId.$githubLogin;</pre>
<p>a. 'github' ... to distinguish it from other Identity Providers.</p>
<p>b. The Github Id which is an integer and naturally unique. </p>
<p>c. and the Github 'login' or username which is perhaps not that unique and may have been used more than once by the user when registering with other Identity Providers e.g LinkedIn</p>
<p><b>Step 2.</b> A random string password is built but will never be used and is hashed.</p>
<p><b>Step 3.</b> The authService attempts to 'first time login' with this 'login' and deactivate the hour token ...otherwise a new user, and userinv is created, directing the user to login with their Identity Provider. </p>
<p><b>Step 4.</b> The user must click on the 'please click here within the next hour to make active' button, to avoid our 'Github Access Token' from expiring.  
<p>Like the email verification token, our Github Access Token is set at an hour or 3600 seconds, allowing the user to activate and login within the hour and is unrelated to the Application's Github Access Token received from Github in step 1.</p>
<p><b>20th December 2024</b></p>
<p>Introduce Oauth2 and FlashMessage Trait</p>
<p>Oauth2 Settings Tab to include/exclude Github, Facebook, and Google continue buttons on both the login, and signup forms.</p>
<p><b>18th December 2024</b></p>
<p>Creating an Oauth Application with Github to get a Github Client Id and Client Secret so that users can login to your site with their Github login. </p>
<p><b>Step 1. </b><a href="https://docs.github.com/v3/oauth">Read the docs</a>
<p><b>Step 2. </b><a href="https://datatracker.ietf.org/doc/html/rfc6749#section-4.1">GitHub's OAuth implementation supports the standard authorization code grant type</a>    
<p><b>Step 3. </b><a href="https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps#web-application-flow">We are using the web application flow.</a>
<p><b>Step 4. </b><a href="https://github.com/settings/applications/new">Register your application</a></p>
<p><b>Step 5a. </b>Request a User's Web Identity with <pre>GET https://github.com/login/oauth/authorize</pre> and <pre>query parameters client_id, redirect_url, state, login</pre></p>
<p><b>Step 5b. </b>Users are redirected back to your site with a temporary code. Exchange this code for an access token using endpoint <pre>POST https://github.com/login/oauth/access_token</pre> with parameters client_id, client_secret, code, redirect_url
e.g. of an access token <pre>access_token=gho_16C7e42F292c6912E7710c838347Ae178B4a&scope=repo%2Cgist&token_type=bearer</pre> and if accept headers used ..in either json or xml format.</p>
<p><b>Step 5c. </b> The src/Auth/Controller/AuthController.php function callbackGithub, <s>with Middleware Authentication class</s> receives the query parameters code and state from Github.</p>
<p><b>Step 5d. </b>If the code is not an unauthorised 401 and the state param is not empty, fetch the token <pre>function OAuth2->fetchAccessToken</pre>.
<p><b>Step 5e. </b>If an official token is received, then use the Access token to access the Api on behalf of a user using <pre>Authorization: Bearer OAUTH-TOKEN<br>
GET https://api.github.com/user</pre><p>
<p><b>Step 5f. </b>Use the function vendor/rossaddison/yii-auth-client/src/Client/Github.php getCurrentUserJsonArray(OAuthToken $token) to use the built OauthToken Type with param 'access-token' specific to Github to access the user details e.g login i.e repository base, and id. </p>    
<p><b>18th December 2024</b></p>
<p>Bootstrap 5 - Apply refactor dropdown widget</p>
<p><b>11th December 2024</b></p>
<p>Prepare to test Google with returnUrls which have not as yet been setup.</p>
<p>Apply yiisoft/yii-dataview/pull/228 Refactoring of pagination to most indexes.</p>
<p><b>3rd December 2024</b></p>
<p>Include a 'Front Page' tab under settings giving checkboxes for each of the main pages for inclusion/exlusion into/from the site.</p>
<p><b>24th November 2024</b></p>
<p>Apply https://github.com/yiisoft/yii-dataview/pull/225</p>
<p><b>22nd November 2024</b></p>
<p>Bugfix pdf in inv/index</p>
<p>Clicking on template summary's default list limit button (first button) will redirect to the actual setting in general settings.</p>
<p><b>21st November 2024</b></p>
<p>Created redirect links from the inv/index breadcrumbs to specific settings in settings views e.g. invoice</p>
<p>Each link shows the current setting with a tooltip</p>
<p><b>16th November 2024</b></p>
<p>Include 4 action buttons in the action column of the inv/index</p>
<p><b>13th November 2024</b></p>
<p>The read only toggle gets set to a status e.g. draft, sent, viewed, paid which serves as a comparison when to set the invoice to read-only. Included a few more checks.</p>
<p><b>11th November 2024</b></p>
<p>1. Email with pdf attachment testing</p>
<p>2. Conflict section in composer.json removed because symfony 7.1.6 could not be installed containing symfony/mime/file 
    which yiisoft/mailer-symfony was dependent upon and could not be found in symfony/mime 5.45</p>
<p>3. General testing of invoices. Few changes made regarding read_only status. </p>
<p><b>9th November 2024</b></p>
<p>1. Retest the .env and autoload files with Psalm Level 1 and filter_var function. Changes made.<p>
<p>2. public/index.php and yii console file at the root tested. Changes made.<p>
<p>3. The build database boolean value appears under performance on the application's menu now and warns if it has not been set back to false after setup.<p> 
<p>4. Include a php-space-filter-var-test.php function for testing .env values at the command line using the filter_var function.</p>
<p>5. If signup fails due to no internet connection, token 'disabled' with: 'already_used_token' and a time value. The admin has to make the signed up userinv status active. </p>
<p><b>7th November 2024</b></p>
<p>1. Remove the email field from userinv entity/table although the relation getUser is used to retrieve the email field in the user table.</p>
<p>2. The User entity email field is shown 'disabled' in the userinv forms.</p>
<p>3. If the user signs up on a localhost with no connection to the internet they will be able to login provided the admin makes their account active via. userinv</p>
<p><b>31st October 2024</b></p>
<p>1. Reconfigured config/common/params to accomodate yiisoft/mailer-symfony</p>
<p>2. Tested SignUpController, ForgotController and are functional.</p>
<p>3. Added additional email headers for authorisation.</p> 
<p><b>24th October 2024</b></p>
<p>1. yiisoft/mailer backward compatibility corrections. pull requests 104 - 109</p>
<p>2. MessageBodyTemplate(s) removed.</p>
<p>3. ->withCharSet('UTF-8') added to messages</p>
<p>4. yiisoft/bootstrap5 dev-master used in an attempt to remove  composer-dependency-checker Yiisoft\Bootstrap5\AlertType unknown sybmol error https://github.com/rossaddison/invoice/actions/runs/11365127873/job/31612623182</p>
<p>5. Previous MessageBodyTemplates moved into ->withTextBody</p>
<p>6. Slight adjustment/improvement to resources/views/layout/alert with AlertVariants assigned to variables outside the match statement.</p>
<p>7. mailert->withAttached changed to withAttachments</p>
<p>8. self-imposed more disciplined approach to using branches and pull requests</p>
<p>Testing of above changes on one.com and beging yii3 discussion on webhooks use with Telegram and testing with one.com</p>
<p><b>23rd October 2024</b></p>
<p><b>Telegram</b></p>
<p>1. 10 step instructions for using Telegram under Settings ... View ... Telegram tab to setup client/customer payment notifications</p> 
<p>2. getUpdates tested and functional</p>
<p>3. A webhook is currently not being used because payment notifications are being sent to the admin only. No messages are being sent from customers.</p>
<p>4. Currently messages from the admin's telegram account to the bot can be retrieved using the manual getUpdates</p>
<pre>
    $settingRepositoryTelegramToken = $settingRepository->getSetting('telegram_token');
    // chat id of first message (usually admin's personal non-bot telegram chat id) sent to bot
    // since the admin will be receiving payment notification messages from the bot
    $telegramChatId = $settingRepository->getSetting('telegram_chat_id');
    $telegramHelper = new TelegramHelper(
        $settingRepositoryTelegramToken, 
        $this->logger
    );
    $this->telegramBotApi = $telegramHelper->getBotApi();
    $sendMessage = $this->telegramBotApi->sendMessage($telegramChatId, 'Hello World');
</pre>
</p>
<p>5. Next: Use Telegram to automatically send a Telegram Invoice to any client that is in the chat group.</p>
<p><b>13th October 2024</b></p>
<p><b>Forgot Password</b></p>
<p>1. User clicks on 'I forgot my password' on login form. </p>
<p>2. src/Auth/Controller/ForgotPasswordController. function forgot - Request Password Reset Token Form is presented for User to enter email address</p>
<p>3. When the form is submitted, an email with a masked link is sent to the user's inbox.</p>
<p>4a. A viewInjection site/forgotalert is presented encouraging the user to click on the link in their inbox.</p>
<p>4b. A viewInjection site/forgotfailed is presented, if the sites senderEmail has not been setup encouraging the user to setup their config/common/params.php senderEmail.</p>
<p>5. When the masked link is clicked, the src/Auth/ResetPasswordController.php function resetpassword unmasks the token.</p>
<p>6. A form with a 'new password' field and a 'verify new password' field is presented if the now unmasked token matches the 32bit string in Token->token with type 'request-password-reset'</p>
<p>7. As soon as this form is submitted, the following algorithm 
       https://github.com/yiisoft/yii2-app-advanced/blob/master/frontend/models/ResetPasswordForm.php is followed:</p> 
<pre>
   1.) setPassword in User 
   2.) nullify PasswordResetToken by setting the Token:token to null but retaining the Token:type 
       so that the token can no longer be used.  
   3.) generateAuthKey in Identity
</pre>
<p>8. A viewInjection site/resetpasswordsuccess 'Password Reset' is shown if the password has changed.</p>
<p>9. A viewInjection site/resetpasswordfailed 'An error occurred while trying to send your password reset email. 
      Please review the application logs or contact the system administrator.' is shown if the password has failed.</p>
<p>10. A logger interface records the failed error messages.</p>
<p>11. Testing site one.com settings have been included in the shared hosting FAQ</P>
<p><b>7th October 2024</b></p>
<p><b>User signup: </b></p>
<p>1. src/Auth/Controller/SignUpController function signup - Signup Form is hydrated or filled with login i.e. username, password, and email address</p>
<p>2. A user count is made, and if this is the first user i.e count == 1, the user is assigned an admin role. Otherwise an observer role is allocated. Viewed at resources/rbac/assignments.php</p>
<p>3a. An email verification token is generated with a random string of 32 and then Masked with Yiisoft\Security\TokenMask before sending by email.</p>
<p>3b. The type of the token i.e.  email-verification-token, is stored along with the unmasked token in the token table which holds the different types of tokens.</p>
<p>4. A try-catch statement will raise an exception if the email is not sent and therefore redirect.
<p>5. As soon as the user clicks on the link in their inbox, the config/common/routes/routes.php userinv/signup/{language}/{token} route is used.
<p>6. There is no AccessChecker Middleware on this route => for security...the use of the email-verification-token.</p>
<p>7. A new window is opened when the link is clicked, and arguments language, and token in route are used, and the token is then unmasked.</p>
<p>8. The timestamped email-verification-token is split into its token and timestamp, allowing an hour (3600 secs) as limit for user to verify via email.</p>
<p>9. The identity of the user is then found by means of this token referring to src/auth/tokenRepository.</p>
<p>10. If the 'signup_automatically_assign_client' has been setup by the admin under Settings...General, the observer user will be assigned one client automatically. This client is identified with the same email address as the user.</p>
<p>11. The admin has a flash message 'on button' option to choose this option after signing in once signed up. </p>
<p>12. Quotes and invoices can then be automatically created for this client without the need to assign a client manually to the userinv (extension table of user) account.</p>
<p>13. The client index has been consolidated freeing up space.</p>
<p>14. The client form has required and essential fields appearing first at the top. The client entity adopts this ordering of fields as well.</p>
<p>15. Bugfix on the company_logo_and_address</p>
<p>16. Settings ... General includes two new settings 'Assign a client to user upon signing up' and 'Assign a client with default minimum age of eighteen to the user upon signing up'. Included in defaults in InvoiceController.</p>
<p>17. Removed the password field on the extension table userinv since the password is hashed in the user table.</p>
<p>18. Added an email field to the signup form</p>
<p>19, There is no need for a user_id field in the Identity entity because the hasOne Identity relation in the User table automatically creates this
<p>20. Bugfix Auth/Token 'type' changed from integer to string</p>
<p>21. The column types in the Client Entity have been improved.</p>
<p>22. The size of an email field in the User table is a standard 254 characters.</p>
<p>23. The salt, email, and the passwordresettoken fields have been removed from userinv (InvoicePlanes user table) since these will be handled independently by the the token and identity table. </p>
<p>24. The token table will be saving the different types of tokens. The token table can be moved to a 'blog-api' but will be kept inclusive for the time being.</p>
<p>25. Include an additional setting in common/config/params symfony-mailer 'enabled' as a checked used in the SignUpController.php</p>
<p>Next: Forgot Password link on login form and reset-password-token and remember-me-token. </p>
<p>Tip: For testing puposes, truncating tables i.e. emptying them of rows, will reset the autoincrement counter on each table in mySql i.e. check all tables ... 'with selected' dropdown ... 'Delete data or table' ... 'Empty'<p>
<p><b>25th September 2024</b></p>
<p>A separate codeql.yml file has been autogenerated by Github because this is a public repository.</p>
<p>This has been done to: </p>
<p>1. enable AI suggestions through codeql using rossaddison/invoice ... settings ... code security ... advanced setup.</p>
<p>2. support jQuery who have now included codeQL in their workflows recently.
<p>3. eliminate the need to constantly check jQuery for vulnerabilities in their javascript.    
<p>Reference: <a href="https://docs.github.com/en/code-security/code-scanning/managing-code-scanning-alerts/about-autofix-for-codeql-code-scanning#limitations-of-autofix-suggestions">Limitations of Autofix suggestions</a></p>
<p><b>18th September 2024</b></p>
<p>A 'stop logging in' and 'stop signing up' setting appear in the settings...view tab and is related to the src\ViewInjection\LayoutViewInjection.php</p>
<p>Grids Quote, Inv, Product, Client can be sorted on the index.php and the guest.php side</p>
<p><a href="https://github.com/yiisoft/demo/pull/624"> Apply propertyTranslatorProviderInterface to Login and SignUp Form</a></p>
<p>Invoices can be marked as 'sent' using single button on inv/index so that invoice appears on client side.</p>
<p>A selection of invoices can be copied which is useful for batch invoice processing from inv/index.php.</p>
<p>CodeQL has been included in workflow actions and the Snyke bot is being used to check for security vulnerabilities.</p>
<p>40 security vulnerabilities were removed. Responsible files have been removed.</p>
<p>jQuery is to be informed of 12 security vulnerabilities using their latest beta version.
   All responsible files have been removed from the directory ...src\Invoice\Asset\jquery-ui-1.14.0</p>
<p>Set .env YII_DEBUG default value to non-empty. If the value is not empty so there is a value after the = sign it will be 
evaluated as a string first and then as true. YII_DEBUG= equates to false whereas YII_DEBUG=false equates to true because it is not empty.</p>
<p>Scrutinizer used for code analysis. scrutinizer.yml appears at the root.</p>
<p>bootstrap 4 data-toggle to bootstrap 5 data-bs-toggle.</p>
<p>javascript vulnerability in emailtemplate.js removed with separate view grid linked to the emailtemplate index.</p>
<p>yiisoft-yii-view-renderer replaces yiisoft/yii-view</p>

<p><b>3rd August 2024</b></p>
<p>Psalm Level 1 Testing Completed using below file format.</p>
<p><pre><xmp>
    <plugins><pluginClass class="Psalm\SymfonyPsalmPlugin\Plugin"/></plugins>    
    <projectFiles>
        <directory name="config" />
        <directory name="resources/views" />
        <directory name="src" />
        <file name="public/index.php"/>
        <file name="yii"/>
        <file name="autoload.php"/>
        <ignoreFiles>
            <directory name="vendor/yiisoft/requirements/src" />
        </ignoreFiles>
    </projectFiles></xmp></pre></p>
    <p>Requirements checker can be run at the command prompt e.g. C:\wamp64\www\invoice>php requirements.php</p>
    <p>The requirements.php file sits in the root folder.</p>
    <pre>Requirements Checker
        This script checks if your server configuration meets the requirements
        for running Yii application.
        It checks if the server is running the right version of PHP,
        if appropriate PHP extensions have been loaded, and if php.ini file settings are correct.

        Check conclusion:
        -----------------

        PHP version: OK

        PDO MySQL extension: OK

        Intl extension: OK

        -----------------------------------------
        Errors: 0   Warnings: 0   Total checks: 3
    </pre>
    <p>The nullable: true relations getPostalAddress, getDelivery, and getDeliveryLocation have been removed in favour of using</p>
    <p>repository access to the inv related tables in order to retrieve relevant information.</p> 
<p>What's next: Telegram integration</p>
<p><b>13th July 2024</b></p>
<p>2. Folders p and q completed.</p>
<p><b>5th July 2024</b></p>
<p>Apply backward compatibility for new yiisoft/yii-view-renderer:</p>
<p>1. config/common/params adjusted and namespace <code>use Yiisoft\Yii\View\ViewRenderer;</code> changed to <code>use Yiisoft\Yii\View\Renderer\ViewRenderer;</code></p>
<p>Redundant routes removed...config/common/routes/routes.php </p>
<p>Psalm level 1 testing of invoice/config folder</p>
<p>Psalm level 1 testing of all invoice/resources/views/invoice folder started.</p>
<p>1. Folders z to r completed.</p>
<p>Psalm Level 1 file future psalm.xml</p>
<p><xmp>
    <plugins><pluginClass class="Psalm\SymfonyPsalmPlugin\Plugin"/></plugins>    
    <projectFiles>
        <directory name="config" />
        <directory name="resources/views/invoice" />
        <directory name="src" />
        <file name="public/index.php"/>
        <file name="yii"/>
        <file name="autoload.php"/>
    </projectFiles></xmp></p>
<p>What's next: Psalm Level 1 testing of views q to ...</p>
<p>What's after Psalm Testing: Integrating vjiks Telegram Api for hopefully client invoice payment notifications.</p>
<p><b>22nd June 2024</b></p>
<p>1. Include a requirements checker under FAQ's using newish repo 'yiisoft/requirements'.</p>
<p>2. Tested the Generator with a dummy Entity and updated the Generator with a few additional fixes.</p>
<p>3. Included actual ini_get values under the performance section on the main menu.</p>
<p>4. Alphabetical reordering of composer .json. (Except Payment Providers and rossaddison/ temporary
 forks which will be removed later).</p>
<p>5. docker-compose.yml does not require the version number anymore.</p>
<p><b>14th June 2024</b></p>
<p>1. The form for creating email templates i.e. resources/views/invoice/emailtemplate/form.php has been split into form_invoice and form_quote</p>
<p>2. All the form inputs have been html encoded and the forms have been converted to using Yiisoft php code instead of the HTML. </p>
<p>3. The generator has been used to help develop the new InvSentLog Entity and has been upgraded. Further refinements will naturally occur with continued use of the Generator. </p>
<p>4. Purpose of InvSentLog: Log or record all invoices that have been sent by email to a specific customer. The index can be filtered according to invoice number on both the Client and Admin side. </p>
<p>5. The Admin side can also filter the emails according to client name. The user associated with this client is also shown.</p>
<p>6. An additional field has been created for Client i.e. Title. The dropdown for this is built from the language folder invoice\resources\messages\en\app.php.</p>
<p>7. jQuery has been upgraded to the latest. i.e. ui 1.13.2 to 1.13.3. and 3.6.0 to 3.7.1</p>
<p>8. The UserInv index now includes the function revoke all roles i.e. revoke the Accountant and Observer role . Excludes the Administrator role. 
<p>9. A few temporary forks i.e. rossaddison/... have been created whilst vjik is busy with the upgrades. These will be removed later.
<p>10. The mailer had a bug which has been fixed and has been tested with a few emails. </p>
<p><b>24th April 2024</b></p>
<p>Include stopLoggingIn and stopSigningUp variable into the LayoutViewInjection</p>
<p>Include these variables into main.php</p> 
<p>Replace currentRoute with RouteArguments in InvoiceController.php</p>
<p>The $debugMode variable now is linked to the .env file. Alter the debugMode in the .env file now instead of the LayoutViewInjection.
   If the $_ENV['BUILD_DATABASE']  environment variable is linked directly in the config/common params.php file it results in performance degradation so leave the setitng on 'false' for the moment.
</p>
<p><b>13th April 2024</b></p>
<p>1. Create a function userinv/guestlimit which stores a guest user's page size limiter selector's last choice.</p>
<p>2. This value is then specific to the user and separate from the default_page_size limiter.</p>
<p>3. Introduce all the invoice statuses to the guest grid.</p>
<p>4. Create a separate payment guest index page for logged in users with clients.</p>
<p>5. Create a separate payment online log guest index page for logged in users with clients.</p>
<p>6. Added the extra field to user inv: ListLimit</p>
<p>7. Adjusted the layouts i.e. ...resources/views/layout guest.php, main.php, and invoice.php
      to better reflect the logo.
</p>
<p>8. Adjusted the pdfHelper to accomodate additional templates. It should be noted that mpdfHelper is not bootstrap compatible.</p>
<p>9. Include two filters InvNumer and Inv Amount Total into the guest grid.</p>
<p>10. Include the additional Invoice statuses into the InvRepository statuses array.</p>
<p>11. Include an addition buttonsGuest function into the PageSizeLimiter widget used for guest users paying off Invoices.</p>
<p></p>
<p><b>2nd April 2024</b></p>
<p>1. The debug mode setting, normally situated in ..\views\layout\invoice\main.php, has been moved to the </p>
<p>   ..\src\ViewInjection\LayoutViewInjection.php return array i.e.  'debugMode' => true, </p>
<p>2. A few basic front pages have been created for the site using 
    <a href="https://bootstrapbrain.com/template/free-bootstrap-5-multipurpose-one-page-template-wave/">
        BootstrapBrain Wavelight Free Template
    </a>
    to illustrate the common view injection.
</p>
<p>3. All dependency classes e.g. bsb- have been removed from the template and just raw bootstrap 5 code remains</p>
<p>4. An acknowledgement link has been included on the about page.</p>
<p>5. A soletrader layout template has been created to illustrate the ..\src\ViewInjection\CommonViewInjection.php pages. i.e. ..resources\views\layout\templates\soletrader\main.php</p>    
<p>6. The front pages use the variables declared in the CommonViewInjection.php pages.</p>
<p>7. The Mollie Payment Api has been introduced. <a>https://github.com/mollie/mollie-api-php</a>
      A redirectUrl and not a webhookUrl is being used. After clients make payment on the Mollie site,
      they are redirected back with the invoice_url_key. This is matched with Mollie's metadata invoice_url_key.
      A payment table appears above the invoice view on successful payment.
</p>
<p>8. Invoices are accepting payments. TODO: Refunds will be available on balancing credit notes if the invoice has a payment.
<p>
<p>9. Updated custom fields index.</p>
<p>10. Payment gateways Amazon, Stripe, and Mollie have been tested.</p>
<p>11. Functions within the PaymentInformationController have been renamed from 'form' to 'inform' to
       better reflect the fact that data is not being captured on a form for pci compliance purposes.
</p>
<p>12. Additional invoice statuses have been included in inv/getStatuses(Translator $translator) namely
       unpaid, reminder, letter, claim, judgement, enforcement, credit_invoice_for_invoice, loss 
</p>       
    </code>    
<p>
<p><b>8th March 2024</b></p>
<p>Update the client index with filters firstname surname, and a mini table of invoices per client using Entity Client ArrayCollection</p>
<p>to build the ArrayCollection up and use its <b>count</b> and <b>toArray</b> function to count the number of invoices</p>
<p>and produce outstanding balance details respectively</p>

<p><b>27 February 2024</b></p>
<p>Psalm Level 1 Testing of Form Hydration, and Form Model Implementation</p>
<p>Transfering of relevant code to the Generator templates.</p>
<p>Creation of two bootstrap modal widgets src\Widget\Bootstrap5ModalInv.php, and src\Widget\Bootstrap5ModalQuote.php</p>
<p></p>

p>Further integration of the entity into the form constructs for the rest of the entities.</p>
<p><b>11 December 2023</b></p>
<p>https://github.com/yiisoft/form/issues/298</p>
<p>Form Hydration, Form Model for Client, ClientNote, and Setting have been completed.</p>
<p><b>18 November 2023</b></p>
<p>Do not use 'Reset' word in the context of changing a password. Use 'Change' instead. <a href=https://github.com/yiisoft/demo/pull/602>Pull request 602 under development.</a></p>
<p>So reset switched to change on all files</p>
<p>The reset password aspect of yii3 is still being developed</p>
<p><b>25 September 2023</b></p>
<p>Bugfix $ucR variable undefined. Retain within controller rather than view.</p>
<p>Testing on yii3i.co.uk</p>
<p>.htaccess file located at root (yii3i.co.uk/) rebasing to /yii3i (main folder yii3-i-main) directing to public folder</p>
<p><a href = "https://stackoverflow.com/questions/23635746/htaccess-redirect-from-site-root-to-public-folder-hiding-public-in-url/23638209#23638209">Stackoverflow </a></p>
<p>
    <code>
        RewriteEngine On<br>
        RewriteBase /yii3i<br>
        RewriteCond %{THE_REQUEST} /public/([^\s?]*) [NC]<br>
        RewriteRule ^ %1 [L,NE,R=302]<br>
        RewriteRule ^((?!public/).*)$ public/$1 [L,NC]<br>
    </code>
</p>
<p><b>24 September 2023</b></p>
<p>Multiple Product Image Gallery can be created for each product under Product ... Index ... View ... View ... Product Images</p>
<p>Sales Report by Product/Task</p>
<p>Bug Fix InvItemAllowanceCharge Edit.</p>
<p><b>20 August 2023</b></p>
<p>Each Product has an additional field called Additional Item Property Name and Value. These product properties can be added from the product view.
<p>A number of Additional Properties can be added to a product now by means of the Product Property Entity.</p>
<p><img src="/site/options.png" height="300" width="500"></p>
<p><img src="/site/ecosio_openpeppol_ubl_invoice_3_15_0.png" height="300" width="600"></p>
<p>Introduce Peppol (src\Invoice\Helpers\Peppol\PeppolHelper)</p>
<p><a href="https://ecosio.com/en/peppol-and-xml-document-validator-button/?pk_abe=EN_Peppol_XML_Validator_Page&pk_abv=With_CTA"> An Ecosio validated 0 error xml e-invoice</a> can be generated. 
<p>Introduce StoreCove (src\Invoice\Helpers\StoreCove\StoreCoveHelper) </p>
<p><a href="https://www.storecove.com/docs#_json_object">A Storecove Json Encoded Invoice</a> can be generated. </p>
<p><b>Requirement 1:</b></p>
<p>With VAT enabled - VAT Invoices can now be created.</p>
<p><b>Requirement 2:</b></p>
<p>Store Cove Api connection functions have been created. (src\Invoice\InvoiceController.php store_cove_call_api)</p>
<p><b>Requirement 3:</b></p>
<p><b>Xml electronic invoices - Can be output</b> if the following sequence is followed:</p>
<p>a: A logged in Client sets up their Peppol details on their side via Client...View...Options...Edit Peppol Details for e-invoicing. </p> 
<p>b: A quote is created and sent by the Administrator to the Client.</p>
<p>c: A logged in Client creates a sales order from the quote with their purchase order number, <s>purchase order line number</s>, and their contact person in the modal.</p>
<p>d: A logged in Client, on each of the sales order line items, inputs their line item purchase order reference number, and their purchase order line number. (Mandatory or else exception will be raised). </p>
<p>e: A logged in Administrator, requests that terms and conditions be accepted.</p>
<p>f: A logged in Client accepts the terms and conditions. </p>
<p>g: A logged in Administrator, updates the status of the sales order from assembled, approved, confirmed, to generate. </p> 
<p>h: A logged in Administrator can generate an invoice if the sales order status is on 'generate'</p>
<p>i: A logged in Administrator can now generate a Peppol Xml Invoice using today's exchange rates setup on Settings...View...Peppol Electronic Invoicing...One of From Currency and One of To Currency</p>
<p>j: Peppol exceptions will be raised.</p>
<p><b>22 March 2023</b></p>
<p>Preparation for Peppol e-Invoicing: UBL classes created using num-num/ubl-invoice. See folder src/Invoice/Ubl. Psalm Level 1 tested.</p>
<p>Html of invoice can be created - modal_inv_to_html - under View...Options</p>
<p>Logo introduced on invoices</p>
<p>A common company logo and address template created under invoice/setting/views.</p>
<p>A sumex extension table of table Invoice created. - Generating a sumex pdf is in its infancy.</p>
<p>Removed extensions causing DOM errors. F12 not carrying any errors.</p>
<p>Simplified Introductory Slider</p>
<p>Created 3 additional 'Yes/No' settings for 1. Stream (G Icon) 2. Archive (folder icon) 3. Html code (code icon) under Pdf Settings</p>
<p>Archives are saving consistently under src/Invoice/Uploads/Archives</p>
<p>Bugfix: Delete inv item redirects to interim page properly.</p>
<p>Bugfix: Cannot delete item if Invoice has 'sent' status.</p>
<p>Zugferd Invoices are archived under Invoice/Uploads/Temp/Zugferd wiht a random string filename.</p>
<p><b>04 March 2023</b></p>
<p>Introduce ZugferdHelper and ZugferXml</p>
<p>LoginForm and SignUp form - Extend both forms with RulesProviderInterface<a href>https://github.com/yiisoft/form/pull/249</a></p>
<p>Psalm Level 1 Testing - 0 errors</p>
<p><b>23 February 2023</b></p>
<p><s>Psalm Level 1 Testing - 1 error - Framework related</s></p>
<code>ERROR: ImplementedReturnTypeMismatch - vendor/yiisoft/form/src/FormModel.php:218:33 - The inherited return type 'iterable<int|string, Yiisoft\Validator\RuleInterface|callable|iterable<int, Yiisoft\Validator\RuleInterface|callable>>' for Yiisoft\Validator\RulesProviderInterface::getRules is different to the implemented return type for Yiisoft\Form\FormModel::getrules 'array<array-key, mixed>' (see https://psalm.dev/123)
    public function getRules(): array</code>
<p><b>31 January 2023</b></p>
<p>Psalm Level 2 Testing - 0 errors</p>
<p>Bug Fix: Edit Task</p>
<p><b>28 January 2023</b></p>
<p>Helpers\CountryHelper\get_country_list</p>
<p>The following code is functional but results in UnresolvableInclude: Worth coming back to.</p>
<p>Psalm Level 2 Testing - Submission 1</p>
<code>    
/**<br>
 * Returns an array list of cldr => country, translated in the language $cldr.<br>
 * If there is no translated country list, return the english one.<br>
 *<br>
 * @param string $cldr<br>
 * @return mixed<br>
 */    <br>
public function get_country_list(string $cldr) : mixed<br>
{<br>
    $new_aliases = new Aliases(['@helpers' => __DIR__, '@country_list' => '@helpers/Country-list']);<br>
    $file = $new_aliases->get('@country_list') .DIRECTORY_SEPARATOR. $cldr .DIRECTORY_SEPARATOR.'country.php';<br>
    $default_english = $new_aliases->get('@country_list') .DIRECTORY_SEPARATOR.'en'.DIRECTORY_SEPARATOR.'country.php';<br>
    if (file_exists($file)) {<br>
        /**<br>
         * @psalm-suppress UnresolvableInclude<br>
         */<br>
        return (include $file);<br>
    } else {<br>
        /**<br>
         * @psalm-suppress UnresolvableInclude<br>
         */<br>
        return (include $default_english);<br>
    }<br>
}<br>
</code>
<p><b>27 January 2023</b></p>
<p>Psalm Level 3 Testing (0 errors)</p>
<p>Testing</p>
<p>Upgrade Amazon Pay: 2.4.0 => 2.5.1</p>
<p>Test Amazon Pay with a Client logon.</p> 
<p>1. Client has been registered as a User via Yii Demo.</p>
<p>2. Client's User id has been transferred to UserInv via Settings ... User Account...+ by means of an Admin logon</p>
<p>3. Client's client_id has been assigned to their user_id via Settings ... User Account ...Assigned Clients by means of an Admin logon.</p>  
<p>Remove unknown region 'gb' bug. Admin must select one of three amazon regions in dropdown under Settings ... Online Payment. </p>
<p>Include Amazon's 3 standard regions ie. North America, Europe, and Japan in regions dropdown on Amazon_pay Settings Payment Gateway.</p>
<p>If region is not set, default to 'eu' in PaymentInformation/amazon_get_region</p>
<p>Include default time/zone under InvoiceController</p>
<p><b>23 January 2023</b></p>
<p>Psalm Level 3 Testing (0 errors)</p>
<p>Improve security of client viewing their quotes/invoices online with following code in InvController/url_key function</p>
<p><code>
        // After signup the user was included in the userinv using Settings...User Account...+<br>
$user_inv = $uiR->repoUserInvUserIdquery($currentUser_getId);<br>
// The client has been assigned to the user id using Setting...User Account...Assigned Clients<br>
$user_client = $ucR->repoUserClientqueryCount($currentUser_getId, $inv->getClient_id()) === 1 ? true : false;<br>
if ($user_inv && $user_client) {  <br>
</code></p>
<p>If the currentUser getId returns a null value they are a guest. See documentation. Yiisoft/CurrentUser</p>
<p>Note: The Psalm PossiblyNullArgument has to be suppressed here to allow for a possible null value here for validation of Yiisoft guest status. </p>
<p>No user with Yiisoft guest status is allowed in.</p>
<p><b>3 January 2023</b></p>
<p><a href="https://github.com/yiisoft/demo/issues/559"><s>Issue 559</s></a></p>
<p>Uzbek Introduced</p>
<p>Psalm Level 7,6,5,4 Testing (0 errors)</p>
<p><b>2 January 2023</b></p>
<p>Psalm Level 7,6,5,4 Testing (0 errors)</p>
<p><b>30 December 2022</b></p>
<p><b>12 Post-setup Steps to Introducing Azerbaijani language</b></p>
<p>1. config/web/params {locales}</p>
<p>2. views/layout/invoice.php {az_Asset and menu construction}</p>
<p>3a. views/layout/main.php {menu construction}</p>
<p>3b. views/layout/guest.php {menu construction}</p>
<p>4. SettingRepository/locale_language_array</p>
<p>5. Settings...Views...Google Translate...select locale from dropdown</p>
<p>6. Generator... Translate src/Invoice/Language/English/ip_lang.php</p>
<p>7. Generator... Translate src/Invoice/Language/English/gateway_lang.php</p>
<p>8. Generator... Translate src/Invoice/Language/English/app_lang {copied from ...resources/messages/en/app.php</p>
<p>9. Retrieve from ...views/invoice/generator/output_overwrite</p>
<p>10. copy output_overwrite/_ip_lang to src/Invoice/Language/{new language}</p>
<p>11. copy output_overwrite/_gateway_lang to src/Invoice/Language/{new language}</p>
<p>12. copy output_overwrite/_app.php to ...resources/messages/{new locale}</p>
<p><a href="https://github.com/yiisoft/demo/issues/559">Issue 559</a>
<p><code>Psalm level 4 php ./vendor/bin/psalm --alter --issues=InvalidReturnType,MissingReturnType,LessSpecificReturnType,MissingParamType --dry-run</code>     
<p><b>29 December 2022</b></p>
<p>Psalm Level 4 Testing (0 errors)</p>
<p>Using Generator...Translate{language file} and Setting...View...Google Translate...locale,
<p>Afrikaans, Russian, German, Ukrainian, Vietnamese language folders generated using Google Translate</p>
<p><b>Locale related files adjusted:</b></p>
<p>...src/Invoice/Setting/SettingRepository/locale_language_array/{insert the locale}</p>
<p>...src/Invoice/Asset/i18nAsset/{create your file with class name the same as the file name}</p>
<p>...resources/views/layout/main.php/{insert the language menu item}</p>
<p>...resources/views/layout/invoice.php{insert the 'use' namespace, 'case', and menu item.</p>
<p>...adjust the config/params 'locales' setting.</p>
<p><b>28 December 2022</b></p>
<p></p>
<p>Google Translate included to generate language files ie. ip_lang.php, gateway_lang.php, app.php for a locale.</p>    
<p><a href="https://github.com/googleapis/google-cloud-php-translate">Examples here</a></p>
<p><a href="https://console.cloud.google.com/iam-admin/serviceaccounts/details/
        // {unique_project_id}/keys?project={your_project_name}"">Build your cloud project<a/>
<p><b>Steps to get the Json file that must be saved in src/Invoice/google_tranlate_unique_folder</b></p>
<p>1: Download https://curl.haxx.se/ca/cacert.pem into active c:\wamp64\bin\php\php8.1.12 folder</p>
<p>2: Select your project that you created under https://console.cloud.google.com/projectselector2/iam-admin/serviceaccounts?supportedpurview=project'</p>
<p>3: Click on Actions icon and select Manage Keys</p>
<p>4: Add Key</p>
<p>5: Choose the Json File option and Download the file to src/Invoice/Google_translate_unique_folder</p>
<p>6: You will have to enable the Cloud Translation API and provide your billing details. You will be charged 0 currency</p>
<p>7: Move the file from views/generator/output_overwrite to eg. src/Invoice/Language/{your language}</p>
<p><b>Step to choose a locale for translation: Settings...View...Google Translate...{dropdown}...save</b></p>
<p><b>Steps to generate eg. ip_lang.php</b></p>
<p>1: Generator ...Translate English\ip_lang.php</p>
<p>2: Either copy the array from off the screen or move it from ..resources/views/generator/output_overwrite into the appropriate folder</p>
<P><s>3. The separator ie. 'YYYY' will enable you to do a search and replace ie search 'YYYY' and replace ', to provide a comma separating each array item from the screen outputted array with a comma.</s></p>
<p>4. Dont forget to restart Netbeans 16 after adjusting C:\Program Files\NetBeans-16\netbeans\etc\netbeans.conf and inserting <code>"-J-Dfile.encoding=UTF-8"</code>  to parameter "netbeans_default_options". when dealing with languages with special characters.</p>
<p><b>26th December 2022</b></p>
<p>Include additional demo languages</p>
<p><b>24th December 2022</b></p>
<p>1. Retesting Sending of invoices by email.</p>
<p>2. Remove 55 Psalm Errors mostly related to <a href="https://github.com/rossaddison/yii3-i/issues/5">Issue #5</a>
<p>3. Psalm Level 7,6,5,4 Testing using Psalm 5.4 instead of 4.3</p>    
<p>4. Auditing of Setting...View...Invoice...Default Public Template and Mark Invoices Sent Copy</p>.
<p>5. Moved rossaddison/yii-invoice to rossaddison/yii3-i.</p>
<p>6. Resynced rossaddison/yii-invoice fork.</p>
<p><b>20th December 2022</b></p>
<p>1. Moved repository into separate working folder under blog, and blog-api for separate github workflow purposes.</p>
<p>2. Invoice works separately from blog. There are no hyperlinks to blog.</p>
<p>3. The Entity User's relations to comment and post have been removed since these are no longer needed.<br>
However the user that is registered under the demo still has to be added to the userinv table using Setting...User Account.    
</p>
<p>4. New Setting: <b>Invoices marked as 'sent' when copying</b> so that a client can view them online immediately without having to be sent by email. See Settings...View...Invoices...Other Settings. This is also useful for testing a series of invoices against a payment gateway.</p>
<p>5. A checkbox 'sandbox' has been added to the SettingsRepository/payment_gateways array for amazon_pay, and braintree.
If you plan to use a language other than English, you will need to include <code>'online_payment_sandbox' => 'Sandbox',</code> in the corresponding src/Invoice/Language/{your_language}/gateway_lang.php file. 
ie. check that the gateway_lang.php in the English folder corresponds with your language folder.
<p>6. The omnipay payment gateway with stripe will give the following warning (which is not recommended to accept): </p>
<p>Payment failed. Please try again. Response: Sending credit card numbers directly to the Stripe API is generally unsafe. We suggest you use test tokens that map to the test card you are using, see https://stripe.com/docs/testing.</p>
<p>You will have to go into https://dashboard.stripe.com/settings/integration and toggle the following screen.</p>
<img src="/site/stripe.png" height="300" width="600">
<p>7. Github Static Analysis: Psalm Level 7,6,5,4 testing - errors 0</p>
<p>8. The function src\User\Console\AssignRoleCommand and CreateCommand is functional as the observer role can be assigned to a client with the result ending up as an assignment in ...invoices/resources/rbac</p>
<p>How to reach Psalm level 4 the following code is needed to suppress Psalm errors on level 4: </p>
<p>9. Braintree Payment Gateway has been introduced. </p>
<code>
    /**
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement, UndefinedInterfaceMethod
     */
</code>
<p>This will need to be looked at later.</p>     
<p><b>10th December 2022(Update July 2025 - Omnipay has been deleted)</b></p>
<p>Github Static Analysis: Psalm Level 7,6,5,4 testing - errors 0</p>
<p>1. Payment and Merchant (Online log) views upgraded to GridView.</p>
<p>2. A subarray has been added to the gateway array under SettingRepository/payment_gateways function <code>'version' => array(
                    'type' => 'checkbox',
                    'label' => 'Omnipay Version'                    
                    )</code><br>This is to facilitate the introduction of PCI compliant gateways. The two pci compliant tested gateways introduced here are: Stripe version 10 and Amazon_Pay. </p>
<p>3. If the above 'Omnipay version' checkbox is left unchecked for the specific gateway, you are implying that it is PCI compliant.</p>
<p>4. Uncheck 'Omnipay version' for Stripe Version 10 and the latest Amazon_Pay which have been introduced here.  </p>
<p>5. The guest url has been removed from the guest view. The user/client when logged in with 'observer role', has a list of enabled gateways in a list under options under the guest's invoice view. </p> 
<p>6. Stripe version 10 is PCI compliant because their js.stripe.com/v3 cdn is dealt with directly and no credit card details are shared. </p>
<p>7. Amazon Pay is PCI compliant because the user/client has to be logged into amazon.co.uk before they can use the Amazon Button. No credit card details are shared. </p>
<p>8. The PaymentInformationForm includes place to enter credit card details but this form only presents itself with Omnipay version checked gateways.
<p>9. The PaymentInformationController handles Omnipay and PCI compliant gateways.</p>
<p>10. Amazon_Pay has been integrated with productType set to PayOnly (see function form) so clients can pay if they have an Amazon Account.  </p>
<p>11. Register as a developer under Stripe to test the Omnipay Integration.
<p>12. The following code under ...resources/views/invoice/paymentinformation/payment_information_stripe_pci is of importance.</p>    
<p><code>. 'async function initialize() {'<br>
        // To avoid Error 422 Unprocessible entity <br>
        // const { clientSecret } = await fetch("/create.php", {<br>
        // method: "POST",<br>
        // headers: { "Content-Type": "application/json" },<br>
        // body: JSON.stringify({ items }),<br>
        // }).then((r) => r.json());<br>    
        . 'const { clientSecret } = {"clientSecret": "'. $client_secret .'"};'<br>
        . 'elements = stripe.elements({ clientSecret });'<br>
        . 'const paymentElementOptions = {'<br>
            . 'layout: "tabs"'<br>
        . '};'<br>
        . 'const paymentElement = elements.create("payment", paymentElementOptions);'<br>
        . 'paymentElement.mount("#payment-element");'<br>
    . '}'<br>
    </code>
</p>
<p>13. Stripe version 10 is working for debit/credit cards and for bacs. Login as a user with observer role.</p>
<img src="/site/stripe_v_10.png" height="300" width="450"/>
<p>14. Amazon Pay v2.40 is working if you are signed in to Amazon concurrently. Login as a user with observer role.</p>
<img src="/site/amazon_pay_v_2_40.png" height="300" width="450"/>
<p>15. The PaymentController and MerchantController guest related functions adopt the same approach as the InvController:</p>
<p><code>
        // Get the current user and determine from (Related logic: see Settings...User Account) whether they have been given <br>
        // either guest or admin rights. These rights are unrelated to rbac and serve as a second <br>
        // 'line of defense' to support role based admin control. <br>
        <br> 
        // Retrieve the user from Yii-Demo's list of users in the User Table <br>
        $user = $this->user_service->getUser(); <br>         
        <br>
        // Use this user's id to see whether a user has been setup under UserInv ie. yii-invoice's list of users <br>
        $userinv = ($uiR->repoUserInvUserIdcount((string)$user->getId()) > 0 <br>
                 ? $uiR->repoUserInvUserIdquery((string)$user->getId()) <br>
                 : null); <br>
        <br>        
        // Determine what clients have been allocated to this user (Related logic: see Settings...User Account) <br>
        // by looking at UserClient table <br>       
        <br>                
        // eg. If the user is a guest-accountant, they will have been allocated certain clients <br>
        // A user-quest-accountant will be allocated a series of clients <br>
        // A user-guest-client will be allocated their client number by the administrator so that <br>
        // they can view their invoices and make payment <br>
        $user_clients = (null!== $userinv ? $ucR->get_assigned_to_user($user->getId()) : []);</code>
</p>
<h2><a href="https://pay.amazon.co.uk/help/202022560">Notes for Amazon Pay</a></h2>
<p>To configure Amazon Pay you will need your Merchant ID, Public Key, Public Key ID and Private Key.</p>
<p>To find your keys and IDs:</p>
<ol>
    <li>sign in to your Amazon Payments merchant account in
        <a href="https://sellercentral-europe.amazon.com" target="_blank">Seller Central</a>
    </li>
    <li>click 
        <strong>Integration</strong>, and then click
        <strong>Integration Central</strong>
    </li>
    <li>select your
        <strong>Integration channel</strong>
    </li>
    <li>choose your <strong>ecommerce solution provider</strong>, or choose <strong>self-developed</strong></li>
    <li>choose your payment type in the
        <strong>Payment type</strong> dropdown</li>
    <li>click
        <strong>Get instructions</strong>, and then click
        <strong>Create keys</strong> in the Instructions section

    </li>
</ol>
<p>To enable the Sign in with Amazon button on your website, you will need a Client ID/Store ID.</p>
<p>To create a Client ID/Store ID or to manage applications:</p>
<ol>
    <li>sign in to your Amazon Payments merchant account in
        <a href="https://sellercentral-europe.amazon.com" target="_blank">Seller Central</a>
    </li>
    <li>choose
        <strong>Amazon Pay (Production View)</strong> from the dropdown on top of the page</li>
    <li>click
        <strong>Integration</strong> within the navigation bar, and then click
        <strong>Integration Central</strong>
    </li>
    <li>scroll to the end the page and click
        <strong>View Client ID/Store ID(s)</strong>, or click
        <strong>Create new Client ID/Store ID</strong>
    </li>
</ol>
<h2><a href="https://stripe.com/docs/keys">Notes for Stripe</a></h2>
<p>Api Key - private key not used in the package and not to be shown to anybody. It can be stored in the database if you choose.</p>
<p>Publishable Key eg. pk_... - Goto Stripe DashBoard at https://dashboard.stripe.com and store in Setting...View...Online Payment...Stripe</p>
<p>Secret Key eg sk_... -  Goto Stripe DashBoard at https://dashboard.stripe.com and store in Setting...View...Online Payment...Stripe</p>
<p>All keys viewed by phpMyAdmin are encoded with Crypt and bear no resemblance to the original keys.</p>
<p>javascript <code>$client_secret</code> in views/invoice/paymentinformation/payment_information_stripe_pci.php - generated by Payment Intent in PaymentInformationController/get_stripe_pci_client_secret based on data passed to payment intent.</p>
<p>...resources/views/layout/invoice.php and quest.php incorporate the following settings which will load either the Stripe asset or the Amazon Pay Asset</p>
<p><code>// '0' => PCI Compliant version<br>
$s->getSetting('gateway_stripe_version') == '0' ? $assetManager->register(stripe_v10_Asset::class) : '';<br>
$s->getSetting('gateway_amazon_pay_version') == '0' ? $assetManager->register(amazon_pay_v2_4_Asset::class) : '';<br></code>
</p>
<p>Enable these Payment Gateways under Settings...Views...Online Payment</p>
<p><b>11th November 2022</b></p>
<p>Bug fix: Include Integration 18.066. Paginators working with external status by inserting status into urlArguments.</p>
<code>
     OffsetPagination::widget()<br>
         ->menuClass('pagination justify-content-center')<br>
         ->paginator($paginator)<br>         
         // No need to use page argument since built-in. Use status bar value passed from urlGenerator to quote/guest<br>
         ->urlArguments(['status'=>$status])<br>
         ->render(),<br>
    )
</code>    
<p><b>8th November 2022</b></p>
<p>Add tabs draft, sent, viewed, approved, canceled, rejected to client view quote</p>
<p>Add tabs draft, sent, viewed, paid to client view invoice</p>
<p><b>4th November 2022</b></p>
<p>Psalm level 7,6,5,4 testng - 0 errors</p>
<p>Bugfixing quotes and invoices</p>
<p>Moved views to resources.</p>
<p><b>25th October 2022</b></p>
<p>Invoice, Quote, Product, UserInv GridView adjusted for latest Gridview code adjustments. Product table can now be sorted.</p>
<p>All tables sort with descending ie. minus sign attached to Sort:: array's id ie. -id</p>
<code>$sort = Sort::only(['status_id','number','date_created','date_due','id','client_id'])
                // (Related logic: see vendor\yiisoft\data\src\Reader\Sort
                // - => 'desc'  so -id => default descending on id
                // Show the latest quotes first => -id
                ->withOrderString($query_params['sort'] ?? '-id'); </code>
<p>The email config params mentioned in 11th October, now have to be linked to the Email Sending Method on Setting...Email...Email Sending Method.</p>
<p>The following code added to the end of the latest yiisoft/demo composer.json ensures that rossaddison/yii-invoice</p> 
<p>will work with psr-3. The dev-psr-3 branch will be picked up. </p>
<code>"rossaddison/mpdf": "*"</code>    
<p>Psalm Level - 7,6,5,4 Testing - 0 errors</p>
<p>Include start date and end date on CompanyPrivate Entity</p>
<p>Archived pdf can now be sent automatically with singular email attachment using Setting...View...Email...Attach Quote/Invoice on email?</p>
<p>MailerHelper/yii_mailer_send function <br>
<code>
    $path_info = pathinfo($pdf_template_target_path); <br>
    $path_info_file_name = $path_info['filename']; <br>
    $email_attachments_with_pdf_template = $email->withAttached(File::fromPath(FileHelper::normalizePath($pdf_template_target_path), <br>
    $path_info_file_name, <br>
    'application/pdf') <br>
    ); <br>
</code>
</p>
<p><b>11h October 2022</b></p>
<p>Psalm Level - 7,6,5,4 Testing - 0 errors</p>
<p>Setting...View...Email shows some config/params.php settings using SettingRepository <code>config_params</code> function.</p>
<p>
<code>
    public function config_params() : array { <br>    
        $config = ConfigFactory::create(new ConfigPaths(dirname(dirname(dirname(__DIR__))), 'config/'), null); <br>
        $params = $config->get('params'); <br>
        $config_array = [ <br>
            'esmtp_scheme' =>$params['symfony/mailer']['esmtpTransport']['scheme'],<br>
            'esmtp_host'=>$params['symfony/mailer']['esmtpTransport']['host'],<br>
            'esmtp_port'=>$params['symfony/mailer']['esmtpTransport']['port'],<br>
            'use_send_mail'=>$params['yiisoft/mailer']['useSendmail'] == 1 ? $this->trans('true') : $this->trans('false'),<br>
        ];<br>
        return $config_array;<br>
    }     
</code>
</p>
<p>Continue with auditing of settings</p>
<p>Bug fix with test data</p>
<p><b>4th October 2022</b></p>
<p>Psalm Level 4 Testing Complete - 150 errors removed.</p>
<p><b>1st October 2022</b></p>
<p>Files can be attached to invoices. Security measure <code>is_uploaded_file php</code> function using 
    specifically a tmp file. See InvController/attachment_move_to function. </p>
<p>Add adjustments to Generator Templates</p>
<p><b>21st September 2022</b></p>
<p>Simplify accesschecker permissions to 4 basic permissions ie. editInv, and viewInv, and editPayment and viewPayment and editUser for all controllers.
<p>Inclusion of Yii's php form code replacing Html code on forms mailer_quote and mailer_invoice.</p>
<p>Psalm Level 6 - 155 errors removed. Remaining errors are yii-demo related.</p>
<p>Psalm Level 5 - 47 errors removed. Remaining errors are yii-demo related.</p>
<p>YiiMailer (adaptation of Symfonymailer) replaces phpmailer utilizing the code provided in yii-demo.</p>
<p>Files can be attached to emails using Send Email under Dropdown Options menu on the Quote/Invoice View Yii. Setting up the email templates is optional.</p>
<p>Users with observer role can view the quotes or invoices, and then access the 'approve or reject section' by using the guest url on the quote or invoice and pasting it into the browser.</p>
<p>Inclusion of below code in config/params and adjustments to MailerHelper and TemplateHelper to facilitate emailing.</p>
<p>Email Templates are working through javascript and parsing of data to build the template is functional.</p>
<p><s>Note: Yii's php textbox, rather than preferably a textarea, is currently used to display the email template.</s></p>
<p>Emailing of quotes/invoices with adjustment to config.params. 
<code>
    'yiisoft/mailer' => [<br>
        'messageBodyTemplate' => [<br>
            'viewPath' => '@src/Contact/mail',<br>
        ],<br>
        'fileMailer' => [<br>
            'fileMailerStorage' => '@runtime/mail',<br>
        ],<br>
        'useSendmail' => false,<br>
        'writeToFiles' => false,<br>
    ],<br>
    'symfony/mailer' => [<br>
        'esmtpTransport' => [<br>
            'scheme' => 'smtp', // "smtps": using TLS, "smtp": without using TLS.<br>
            'host' => 'mail.btinternet.com',<br>
            'port' => 25,<br>
            'username' => 'ross.addison@yourinternet.com',<br>
            'password' => 'yourpassword',<br>
            'options' => [], // See: https://symfony.com/doc/current/mailer.html#tls-peer-verification<br>
        ],<br>
    ],    </code>

<p>Navbar on invoice layout includes an offcanvas Menu that works with the 'burger icon' menu button if NOT a full screen and moves in from the left.</p>
<p>Role: Observer included so customers can view their quotes, invoices, and payments online.</p>
<p><b>To assign the observer role to a client so they can view their invoices online.</b></p>
<p><b>Step 1:</b> Sign up user using Yii-demo</p>
<p><b>Step 2:</b> Signed in as Administrator, make sure this new user is a client ie. Client...View...New</p>
<p><b>Step 3:</b> As administatrator, make sure this user has Guest (read/only) permissions under Settings...User Account</p>
<p><b>Step 4:</b> Add signed up client / user to Settings...User Account table and use Assigned Clients 'burger' to add/associate this client to their user id.</p>
<p><b>Step 5:</b> Use assignRole 'observer' and user id from User Account to assign observer permissions to this user/client.</p>
<p><code>C:\wamp64\www\yii-invoice>yii user/assignRole observer 3</code></p>
<p><b>Step 6:</b> Create some invoices for this user/client signed in as Administrator and add payments.</p>
<p><b>Step 7:</b> Login as user/client and view the invoices and associated payments that you as Administrator have created for them.</p>
<p>The yii logo is called up permanently in the navbar with the logo located in the public folder.</p>
<p>The login logo previously located under Settings ... General has been moved to Settings...Company Private Details.</p>
<p>Each Company Private Detail record can now have their own logo/icon</p>
<p>This is useful for a company logo that evolves with time, older invoices retaining their older logo.</p> 
<p>Use Yii's<code> Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('CompanyPrivateForm')
    ->open()</code> on forms instead of Html form tag</p>
<p><code>C:\wamp64\www\yii-invoice>php ./vendor/bin/psalm 
         --alter --issues=InvalidNullableReturnType,
                          InvalidReturnType,
                          MissingReturnType,
                          LessSpecificReturnType,
                          MismatchingDocblockParamType,
                          MismatchingDocblockReturnType,
                          MissingParamType --dry-run</code></p>
<p><b>20th August 2022</b></p>
<p>Menu font enlarged.</p>
<p>Psalm Level 7 testing using <code>php ./vendor/bin/psalm</code>at command prompt: 0 errors.</p>
<p>Upgraded index for Quote and Invoice - sortable headers</p>
<p>Include a three input box Start of tax year date under Settings..View..Taxes for Tax Reporting.</p>
<p>Include a Y-m-d H:i:s format for invoicing.</p>
<p><b>Reports</b>Sales by Client, Sales by Date, Payment History, and Invoice Aging completed.</p>
<p>Move invoice/index schema code to Generator...Schema</p>
<p>Setting...UserAccount functional. Payment...Enter functional.</p>
<p>Invoices cannot be edited once paid. The read only flag is used on the invoice and buttons are disabled.</p>
<p><b>5th August 2022</b> - The Dashboard has been put up. Once Sumex is further developed it will be added to the dashboard. Accessible by menu: Dashboard </p>
<p>To avoid issues concerning psr3 and unvoided return types in mpdf/mpdf I have forked the latest mpdf/mpdf development repo and made adjustments to the relevant files. </p>
<p>The fork rossaddison/mpdf has been included in the composer.json file. Although at this stage it will run using version mpdf/mpdf 8.016, using psr 2, I have the reassurance that these errors concerning void will not show up in Yii</p> 
<p><b>29th July 2022</b> - Psalm testing using 'php ./vendor/bin/psalm' at command prompt - static errors removed. Info issues reduced from 2800 to 2000.</p>
<p>The client view now includes client's details, quotes, invoices, notes, and custom fields tabs.</p>
<p>Relevant javascript has been added to client.js ... load_client_notes, and save_client_notes.</p>
<b>21st July 2022</b>
<p>Invoice/Layout/main.php has been moved to @views/layout/invoice.php. The cldr setting value is given the $session->get('_language') value by means of the locale dropdown.</p>
<p>The $s value is configured for the @views/layout/invoice.php. (different to the demo layout ie. views/layout/main.php) in config/params.php yii-soft/view Reference::to and NOT by means of the InvoiceController .
   The $s value is necessary in this layout to record the current locale in the cldr setting if it is selected BEFORE login. ie. 
   <code><s>$s->save_session_locale_to_cldr($session->get('_language') ?? ($s->getSetting('cldr') ? $s->getSetting('cldr') : 'en'));</s></code>
<p></p>
<p>jquery 1.13.2 which has just been released is now the default. jquery 1.13.0 has been removed. </p>
<p>In demo mode, the menu now includes the ability to test data with pre-setup clients, products, groups, tax rates. A deinstall feature of demo clients(2), products(2) is available. </p><!-- comment -->
<p>Two additional settings under the Settings tab-index help with this eg. Install test data and Use test data. These work with Generator...Remove Test Data...Reset Test Data</p>
<p>Datepickers now change automatically according to the locale dropdown with eg. views/client/form.php javascript function at the bottom.</p>
<p>Individual datepickers have been removed, and only one datepicker is used on the @views/layout/invoice.php page.
    <code>
        ...php<br>
    
    $js11 = "$(function () {".
    '$(".form-control.input-sm.datepicker").datepicker({dateFormat:"'.$datehelper->datepicker_dateFormat()<br>
                                                        .'", firstDay:'.$datehelper->datepicker_firstDay()<br>
                                                        .', changeMonth: true'<br>
                                                        .', changeYear: true'<br>
                                                        .', yearRange: "-50:+10"'<br>
                                                        .', clickInput: true'<br>
                                                        .', constrainInput: false'<br>
                                                        .', highlightWeek: true'<br>
                                                        .' });'.<br>
    '});';<br>
    echo Html::script($js11)->type('module');<br>
    
    </code>
<p>Every setting under views/invoice/setting/views is being audited by a <code>$s->where('number_format')</code> which tooltips why and where the setting is used.</p>
<p>config/params.php : Uncommenting Synctables (Table structure relatively permanent now) and assigning READ_AND_WRITE parameter to below with significant performance improvement.</p>
<code>\Yiisoft\Yii\Cycle\Schema\Provider\PhpFileSchemaProvider::class => [
                'mode' => \Yiisoft\Yii\Cycle\Schema\Provider\PhpFileSchemaProvider::MODE_READ_AND_WRITE,
                'file' => 'runtime/schema.php',
],</code>
<p>Inclusion of all countries from https://en.wikipedia.org/wiki/ISO_4217</p>
<p><b>9th July 2022</b> - Psalm testing using ./vendor/bin/psalm at the command prompt is now complete. 155 static errors and redundant code removed.</p>
<p><s><a href="https://github.com/yiisoft/demo/issues/439" >Issue 439: BelongsTo relation not updating on edit of relation field eg. Product' relation field tax rate is not editing and updating.</a></s> 
<s>Absolute Clear Cache path is located under SettingsController clear function and it will have to be adjusted to your setup in debug mode. </s>   
<s>public function clear() $directory = "C:\wamp64\www\yii-invoice\public\assets";</s>
<p>The SettingsController clear cache function is functional without an absolute path now.</p>
<p>The LAMP administrator will have to add sufficient permission to the public assets folder using the <code>sudo chown -R username folderpath </code> command before assets can be deleted in debug mode.</p>
<p>Quote - The Quote is functional ie. can be pdf'd <s>but the emailing aspect has to be developed.</s>and the emailing aspect has been tested with sendmail. Testing with SMTP is in progress.</p>
<p>Invoice - The Invoice is functional ie. can be pdf'd and archived <s>but the emailing aspect has to be developed.</s> and sendmail is functional. </p> 
<p>Recurring invoices - Functional but not fully tested.</p>
<p>Payment - Can be recorded against an Invoice. The latest version in League/Omnipay v3.2 will be setup with a few of the major payment providers added to the composer.json</p>
<p>User Custom Fields - not started yet.</p>
<p>File Attachments - not started yet. </p>
<p>Settings...View(Debug mode ie. Red) - These are being used.</p>
<p>Settings...View(Non-Debug mode ie. Not Red) - Some are being used. Their functionality is currently being analysed in Invoiceplane.--</p>
<p>The userinv table is an extension of yii/demo's user table and contains all the critical user information.</p>
</p>
<b>Setting Up</b>
<p>The settings table builds up initially with a default array of settings via the InvoiceController if they do not exist ie. setting 'default_settings_exist' does not exist.</p>
<b>Generator</b>
<p>The code generator templates have been adapted according to the latest demo updates.</p>
<b>Annotations</b>
<p>The lengthy Entity annotations have been replaced with the more concise Attributes coding structure. eg. <code> * @ORM\Column(type="string")</code>
    replaced with <code>#[ORM\Column(type: "string")]</code>. <s>However issue 439 is currently relevant here.</s>  
</p>
<b>Demo Mode</b>
<p>A demo mode variable located in src\Invoice\Layout\main.php ie. <code>$demo_mode</code> can be set to false to remove performance settings and the clear cache tool.
All areas in red will be removed.</p>
<b>Jquery</b>
<p>Jquery  3.6.0 (March 2nd 2021) version is being used for custom fields, and smaller modals. Temporarily, Invoiceplane's dependencies.js file is being used in AppAsset.
The modals are dependent on it. </p>
<b>Html Tags on Views</b>
<p>Views can be improved with more Yii related tags ie. using <code>Html::tag</code>. Html::encode is mandatory or compulsory or always present.</p>
<b>Paginator</b>
<p>The length of the lists can be changed via setting/view: <code>default_list_limit</code></p>
<b>Locales</b>
<p>The SettingRepository <code>load_language_folder</code> function accepts the dropdown locale through yiisoft/demo's <code>$session->get('_language')</code> function: this setting takes precedence over the database 'default_language' setting when set.</p>
<b>Client's language different to locale_derived_language or fallback settings 'default_language'</b>
<p>When printing occurs, the client's language ensures the documentation is printed out in his/her language using <code>$session->get('print_language')</code>
<p>The session variable <code>print_language</code> is reset after printing.</p>    
<b>Languages</b>
<p>Any words used not in the Invoiceplane folders, will be translated using Yii's translation methodology.</p>
<p>The above menu's language can be created in ...resources/messages for a specific language.
<p>Language folders can be imported from Invoiceplane but the following code must be inserted in each file <code>declare(strict_types=1);</code>within that folder.</p>
<b>Steps to include a language</b>
<p>1. Include the language folder in src/Invoice/Language after including declare(strict_types) in each file.</p>
<p>2. Include the new language in SettingsRepository's locale_language_array</p>
<p>3. Adjust the config/params.php locales array.</p>
<p>4. Adjust the views/layout/main.php menu.</p>
<p>5. Adjust each of the resources/messages folders language array.</p>
<p>6. CJK (C(hinese) J(apanese) K(orean) Languages <a href="https://mpdf.github.io/fonts-languages/cjk-languages.html"></a>
In order to view a file with non-embedded CJK (chinese-japanese-korean) fonts, you - and other users - need to download the Asian font pack for Adobe Reader for the languages:

Chinese (Simplified),
Chinese (Traditional),
Korean,
and Japanese

<a href="https://helpx.adobe.com/acrobat/kb/windows-font-packs-32-bit-reader.html">For Windows</a>
<a href="https://helpx.adobe.com/acrobat/kb/macintosh-font-packs�acrobat�reader-.html">For Mac</a></p>
<p>If spaces appear where the language should appear whilst viewing using eg. Chrome default PDF reader, add the extension - Chrome PDF Viewer 2.3.164.</p>
<p>7. When copying, and pasting the Chinese Simplified folder make sure that you remove the space between the Chinese and Simplified. ie. ChineseSimplified. This is camelcase. </p>
<b>Netbeans: <a href="https://stackoverflow.com/questions/59800221/gradle-netbeans-howto-set-encoding-to-utf-8-in-editor-and-compiler">How to include UTF-8 in Netbeans</a></b>
<p>Set encoding used in Netbeans globally to UTF-8. Added in netbeans.conf "-J-Dfile.encoding=UTF-8" to parameter "netbeans_default_options". This unfortunately has
to be done everytime you edit a file with 'special letters'. So edit the file with the UTF-8 setting above, save it, and then remove the above setting from Netbeans.conf. </p> 
<p>File Location: C:\Program Files\NetBeans-16\netbeans\etc\netbeans.conf open with notepad cntrl+F netbeans_default_options </p>
<b>Improved Features</b>
<p>A start tax year date eg. 06/04/2022 can be setup under view so that reports will use this by default as their start date.</p>
<p>The invoice aging report includes the invoice number(s) of the outstanding balance.</p>
<p>The Sales by Client report includes more specifics including the Sales without Tax, Item Tax, and Invoice Tax, and Sales Inclusive amount.</p>
<p>The Sales by Date (aka Sales by Year) report breaks each Sales per Client into quarters over the selected years.</p>
<p></p>
<p>Multiple products of quantity of 1 can be selected from the products lookup with the 'burger' or '3 horizontal lines' icon whilst in quote.</p>
<p>Multiple items can be deleted under the options button with 'Delete Item'.</p>
<p>Company details are divided into public and private. The profile table is intended for multiple profiles especially when email addresses, and mobile numbers change.</p>
<p>If a Tax -Rate is set to default, it will be used on all new quotes and invoices. ie. A Quote Tax Rate will be created from this Tax Rate automatically and will be created and used on all new quotes. The same will apply to all invoices.</p>
<p>If you want to create a quote or an invoice group, specific to a client, use the Setting...Group feature to setup a Group identifier eg. JOE for the  JOE Ltd Company. The next id will be appended to JOE ie. JOE001.</p>
<p>Products and Tasks can be entered separately on an invoice and are mutually exclusive so if you enter a task you cannot enter a product at the same time.</p>
<b>Deprecated Original Features</b>
<p>Themes will be introduced at a later date.</p>
<p>It is not intended to deprecate any of the features currently in InvoicePlane.</p>
<b>Proposed Features</b>
<p>Upgrading the forms to include the demo's php or server based form structure.</p>
<p>An interlinked basic bookkeeping system to audit transactions and produce an audit trail of invoices and payments</p>
<b>Security</b>
<p>All Entity properties initialized before the construct should be private. The private property is accessed through a public getter method as built below the construct.</p>
<b>Reasons for using a simplified <code>id</code> as a primary key in all the tables</b>
<p>See <a href="https://cycle-orm.dev/docs/annotated-relations/1.x/en#belongsto">{relationName}_{outerKey}</a>, the outerKey being the primary key, structure. 
    Eg. the field <code>tax_rate_id</code> in the Product table is a relation or a foreign key in the Product table equal and pointing to its parent table's Tax Rate's <code>id</code>  
    so the relation name <b>variable</b> in Entity: Product must be <code>$tax_rate</code> and joined with the outerKey as <code>$id</code> you get <code>$tax_rate_id</code> which matches the foreign key <code>$tax_rate_id</code> in Entity: Product
    If the primary key in the Tax Rate table was named something like tax_rate_id and not id then the relation could not be given a name.
</p>
<b>Future Work</b>
<p>Psalm static testing and removing of INFO suggestions.</p>
<p>Yiisoft's Mailer function incorporated using adminEmail as default config param for userinv->email.</p>
<p>A new feature of product custom fields has to be developed.</p>
<p>Client Custom Fields dependency on client_custom_fields.js will be removed as has been done for Payment Custom Fields. </p>
<p>Redundant functions generated by the Generator have to be deleted.</p>
<b>Work in progress</b>
<p>The client/view has been developed but the index under the Client View has to be rebuilt using the code similar to the new Invoice index.</p>
<p>The dashboard is being developed with Sumex still to be incorporated.</p>
<p>All the settings in the setting view,  still have to be linked to their specific purpose by consulting with the Original Invoiceplane code. Progress on this has been made especially with the Email tab.</p>
<p>All forms have to be modified from Html to Yii3's php based form structure.
<p>Email Template</p>
<p>User custom fields have to be developed.</p>
<p>Accessing a quote using the url_key in the browser is possible now by a customer. The url_key is retrieved at the bottom of the  quote by admin and pasted into an email. Login as a user who has the observer role with user_id 7, (after the 6 dummy users in demo) to test the customer's login ability. The customer can be sent the url_key (instead of the actual quote) by email. The user can then accept or reject the quote online using this url_key. This quote can then be copied to an invoice by the administrator if accepted by the customer.</p>
